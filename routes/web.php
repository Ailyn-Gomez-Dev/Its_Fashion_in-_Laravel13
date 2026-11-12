<?php

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ReporteController;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Devolucion;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use Illuminate\Support\Facades\DB;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    /** @var User $user */
    $user = Auth::user();

    return match ((int) $user->rol_id) {
        1 => (function() {
            $ultimos7Dias = [];
            $ventas7Dias = [];
            for ($i = 6; $i >= 0; $i--) {
                $dia = today()->subDays($i);
                $ultimos7Dias[] = $dia->format('d M');
                $ventas7Dias[] = (float) Venta::whereDate('fecha', $dia)
                    ->where('estado', 'Completada')
                    ->sum('total');
            }

            $categorias = Categoria::withSum('productos', 'stock')->get();
            $categoriasLabels = $categorias->pluck('nombre')->toArray();
            $categoriasStock = $categorias->map(fn($c) => (int)($c->productos_sum_stock ?? 0))->toArray();

            $metodosPago = [
                'Efectivo' => (float) Venta::where('metodo_pago', 'Efectivo')->where('estado', 'Completada')->sum('total'),
                'Transferencia' => (float) Venta::where('metodo_pago', 'Transferencia bancaria')->where('estado', 'Completada')->sum('total'),
            ];

            $stockNormal = Producto::where('estado', 'Activo')->whereColumn('stock', '>', 'stock_minimo')->count();
            $stockCritico = Producto::where('estado', 'Activo')->whereColumn('stock', '<=', 'stock_minimo')->where('stock', '>', 0)->count();
            $stockAgotado = Producto::where('estado', 'Activo')->where('stock', '<=', 0)->count();

            $topProductos = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'Completada')
                ->groupBy('producto_id')
                ->orderByDesc('total_vendido')
                ->with('producto')
                ->take(5)
                ->get();

            return view('admin.dashboard', [
                'totalClientes'          => User::where('rol_id', 3)->count(),
                'clientesActivos'        => User::where('rol_id', 3)->where('estado', 'Activo')->count(),
                'totalProductos'         => Producto::count(),
                'productosActivos'       => Producto::where('estado', 'Activo')->count(),
                'ventasHoy'              => Venta::whereDate('fecha', today())->where('estado', 'Completada')->sum('total'),
                'ventasHoyCount'         => Venta::whereDate('fecha', today())->where('estado', 'Completada')->count(),
                'ingresosMes'            => Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->sum('total'),
                'ingresosMesCount'       => Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->count(),
                'devolucionesPendientes' => Devolucion::where('estado', 'Pendiente')->count(),
                'chartDias'              => $ultimos7Dias,
                'chartVentas'            => $ventas7Dias,
                'chartCategoriasLabels'  => $categoriasLabels,
                'chartCategoriasStock'   => $categoriasStock,
                'chartMetodos'           => $metodosPago,
                'stockSalud'             => [
                    'normal'  => $stockNormal,
                    'critico' => $stockCritico,
                    'agotado' => $stockAgotado,
                ],
                'topProductos'           => $topProductos,
            ]);
        })(),
        2 => view('empleado.dashboard', [
            'ventasHoy'              => Venta::whereDate('fecha', today())->where('estado', 'Completada')->sum('total'),
            'ventasHoyCount'         => Venta::whereDate('fecha', today())->where('estado', 'Completada')->count(),
            'ingresosMes'            => Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->sum('total'),
            'ingresosMesCount'       => Venta::whereMonth('fecha', today()->month)->whereYear('fecha', today()->year)->where('estado', 'Completada')->count(),
            'devolucionesPendientes' => Devolucion::where('estado', 'Pendiente')->count(),
            'productosActivos'       => Producto::where('estado', 'Activo')->count(),
            'productosCriticos'      => Producto::where('estado', 'Activo')->whereColumn('stock', '<=', 'stock_minimo')->count(),
        ]),
        3 => view('cliente.dashboard', [
            'misVentas'          => Venta::where('cliente_id', $user->id)->where('estado', 'Completada')->latest('fecha')->take(5)->get(),
            'totalCompras'       => Venta::where('cliente_id', $user->id)->where('estado', 'Completada')->count(),
            'totalGastado'       => Venta::where('cliente_id', $user->id)->where('estado', 'Completada')->sum('total'),
            'misDevoluciones'    => Devolucion::whereHas('venta', fn($q) => $q->where('cliente_id', $user->id))->latest('fecha')->take(5)->get(),
            'totalDevoluciones'  => Devolucion::whereHas('venta', fn($q) => $q->where('cliente_id', $user->id))->count(),
        ]),
        default => abort(403),
    };
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// =========================================================================
// Rutas exclusivas para el Administrador (rol_id = 1)
// =========================================================================
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Gestión de usuarios y roles internos
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/toggle-estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggle-estado');

    // Informes financieros y exportación
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar/pdf', [ReporteController::class, 'exportarPdf'])->name('reportes.exportar.pdf');
    Route::get('/reportes/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('reportes.exportar.excel');

    // Resolución de devoluciones (solo Admin)
    Route::post('/devoluciones/{devolucion}/aprobar', [DevolucionController::class, 'aprobar'])->name('devoluciones.aprobar');
    Route::post('/devoluciones/{devolucion}/rechazar', [DevolucionController::class, 'rechazar'])->name('devoluciones.rechazar');
});

// =========================================================================
// Rutas operativas para Administrador y Empleado/Vendedor (rol_id = 1 y 2)
// =========================================================================
Route::middleware(['auth', 'role:admin,vendedor'])->group(function () {
    Route::resource('categorias', CategoriaController::class)->except(['create', 'show', 'edit']);
    Route::resource('productos', ProductoController::class)->except(['create', 'edit']);
    Route::patch('/productos/{producto}/toggle-estado', [ProductoController::class, 'toggleEstado'])->name('productos.toggle-estado');

    Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->except(['create', 'edit']);
    Route::resource('compras', CompraController::class)->only(['index', 'store', 'show']);

    Route::get('/ventas/buscar-cliente', [VentaController::class, 'buscarCliente'])->name('ventas.buscar-cliente');

    // Caja y Arqueo
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::post('/caja/movimiento', [CajaController::class, 'registrarMovimiento'])->name('caja.movimiento');

    // Inventario y Kardex
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');

    // Directorio de Clientes
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::patch('/clientes/{cliente}/toggle-estado', [ClienteController::class, 'toggleEstado'])->name('clientes.toggle-estado');
});

// =========================================================================
// Rutas compartidas (Admin, Empleado y Cliente según su rol)
// =========================================================================
Route::middleware('auth')->group(function () {
    Route::resource('ventas', VentaController::class)->only(['index', 'store', 'show']);
    Route::get('/ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])->name('ventas.comprobante');
    Route::resource('devoluciones', DevolucionController::class)->parameters(['devoluciones' => 'devolucion'])->only(['index', 'store', 'show']);

    Route::get('/clientes/{cliente}/compras', [ClienteController::class, 'compras'])->name('clientes.compras');
    Route::get('/clientes/{cliente}/devoluciones', [ClienteController::class, 'devoluciones'])->name('clientes.devoluciones');

    Route::get('/notificaciones', function() {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user instanceof \App\Models\User) {
            return response()->json($user->unreadNotifications);
        }
        return response()->json([]);
    })->name('notificaciones.index');

    Route::post('/notificaciones/leer-todas', function() {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user instanceof \App\Models\User) {
            $user->unreadNotifications->markAsRead();
        }
        return response()->json(['ok' => true]);
    })->name('notificaciones.readAll');
});









