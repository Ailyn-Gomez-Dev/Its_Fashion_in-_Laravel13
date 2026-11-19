<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteApiController extends Controller
{
    /**
     * Resumen de métricas de ventas y devoluciones por rango de fechas.
     * GET /api/reportes/resumen
     */
    public function resumen(Request $request)
    {
        $desde = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->get('hasta', now()->endOfMonth()->format('Y-m-d'));

        $ventasQuery = Venta::whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta);

        $totalVentas = (clone $ventasQuery)->count();
        $totalRecaudado = (clone $ventasQuery)->sum('total');
        $promedioVenta = $totalVentas > 0 ? round($totalRecaudado / $totalVentas, 2) : 0;

        $devolucionesQuery = Devolucion::whereDate('fecha_solicitud', '>=', $desde)
            ->whereDate('fecha_solicitud', '<=', $hasta);

        $totalDevoluciones = (clone $devolucionesQuery)->count();
        $totalDevuelto = (clone $devolucionesQuery)->where('estado', 'Aprobada')->sum('monto_devolucion');

        return response()->json([
            'success' => true,
            'rango'   => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'kpis'    => [
                'total_ventas'       => $totalVentas,
                'total_recaudado'    => round($totalRecaudado, 2),
                'ticket_promedio'    => $promedioVenta,
                'total_devoluciones' => $totalDevoluciones,
                'monto_devuelto'     => round($totalDevuelto, 2),
            ],
        ]);
    }

    /**
     * Desglose de ventas por método de pago.
     * GET /api/reportes/metodos-pago
     */
    public function metodosPago(Request $request)
    {
        $desde = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->get('hasta', now()->endOfMonth()->format('Y-m-d'));

        $desglose = Venta::whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->select('metodo_pago', DB::raw('count(*) as cantidad'), DB::raw('sum(total) as total'))
            ->groupBy('metodo_pago')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $desglose,
        ]);
    }

    /**
     * Top prendas más vendidas.
     * GET /api/reportes/top-productos
     */
    public function topProductos(Request $request)
    {
        $limite = $request->get('limit', 10);

        $top = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'), DB::raw('SUM(subtotal) as total_ingresos'))
            ->with('producto:id,codigo,nombre,precio,imagen')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->take($limite)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $top,
        ]);
    }
}
