<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraApiController extends Controller
{
    /**
     * Listar compras con proveedor y usuario.
     * GET /api/compras
     */
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor:id,nombre,contacto,telefono', 'usuario:id,nombre,apellido,username'])
            ->orderBy('fecha', 'desc');

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        $compras = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $compras,
            'kpis'    => [
                'total_compras' => Compra::count(),
                'monto_total'   => Compra::sum('total'),
                'compras_hoy'   => Compra::whereDate('fecha', today())->count(),
            ],
        ]);
    }

    /**
     * Registrar una nueva compra de mercancía (abastecimiento).
     * POST /api/compras
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'items'        => ['required', 'array', 'min:1'],
            'items.*.producto_id'    => ['required', 'exists:productos,id'],
            'items.*.cantidad'       => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario'=> ['required', 'numeric', 'min:0.01'],
        ], [
            'proveedor_id.required' => 'Debes seleccionar un proveedor.',
            'proveedor_id.exists'   => 'El proveedor seleccionado no existe.',
            'items.required'        => 'Debes incluir al menos un producto a la compra.',
        ]);

        try {
            $compra = DB::transaction(function () use ($validated) {
                $total = 0;
                foreach ($validated['items'] as $item) {
                    $total += $item['cantidad'] * $item['precio_unitario'];
                }

                $nuevaCompra = Compra::create([
                    'fecha'        => now(),
                    'total'        => round($total, 2),
                    'proveedor_id' => $validated['proveedor_id'],
                    'usuario_id'   => Auth::id() ?? 1,
                ]);

                foreach ($validated['items'] as $item) {
                    $productoId = (int)$item['producto_id'];
                    $cantidad = (int)$item['cantidad'];
                    $precioUnitario = (float)$item['precio_unitario'];
                    $subtotal = round($cantidad * $precioUnitario, 2);

                    $producto = Producto::lockForUpdate()->findOrFail($productoId);

                    DetalleCompra::create([
                        'compra_id'       => $nuevaCompra->id,
                        'producto_id'     => $producto->id,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal'        => $subtotal,
                    ]);

                    // Incrementar stock
                    $nuevoStock = (int)$producto->stock + $cantidad;
                    $producto->update([
                        'stock'         => $nuevoStock,
                        'precio_compra' => $precioUnitario, // Actualizar último costo de adquisición
                    ]);

                    // Registrar en Kardex
                    Inventario::registrarMovimiento($producto->id, 'Entrada', $nuevoStock, $cantidad);
                }

                return $nuevaCompra->load(['proveedor', 'detalles.producto']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Compra registrada y stock actualizado exitosamente.',
                'data'    => $compra,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver detalle de una compra específica.
     * GET /api/compras/{id}
     */
    public function show($id)
    {
        $compra = Compra::with([
            'proveedor',
            'usuario:id,nombre,apellido,username',
            'detalles.producto:id,codigo,nombre,imagen'
        ])->find($id);

        if (!$compra) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $compra,
        ]);
    }
}
