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

/*
|--------------------------------------------------------------------------
| Rutas API - It's Fashion Sistema Completo Móvil & Web
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
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. Dashboard y KPIs según rol (Admin, Empleado/Vendedor, Cliente)
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // 3. Ventas y Punto de Venta (POS) / Compras de Cliente
    Route::get('/ventas', [VentaApiController::class, 'index']);
    Route::post('/ventas', [VentaApiController::class, 'store']);
    Route::get('/ventas/{id}', [VentaApiController::class, 'show']);

    // 4. Gestión administrativa y operativa de Catálogo e Inventario (Admin y Vendedor)
    Route::middleware('role:admin,vendedor')->group(function () {
        Route::post('/products', [ProductoApiController::class, 'store']);
        Route::match(['PUT', 'POST', 'PATCH'], '/products/{id}', [ProductoApiController::class, 'update']);
        Route::match(['POST', 'PATCH'], '/products/{id}/toggle-estado', [ProductoApiController::class, 'toggleEstado']);
        Route::delete('/products/{id}', [ProductoApiController::class, 'destroy']);
        Route::post('/products/{id}/adjust-stock', [ProductoApiController::class, 'adjustStock']);
        Route::post('/categories', [ProductoApiController::class, 'storeCategoria']);

        // Turnos y Movimientos de Caja
        Route::get('/caja', [CajaApiController::class, 'index']);
        Route::post('/caja/abrir', [CajaApiController::class, 'abrir']);
        Route::post('/caja/cerrar', [CajaApiController::class, 'cerrar']);
        Route::post('/caja/movimiento', [CajaApiController::class, 'registrarMovimiento']);

        // Directorio de Clientes (consulta y registro desde POS móvil)
        Route::get('/clientes', [ClienteApiController::class, 'index']);
        Route::post('/clientes', [ClienteApiController::class, 'store']);
        Route::get('/clientes/{id}', [ClienteApiController::class, 'show']);
    });

    // 5. Operaciones de Cliente / Estado (Solo Administrador)
    Route::middleware('role:admin')->group(function () {
        Route::match(['POST', 'PATCH'], '/clientes/{id}/toggle-estado', [ClienteApiController::class, 'toggleEstado']);
        Route::post('/devoluciones/{id}/aprobar', [DevolucionApiController::class, 'aprobar']);
        Route::post('/devoluciones/{id}/rechazar', [DevolucionApiController::class, 'rechazar']);
    });

    // 6. Devoluciones (Consulta y Solicitud)
    Route::get('/devoluciones', [DevolucionApiController::class, 'index']);
    Route::post('/devoluciones', [DevolucionApiController::class, 'store']);

    // 7. Notificaciones
    Route::get('/notificaciones', [NotificacionApiController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionApiController::class, 'leerTodas']);
});