<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Devolucion;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    /**
     * Retorna los KPIs y métricas del dashboard según el rol del usuario autenticado.
     * GET /api/dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Dashboard de Cliente (rol_id = 3)
        if ((int)$user->rol_id === 3) {
            $misVentas = Venta::with('detalles.producto')
                ->where('cliente_id', $user->id)
                ->where('estado', 'Completada')
                ->latest('fecha')
                ->take(5)
                ->get();

            $totalCompras = Venta::where('cliente_id', $user->id)
                ->where('estado', 'Completada')
                ->count();

            $totalGastado = (float) Venta::where('cliente_id', $user->id)
                ->where('estado', 'Completada')
                ->sum('total');

            $misDevoluciones = Devolucion::whereHas('venta', fn($q) => $q->where('cliente_id', $user->id))
                ->latest('fecha')
                ->take(5)
                ->get();

            $totalDevoluciones = Devolucion::whereHas('venta', fn($q) => $q->where('cliente_id', $user->id))
                ->count();

            return response()->json([
                'success' => true,
                'rol'     => 'cliente',
                'kpis'    => [
                    'total_compras'      => $totalCompras,
                    'total_gastado'      => $totalGastado,
                    'total_devoluciones' => $totalDevoluciones,
                ],
                'ultimas_compras'      => $misVentas,
                'ultimas_devoluciones' => $misDevoluciones,
            ]);
        }

        // 2. Dashboard de Vendedor / Empleado (rol_id = 2)
        if ((int)$user->rol_id === 2) {
            $ventasHoy = (float) Venta::whereDate('fecha', today())->where('estado', 'Completada')->sum('total');
            $ventasHoyCount = Venta::whereDate('fecha', today())->where('estado', 'Completada')->count();
            $ingresosMes = (float) Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->sum('total');
            $ingresosMesCount = Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->count();
            $devolucionesPendientes = Devolucion::where('estado', 'Pendiente')->count();
            $productosActivos = Producto::where('estado', 'Activo')->count();
            $productosCriticos = Producto::where('estado', 'Activo')->whereColumn('stock', '<=', 'stock_minimo')->count();

            $ultimasVentas = Venta::with(['cliente', 'detalles.producto'])
                ->latest('fecha')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'rol'     => 'empleado',
                'kpis'    => [
                    'ventas_hoy_monto'       => $ventasHoy,
                    'ventas_hoy_cantidad'    => $ventasHoyCount,
                    'ingresos_mes_monto'     => $ingresosMes,
                    'ingresos_mes_cantidad'  => $ingresosMesCount,
                    'devoluciones_pendientes'=> $devolucionesPendientes,
                    'productos_activos'      => $productosActivos,
                    'productos_criticos'     => $productosCriticos,
                ],
                'ultimas_ventas' => $ultimasVentas,
            ]);
        }

        // 3. Dashboard de Administrador (rol_id = 1)
        $totalClientes = User::where('rol_id', 3)->count();
        $clientesActivos = User::where('rol_id', 3)->where('estado', 'Activo')->count();
        $totalProductos = Producto::count();
        $productosActivos = Producto::where('estado', 'Activo')->count();
        $productosCriticos = Producto::where('estado', 'Activo')->whereColumn('stock', '<=', 'stock_minimo')->count();
        $ventasHoy = (float) Venta::whereDate('fecha', today())->where('estado', 'Completada')->sum('total');
        $ventasHoyCount = Venta::whereDate('fecha', today())->where('estado', 'Completada')->count();
        $ingresosMes = (float) Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->sum('total');
        $ingresosMesCount = Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->count();
        $devolucionesPendientes = Devolucion::where('estado', 'Pendiente')->count();

        $ultimasVentas = Venta::with(['cliente', 'usuario', 'detalles.producto'])
            ->latest('fecha')
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'rol'     => 'admin',
            'kpis'    => [
                'total_clientes'         => $totalClientes,
                'clientes_activos'       => $clientesActivos,
                'total_productos'        => $totalProductos,
                'productos_activos'      => $productosActivos,
                'productos_criticos'     => $productosCriticos,
                'ventas_hoy_monto'       => $ventasHoy,
                'ventas_hoy_cantidad'    => $ventasHoyCount,
                'ingresos_mes_monto'     => $ingresosMes,
                'ingresos_mes_cantidad'  => $ingresosMesCount,
                'devoluciones_pendientes'=> $devolucionesPendientes,
            ],
            'ultimas_ventas' => $ultimasVentas,
        ]);
    }
}
