<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VentaApiController extends Controller
{
    /**
     * Listado de ventas.
     * GET /api/ventas
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Venta::with(['cliente', 'usuario', 'detalles.producto'])
            ->orderBy('fecha', 'desc');

        // Si es cliente (rol_id = 3), sólo ve sus propias compras
        if ((int)$user->rol_id === 3) {
            $query->where('cliente_id', $user->id);
        } else {
            // Filtros opcionales para administradores y vendedores
            if ($request->has('cliente_id') && !empty($request->cliente_id)) {
                $query->where('cliente_id', $request->cliente_id);
            }
            if ($request->has('metodo_pago') && !empty($request->metodo_pago)) {
                $query->where('metodo_pago', $request->metodo_pago);
            }
            if ($request->has('fecha') && !empty($request->fecha)) {
                $query->whereDate('fecha', $request->fecha);
            }
        }

        $ventas = $query->paginate($request->input('per_page', 20));

        // KPIs rápidos
        $kpi = [
            'total_ventas' => (int) $user->rol_id === 3 
                ? Venta::where('cliente_id', $user->id)->count() 
                : Venta::count(),
            'monto_total'  => (float) ((int) $user->rol_id === 3 
                ? Venta::where('cliente_id', $user->id)->where('estado', 'Completada')->sum('total') 
                : Venta::where('estado', 'Completada')->sum('total')),
        ];

        return response()->json([
            'success' => true,
            'kpi'     => $kpi,
            'data'    => $ventas,
        ]);
    }

    /**
     * Detalle de una venta específica.
     * GET /api/ventas/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $venta = Venta::with(['cliente', 'usuario', 'detalles.producto.categoria', 'devoluciones'])
            ->findOrFail($id);

        // Si es cliente, verificar que le pertenezca
        if ((int)$user->rol_id === 3 && (int)$venta->cliente_id !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver esta venta.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $venta,
        ]);
    }

    /**
     * Registrar una nueva venta desde la app móvil.
     * POST /api/ventas
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'metodo_pago' => ['required', 'in:Efectivo,Transferencia bancaria'],
            'items'       => ['required'], // Array u objeto JSON con productos
        ], [
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in'       => 'Método de pago inválido (Efectivo o Transferencia bancaria).',
            'items.required'       => 'Debes incluir al menos un producto en la venta.',
        ]);

        $rawItems = $request->input('items');
        $items = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;

        if (empty($items) || !is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'La lista de productos es requerida.',
            ], 422);
        }

        // Si es cliente, el cliente_id es su propio ID; si es vendedor/admin, el que seleccione o null
        $clienteId = (int)$user->rol_id === 3 ? $user->id : $request->input('cliente_id');

        // Validar caja si el pago es en efectivo y el usuario no es cliente autoservicio
        if ($request->metodo_pago === 'Efectivo') {
            $cajaAbierta = DB::table('cajas')->where('estado', 'Abierta')->exists();
            if (!$cajaAbierta && (int)$user->rol_id !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes tener una caja abierta para procesar pagos en efectivo.',
                ], 422);
            }
        }

        try {
            $venta = DB::transaction(function () use ($request, $items, $clienteId, $user) {
                // 1. Validar disponibilidad de stock de todos los ítems con bloqueo pesimista
                foreach ($items as $item) {
                    $productoId = $item['producto_id'] ?? $item['id_producto'] ?? $item['id'];
                    $cantidad   = (int) ($item['cantidad'] ?? 1);

                    $producto = Producto::lockForUpdate()->find($productoId);
                    if (!$producto || $producto->estado !== 'Activo') {
                        throw new \Exception("El producto no está disponible o se encuentra inactivo.");
                    }
                    if ($producto->stock < $cantidad) {
                        throw new \Exception("Stock insuficiente para '{$producto->nombre}' (Disponible: {$producto->stock}, Solicitado: {$cantidad}).");
                    }
                }

                // 2. Calcular total de la venta garantizando precios oficiales de la base de datos
                $total = 0;
                $itemsProcesados = [];
                foreach ($items as $item) {
                    $productoId = $item['producto_id'] ?? $item['id_producto'] ?? $item['id'];
                    $cantidad   = max(1, (int) ($item['cantidad'] ?? 1));
                    $producto   = Producto::lockForUpdate()->find($productoId);
                    $precioOficial = (float) $producto->precio_venta; // No confiar en precio_unitario enviado por cliente

                    $total += ($cantidad * $precioOficial);
                    $itemsProcesados[] = [
                        'producto' => $producto,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioOficial,
                    ];
                }

                // 3. Crear cabecera de la venta
                $nuevaVenta = Venta::create([
                    'fecha'       => now(),
                    'total'       => $total,
                    'cliente_id'  => $clienteId ?: null,
                    'metodo_pago' => $request->metodo_pago,
                    'estado'      => 'Completada',
                    'usuario_id'  => $user->id,
                ]);

                // 4. Crear detalles, descontar inventario y registrar en Kardex
                foreach ($itemsProcesados as $itemData) {
                    $producto = $itemData['producto'];
                    $cantidad = $itemData['cantidad'];
                    $precioUnit = $itemData['precio_unitario'];

                    DetalleVenta::create([
                        'venta_id'        => $nuevaVenta->id,
                        'producto_id'     => $producto->id,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precioUnit,
                    ]);

                    $nuevoStock = $producto->stock - $cantidad;
                    $producto->update(['stock' => $nuevoStock]);

                    // NOTIFICACIÓN: Stock crítico a administradores
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
                            // Silenciar fallo de notificación
                        }
                    }

                    // Registrar movimiento de Salida en Kardex
                    Inventario::registrarMovimiento($producto->id, 'Salida', $nuevoStock, $cantidad);
                }

                // 5. Registrar en caja activa ÚNICAMENTE si el método de pago es Efectivo
                if ($request->metodo_pago === 'Efectivo') {
                    $caja = DB::table('cajas')->where('estado', 'Abierta')->first();
                    if ($caja) {
                        DB::table('movimientos_caja')->insert([
                            'caja_id'    => $caja->id,
                            'tipo'       => 'Ingreso',
                            'monto'      => $total,
                            'concepto'   => "Venta #{$nuevaVenta->id} (Efectivo)",
                            'fecha'      => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('cajas')
                            ->where('id', $caja->id)
                            ->update([
                                'total_ingresos' => DB::raw("COALESCE(total_ingresos, 0) + {$total}"),
                                'updated_at'     => now(),
                            ]);
                    }
                }

                return $nuevaVenta;
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente.',
                'venta'   => $venta->load(['cliente', 'detalles.producto']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Búsqueda rápida de clientes para el POS móvil.
     * GET /api/ventas/buscar-cliente
     */
    public function buscarCliente(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $clientes = User::where('rol_id', 3)
            ->where('estado', 'Activo')
            ->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellido', 'like', "%{$q}%")
                      ->orWhere('documento', 'like', "%{$q}%")
                      ->orWhere('username', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%");
            })
            ->select('id', 'nombre', 'apellido', 'documento', 'username', 'telefono', 'email')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $clientes,
        ]);
    }

    /**
     * Datos estructurados del comprobante / ticket de venta para Flutter.
     * GET /api/ventas/{id}/comprobante
     */
    public function comprobante(Request $request, $id)
    {
        $user = $request->user();
        $venta = Venta::with(['cliente', 'usuario', 'detalles.producto.categoria'])->findOrFail($id);

        if ((int)$user->rol_id === 3 && (int)$venta->cliente_id !== (int)$user->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $ticket = [
            'empresa' => [
                'nombre'    => "It's Fashion Boutique",
                'nit'       => '900.123.456-7',
                'direccion' => 'Calle Principal #123, Bogotá, Colombia',
                'telefono'  => '+57 300 123 4567',
                'email'     => 'info@itsfashion.com',
            ],
            'venta' => [
                'id'             => $venta->id,
                'numero_factura' => 'FAC-' . str_pad($venta->id, 6, '0', STR_PAD_LEFT),
                'fecha'          => $venta->fecha,
                'metodo_pago'    => $venta->metodo_pago,
                'estado'         => $venta->estado,
                'total'          => (float)$venta->total,
                'vendedor'       => $venta->usuario ? "{$venta->usuario->nombre} {$venta->usuario->apellido}" : 'Venta en línea',
            ],
            'cliente' => $venta->cliente ? [
                'nombre'    => "{$venta->cliente->nombre} {$venta->cliente->apellido}",
                'documento' => $venta->cliente->documento ?? 'Consumidor Final',
                'telefono'  => $venta->cliente->telefono ?? 'N/A',
                'email'     => $venta->cliente->email ?? 'N/A',
            ] : [
                'nombre'    => 'Cliente General / Consumidor Final',
                'documento' => '222222222222',
            ],
            'items' => $venta->detalles->map(function ($det) {
                return [
                    'producto_id'     => $det->producto_id,
                    'codigo'          => $det->producto?->codigo ?? 'N/A',
                    'nombre'          => $det->producto?->nombre ?? 'Producto',
                    'talla'           => $det->producto?->talla ?? '',
                    'color'           => $det->producto?->color ?? '',
                    'cantidad'        => (int)$det->cantidad,
                    'precio_unitario' => (float)$det->precio_unitario,
                    'subtotal'        => (float)$det->subtotal,
                ];
            }),
            'url_comprobante_web' => url("/ventas/{$venta->id}/comprobante"),
        ];

        return response()->json([
            'success' => true,
            'ticket'  => $ticket,
        ]);
    }
}
