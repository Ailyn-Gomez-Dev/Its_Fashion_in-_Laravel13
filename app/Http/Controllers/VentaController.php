<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VentaController extends Controller
{
    /**
     * Muestra el catálogo de ventas y KPIs.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Si es cliente, redirigir a su vista específica mis_compras
        if ($user->rol_id === 3) {
            $misVentas = Venta::where('cliente_id', $user->id)->orderBy('fecha', 'desc')->get();
            $totalGastado = Venta::where('cliente_id', $user->id)->where('estado', 'Completada')->sum('total');
            return view('cliente.mis_compras', compact('misVentas', 'totalGastado'));
        }

        // Obtener las ventas con relaciones cargadas
        $ventas = Venta::with(['cliente', 'usuario'])->orderBy('fecha', 'desc')->get();

        // Obtener clientes activos (rol_id = 3 representa el rol Cliente en la base de datos)
        $clientes = User::where('rol_id', 3)->where('estado', 'Activo')->orderBy('nombre', 'asc')->get();

        // Obtener productos activos con stock
        $productos = Producto::where('estado', 'Activo')->orderBy('nombre', 'asc')->get();

        // Verificar si la caja está abierta
        $cajaAbierta = DB::table('cajas')
            ->where('estado', 'Abierta')
            ->exists();

        // KPIs de ventas
        $kpi = [
            'total' => Venta::count(),
            'monto' => Venta::where('estado', 'Completada')->sum('total'),
            'completadas' => Venta::where('estado', 'Completada')->count(),
            'hoy' => Venta::whereDate('fecha', today())->count(),
        ];

        if ($user->rol_id === 2) {
            return view('empleado.ventas', compact('ventas', 'clientes', 'productos', 'cajaAbierta', 'kpi'));
        }

        return view('admin.ventas', compact('ventas', 'clientes', 'productos', 'cajaAbierta', 'kpi'));
    }

    /**
     * Almacena una nueva venta en una transacción de base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if ((int)$user->rol_id === 3) {
            return response()->json([
                'ok' => false,
                'msg' => 'Acceso denegado. Los clientes no están autorizados para registrar ventas de punto de venta.'
            ], 403);
        }

        $request->validate([
            'cliente_id' => ['nullable', 'exists:users,id'],
            'metodo_pago' => ['required', 'in:Efectivo,Transferencia bancaria'],
            'items' => ['required', 'string'], // Esperamos un JSON serializado de los ítems de venta
        ], [
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago seleccionado es inválido.',
            'items.required' => 'Debes agregar al menos un producto al detalle de venta.',
        ]);

        $items = json_decode($request->items, true);

        if (empty($items)) {
            return response()->json(['ok' => false, 'msg' => 'Agrega al menos un producto a la venta.']);
        }

        // Si el pago es en efectivo y no hay caja abierta, bloquear transacción
        if ($request->metodo_pago === 'Efectivo') {
            $cajaAbierta = DB::table('cajas')->where('estado', 'Abierta')->exists();
            if (!$cajaAbierta) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Debe abrir la caja en el módulo de caja para poder registrar pagos en efectivo.'
                ]);
            }
        }

        try {
            $idVenta = DB::transaction(function () use ($request, $items) {
                // 1. Verificar stock disponible con bloqueo pesimista antes de proceder
                foreach ($items as $item) {
                    $producto = Producto::lockForUpdate()->find($item['id_producto']);
                    if (!$producto || $producto->estado !== 'Activo') {
                        throw new \Exception("El producto '{$item['nombre']}' ya no está disponible.");
                    }
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para '{$producto->nombre}' (Disponible: {$producto->stock}, Solicitado: {$item['cantidad']}).");
                    }
                }

                // 2. Calcular total de la venta garantizando precios oficiales de catálogo
                $total = 0;
                $itemsProcesados = [];
                foreach ($items as $item) {
                    $producto = Producto::lockForUpdate()->find($item['id_producto']);
                    $cantidad = max(1, (int) $item['cantidad']);
                    $precioOficial = (float) $producto->precio_venta;
                    $total += ($cantidad * $precioOficial);
                    $itemsProcesados[] = [
                        'producto' => $producto,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioOficial,
                    ];
                }

                // 3. Registrar venta
                $venta = Venta::create([
                    'fecha' => now(),
                    'total' => $total,
                    'cliente_id' => $request->cliente_id ? $request->cliente_id : null,
                    'metodo_pago' => $request->metodo_pago,
                    'estado' => 'Completada',
                    'usuario_id' => Auth::id(),
                ]);

                // 4. Registrar detalles y actualizar stock
                foreach ($itemsProcesados as $itemData) {
                    $producto = $itemData['producto'];
                    $cantidad = $itemData['cantidad'];
                    $precioUnitario = $itemData['precio_unitario'];

                    // Guardar detalle_ventas
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                    ]);

                    // Descontar stock del producto
                    $nuevoStock = $producto->stock - $cantidad;
                    $producto->update(['stock' => $nuevoStock]);

                    // NOTIFICACIÓN: Stock crítico
                    if ($nuevoStock <= $producto->stock_minimo) {
                        try {
                            $admins = \App\Models\User::where('rol_id', 1)->get();
                            $noti = new \App\Notifications\SystemNotification(
                                'Stock Crítico',
                                "El producto '{$producto->nombre}' ha alcanzado un stock crítico ({$nuevoStock} unidades restantes).",
                                'stock_critico',
                                url('/inventario')
                            );
                            foreach ($admins as $admin) {
                                $admin->notify($noti);
                            }
                        } catch (\Exception $ex) {
                            // Silenciar fallos de notificación
                        }
                    }

                    // Registrar movimiento de Salida en el Kardex (inventario)
                    Inventario::registrarMovimiento($producto->id, 'Salida', $nuevoStock, $cantidad);
                }

                // 5. Registrar en caja activa ÚNICAMENTE si el pago fue en Efectivo (para evitar descuadre en arqueo físico)
                if ($request->metodo_pago === 'Efectivo') {
                    $caja = DB::table('cajas')
                        ->where('estado', 'Abierta')
                        ->first();

                    if ($caja) {
                        // Registrar el movimiento de ingreso de caja
                        DB::table('movimientos_caja')->insert([
                            'caja_id' => $caja->id,
                            'tipo' => 'Ingreso',
                            'monto' => $total,
                            'concepto' => "Venta #{$venta->id} (Efectivo)",
                            'fecha' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Actualizar el total_ingresos de la caja activa
                        DB::table('cajas')
                            ->where('id', $caja->id)
                            ->update([
                                'total_ingresos' => DB::raw("COALESCE(total_ingresos, 0) + {$total}"),
                                'updated_at' => now(),
                            ]);
                    }
                }

                return $venta->id;
            });

            return response()->json([
                'ok' => true,
                'msg' => 'Venta registrada correctamente.',
                'id_venta' => $idVenta
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Error al registrar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra los detalles de una venta específica en formato JSON.
     */
    public function show(Venta $venta): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if ((int)$user->rol_id === 3 && (int)$venta->cliente_id !== (int)$user->id) {
            return response()->json(['error' => 'No autorizado para ver esta venta.'], 403);
        }

        $detalles = DetalleVenta::with('producto')
            ->where('venta_id', $venta->id)
            ->get()
            ->map(function ($d) {
                return [
                    'id_producto' => $d->producto_id,
                    'producto' => $d->producto->nombre ?? 'Producto Eliminado',
                    'talla' => $d->producto->talla ?? '—',
                    'color' => $d->producto->color ?? '—',
                    'cantidad' => $d->cantidad,
                    'precio_unitario' => $d->precio_unitario,
                    'subtotal' => $d->cantidad * $d->precio_unitario,
                ];
            });

        return response()->json($detalles);
    }

    /**
     * Busca clientes activos para el autocompletado en el formulario de ventas.
     */
    public function buscarCliente(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));

        $query = User::where('rol_id', 3)->where('estado', 'Activo');

        if (strlen($q) >= 1) {
            $query->where(function($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('documento', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%");
            });
        }

        $clientes = $query->orderBy('nombre', 'asc')
            ->limit(10)
            ->get(['id', 'nombre', 'apellido', 'email', 'documento', 'telefono']);

        return response()->json($clientes);
    }

    /**
     * Genera el comprobante de venta imprimible en formato ticket POS.
     */
    public function comprobante(Venta $venta): View
    {
        /** @var User $user */
        $user = Auth::user();
        if ((int)$user->rol_id === 3 && (int)$venta->cliente_id !== (int)$user->id) {
            abort(403, 'No tienes autorización para consultar este comprobante.');
        }

        $venta->load(['cliente', 'usuario', 'detalles.producto']);

        return view('ventas.comprobante', compact('venta'));
    }
}
