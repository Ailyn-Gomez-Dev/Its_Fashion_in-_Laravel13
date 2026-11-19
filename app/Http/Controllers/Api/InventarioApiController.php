<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Http\Request;

class InventarioApiController extends Controller
{
    /**
     * Obtener el historial de movimientos de inventario (Kardex).
     * GET /api/inventario
     */
    public function index(Request $request)
    {
        $query = Inventario::with('producto:id,codigo,nombre,imagen')
            ->orderBy('fecha_registro', 'desc');

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        $movimientos = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $movimientos,
        ]);
    }

    /**
     * Obtener métricas y KPIs de estado de inventario.
     * GET /api/inventario/kpis
     */
    public function kpis()
    {
        $productos = Producto::all();

        $total = $productos->count();
        $conStock = $productos->filter(fn($p) => (int)$p->stock > (int)($p->stock_minimo ?? 0))->count();
        $critico = $productos->filter(fn($p) => (int)$p->stock > 0 && (int)$p->stock <= (int)($p->stock_minimo ?? 0))->count();
        $sinStock = $productos->filter(fn($p) => (int)$p->stock == 0)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_productos' => $total,
                'con_stock'       => $conStock,
                'stock_critico'   => $critico,
                'sin_stock'       => $sinStock,
            ],
        ]);
    }
}
