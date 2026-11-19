<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProductoApiController;
use App\Http\Controllers\Api\VentaApiController;
use App\Http\Controllers\Api\CajaApiController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\Api\DevolucionApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\ProveedorApiController;
use App\Http\Controllers\Api\CompraApiController;
use App\Http\Controllers\Api\UsuarioApiController;
use App\Http\Controllers\Api\InventarioApiController;
use App\Http\Controllers\Api\ReporteApiController;

/*
|--------------------------------------------------------------------------
| Rutas API - It's Fashion Sistema Completo 100% Móvil & Web
|--------------------------------------------------------------------------
*/

// 1. Health check de conectividad para Flutter
Route::get('/health-check', function () {
    return response()->json([
        'status'    => 'ok',
        'app'       => "It's Fashion - Laravel API",
        'timestamp' => now()->toIso8601String(),
    ]);
});

// 2. Autenticación pública
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 3. Catálogo público de Productos y Categorías (Consulta tienda móvil)
Route::get('/products', [ProductoApiController::class, 'index']);
Route::get('/products/{id}', [ProductoApiController::class, 'show']);
Route::get('/categories', [ProductoApiController::class, 'categories']);

// =========================================================================
// Rutas protegidas mediante token de Laravel Sanctum (Móvil Flutter)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // 1. Sesión y Perfil
    Route::get('/user', [AuthController::class, 'user']);
    Route::match(['PUT', 'POST', 'PATCH'], '/user', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. Dashboard y KPIs según rol (Admin, Empleado/Vendedor, Cliente)
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // 3. Ventas y Punto de Venta (POS) / Compras de Cliente
    Route::get('/ventas', [VentaApiController::class, 'index']);
    Route::post('/ventas', [VentaApiController::class, 'store']);
    Route::get('/ventas/buscar-cliente', [VentaApiController::class, 'buscarCliente']);
    Route::get('/ventas/{id}', [VentaApiController::class, 'show']);
    Route::get('/ventas/{id}/comprobante', [VentaApiController::class, 'comprobante']);

    // 4. Operaciones Operativas de Boutique (Admin y Vendedor)
    Route::middleware('role:admin,vendedor')->group(function () {
        // Productos y Categorías (CRUD completo)
        Route::post('/products', [ProductoApiController::class, 'store']);
        Route::match(['PUT', 'POST', 'PATCH'], '/products/{id}', [ProductoApiController::class, 'update']);
        Route::match(['POST', 'PATCH'], '/products/{id}/toggle-estado', [ProductoApiController::class, 'toggleEstado']);
        Route::delete('/products/{id}', [ProductoApiController::class, 'destroy']);
        Route::post('/products/{id}/adjust-stock', [ProductoApiController::class, 'adjustStock']);
        Route::post('/categories', [ProductoApiController::class, 'storeCategoria']);
        Route::match(['PUT', 'POST', 'PATCH'], '/categories/{id}', [ProductoApiController::class, 'updateCategoria']);
        Route::delete('/categories/{id}', [ProductoApiController::class, 'destroyCategoria']);

        // Turnos y Movimientos de Caja
        Route::get('/caja', [CajaApiController::class, 'index']);
        Route::post('/caja/abrir', [CajaApiController::class, 'abrir']);
        Route::post('/caja/cerrar', [CajaApiController::class, 'cerrar']);
        Route::post('/caja/movimiento', [CajaApiController::class, 'registrarMovimiento']);

        // Directorio de Clientes
        Route::get('/clientes', [ClienteApiController::class, 'index']);
        Route::post('/clientes', [ClienteApiController::class, 'store']);
        Route::get('/clientes/{id}', [ClienteApiController::class, 'show']);
        Route::get('/clientes/{id}/compras', [ClienteApiController::class, 'compras']);
        Route::get('/clientes/{id}/devoluciones', [ClienteApiController::class, 'devoluciones']);

        // Proveedores de Mercancía
        Route::get('/proveedores', [ProveedorApiController::class, 'index']);
        Route::post('/proveedores', [ProveedorApiController::class, 'store']);
        Route::get('/proveedores/{id}', [ProveedorApiController::class, 'show']);
        Route::match(['PUT', 'POST', 'PATCH'], '/proveedores/{id}', [ProveedorApiController::class, 'update']);
        Route::delete('/proveedores/{id}', [ProveedorApiController::class, 'destroy']);

        // Compras de Mercancía / Abastecimiento
        Route::get('/compras', [CompraApiController::class, 'index']);
        Route::post('/compras', [CompraApiController::class, 'store']);
        Route::get('/compras/{id}', [CompraApiController::class, 'show']);

        // Kardex y Movimientos de Inventario
        Route::get('/inventario', [InventarioApiController::class, 'index']);
        Route::get('/inventario/kpis', [InventarioApiController::class, 'kpis']);
    });

    // 5. Operaciones de Administración Avanzada (Solo Administrador)
    Route::middleware('role:admin')->group(function () {
        // Gestión de Clientes
        Route::match(['POST', 'PATCH'], '/clientes/{id}/toggle-estado', [ClienteApiController::class, 'toggleEstado']);

        // Aprobación / Rechazo de Devoluciones
        Route::post('/devoluciones/{id}/aprobar', [DevolucionApiController::class, 'aprobar']);
        Route::post('/devoluciones/{id}/rechazar', [DevolucionApiController::class, 'rechazar']);

        // Gestión de Empleados y Usuarios del Sistema
        Route::get('/usuarios', [UsuarioApiController::class, 'index']);
        Route::get('/roles', [UsuarioApiController::class, 'roles']);
        Route::post('/usuarios', [UsuarioApiController::class, 'store']);
        Route::get('/usuarios/{id}', [UsuarioApiController::class, 'show']);
        Route::match(['PUT', 'POST', 'PATCH'], '/usuarios/{id}', [UsuarioApiController::class, 'update']);
        Route::match(['POST', 'PATCH'], '/usuarios/{id}/toggle-estado', [UsuarioApiController::class, 'toggleEstado']);

        // Reportes y Analítica
        Route::get('/reportes/resumen', [ReporteApiController::class, 'resumen']);
        Route::get('/reportes/metodos-pago', [ReporteApiController::class, 'metodosPago']);
        Route::get('/reportes/top-productos', [ReporteApiController::class, 'topProductos']);
    });

    // 6. Devoluciones (Consulta y Solicitud)
    Route::get('/devoluciones', [DevolucionApiController::class, 'index']);
    Route::post('/devoluciones', [DevolucionApiController::class, 'store']);

    // 7. Notificaciones
    Route::get('/notificaciones', [NotificacionApiController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionApiController::class, 'leerTodas']);
});
