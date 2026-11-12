<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Devolucion;
use App\Models\DetalleDevolucion;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolucionApiController extends Controller
{
    /**
     * Listado de devoluciones.
     * GET /api/devoluciones
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Devolucion::with(['venta.cliente', 'usuario', 'detalles.producto'])
            ->orderBy('fecha', 'desc');

        // Si es cliente, sólo sus devoluciones
        if ((int)$user->rol_id === 3) {
            $query->whereHas('venta', fn($q) => $q->where('cliente_id', $user->id));
        } elseif ($request->has('estado') && !empty($request->estado)) {
            $query->where('estado', $request->estado);
        }

        $devoluciones = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $devoluciones,
        ]);
    }

    /**
     * Solicitar o registrar una devolución.
     * POST /api/devoluciones
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'venta_id' => ['required', 'exists:ventas,id'],
            'motivo'   => ['required', 'string', 'max:500'],
            'items'    => ['required'], // Array de [{producto_id, cantidad, precio_unitario}]
        ], [
            'venta_id.required' => 'La venta asociada es obligatoria.',
            'motivo.required'   => 'Describe el motivo de la devolución.',
        ]);

        $venta = Venta::with('detalles')->findOrFail($request->venta_id);

        // Si es cliente, validar que la venta sea suya
        if ((int)$user->rol_id === 3 && (int)$venta->cliente_id !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes solicitar devoluciones sobre compras ajenas.',
            ], 403);
        }

        $rawItems = $request->input('items');
        $items = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;

        if (empty($items) || !is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar los productos a devolver.',
            ], 422);
        }

        try {
            $devolucion = DB::transaction(function () use ($request, $items, $venta, $user) {
                // Obtener detalles originales de la venta
                $detallesVenta = DetalleVenta::where('venta_id', $venta->id)
                    ->get()
                    ->keyBy('producto_id');

                $totalDevolucion = 0;
                $itemsValidados = [];

                foreach ($items as $item) {
                    $productoId = (int)($item['producto_id'] ?? $item['id'] ?? 0);
                    $cantidad   = (int)($item['cantidad'] ?? 1);

                    if (!$detallesVenta->has($productoId)) {
                        throw new \Exception("El producto ID #{$productoId} no pertenece a la venta original.");
                    }

                    $detalleOriginal = $detallesVenta->get($productoId);

                    // Calcular cantidad ya devuelta o en trámite para este producto en esta venta
                    $yaDevuelto = DetalleDevolucion::whereHas('devolucion', function ($q) use ($venta) {
                        $q->where('venta_id', $venta->id)->whereIn('estado', ['Pendiente', 'Aceptada']);
                    })->where('producto_id', $productoId)->sum('cantidad');

                    $disponible = (int)$detalleOriginal->cantidad - (int)$yaDevuelto;
                    if ($cantidad > $disponible) {
                        throw new \Exception("La cantidad a devolver ({$cantidad}) supera las unidades disponibles para devolución ({$disponible}) del producto seleccionado.");
                    }

                    $precioOriginal = (float) $detalleOriginal->precio_unitario;
                    $totalDevolucion += ($cantidad * $precioOriginal);

                    $itemsValidados[] = [
                        'producto_id'     => $productoId,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precioOriginal,
                    ];
                }

                $dev = Devolucion::create([
                    'fecha'            => now(),
                    'motivo'           => trim($request->motivo),
                    'estado'           => 'Pendiente',
                    'total_devolucion' => $totalDevolucion,
                    'venta_id'         => $venta->id,
                    'usuario_id'       => $user->id,
                ]);

                foreach ($itemsValidados as $itemVal) {
                    DetalleDevolucion::create([
                        'devolucion_id'   => $dev->id,
                        'producto_id'     => $itemVal['producto_id'],
                        'cantidad'        => $itemVal['cantidad'],
                        'precio_unitario' => $itemVal['precio_unitario'],
                    ]);
                }

                return $dev;
            });

            // Notificar a los administradores
            try {
                $admins = \App\Models\User::where('rol_id', 1)->get();
                $nombreUser = trim($user->nombre . ' ' . $user->apellido);
                $noti = new \App\Notifications\SystemNotification(
                    'Nueva Solicitud de Devolución',
                    "{$nombreUser} ha solicitado la devolución de la venta #{$venta->id}.",
                    'devolucion',
                    url('/devoluciones')
                );
                foreach ($admins as $admin) {
                    $admin->notify($noti);
                }
            } catch (\Exception $ex) {
                // Silenciar fallos de notificación
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Solicitud de devolución registrada en estado Pendiente.',
                'devolucion' => $devolucion->load('detalles.producto'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Aprobar devolución y reintegrar productos al stock.
     * POST /api/devoluciones/{id}/aprobar
     */
    public function aprobar(Request $request, $id)
    {
        $user = $request->user();
        if ((int)$user->rol_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Solo el Administrador está autorizado para aprobar devoluciones.'], 403);
        }

        $devolucion = Devolucion::with(['detalles.producto', 'venta'])->findOrFail($id);

        if ($devolucion->estado !== 'Pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Esta devolución ya fue procesada anteriormente.',
            ], 422);
        }

        DB::transaction(function () use ($devolucion, $user) {
            $devolucion->update([
                'estado'           => 'Aceptada',
                'fecha_resolucion' => now(),
                'admin_id'         => $user->id,
            ]);

            // Reintegrar stock y registrar Entrada en Kardex
            foreach ($devolucion->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                if ($producto) {
                    $nuevoStock = $producto->stock + $detalle->cantidad;
                    $producto->update(['stock' => $nuevoStock]);

                    Inventario::registrarMovimiento(
                        $producto->id,
                        'Entrada',
                        $nuevoStock,
                        $detalle->cantidad
                    );
                }
            }

            // Registrar egreso en caja física ÚNICAMENTE si la venta original fue pagada en Efectivo
            $esPagoEfectivo = ($devolucion->venta?->metodo_pago === 'Efectivo');
            if ($esPagoEfectivo) {
                $caja = DB::table('cajas')->where('estado', 'Abierta')->first();
                if ($caja) {
                    $concepto = "Devolución #{$devolucion->id} (Venta #{$devolucion->venta_id})";

                    DB::table('movimientos_caja')->insert([
                        'caja_id'    => $caja->id,
                        'tipo'       => 'Egreso',
                        'monto'      => $devolucion->total_devolucion,
                        'concepto'   => $concepto,
                        'fecha'      => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('cajas')
                        ->where('id', $caja->id)
                        ->update([
                            'total_egresos' => DB::raw("COALESCE(total_egresos, 0) + {$devolucion->total_devolucion}"),
                            'updated_at'    => now(),
                        ]);
                }
            }
        });

        // Notificar al cliente solicitante
        try {
            $cliente = \App\Models\User::find($devolucion->venta?->cliente_id ?? $devolucion->usuario_id);
            if ($cliente) {
                $cliente->notify(new \App\Notifications\SystemNotification(
                    'Devolución Aprobada',
                    "Tu solicitud de devolución para la venta #{$devolucion->venta_id} ha sido aprobada.",
                    'devolucion',
                    url('/devoluciones')
                ));
            }
        } catch (\Exception $ex) {
            // Silenciar fallos de notificación
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Devolución aprobada y stock reintegrado con éxito.',
            'devolucion' => $devolucion->fresh(['detalles.producto']),
        ]);
    }

    /**
     * Rechazar devolución.
     * POST /api/devoluciones/{id}/rechazar
     */
    public function rechazar(Request $request, $id)
    {
        $user = $request->user();
        if ((int)$user->rol_id !== 1) {
            return response()->json(['success' => false, 'message' => 'Solo el Administrador está autorizado para rechazar devoluciones.'], 403);
        }

        $devolucion = Devolucion::findOrFail($id);

        if ($devolucion->estado !== 'Pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Esta devolución ya fue procesada anteriormente.',
            ], 422);
        }

        $devolucion->update([
            'estado'           => 'Rechazada',
            'fecha_resolucion' => now(),
            'admin_id'         => $user->id,
        ]);

        // Notificar al cliente solicitante
        try {
            $cliente = \App\Models\User::find($devolucion->venta?->cliente_id ?? $devolucion->usuario_id);
            if ($cliente) {
                $cliente->notify(new \App\Notifications\SystemNotification(
                    'Devolución Rechazada',
                    "Tu solicitud de devolución para la venta #{$devolucion->venta_id} ha sido rechazada.",
                    'devolucion',
                    url('/devoluciones')
                ));
            }
        } catch (\Exception $ex) {
            // Silenciar fallos de notificación
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Devolución rechazada correctamente.',
            'devolucion' => $devolucion,
        ]);
    }
}
