<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\DetalleDevolucion;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRoleSecurityAndStressTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendedor;
    protected User $cliente1;
    protected User $cliente2;
    protected Categoria $categoria;
    protected Producto $producto;
    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        Rol::firstOrCreate(['id' => 1], ['descripcion' => 'admin']);
        Rol::firstOrCreate(['id' => 2], ['descripcion' => 'vendedor']);
        Rol::firstOrCreate(['id' => 3], ['descripcion' => 'cliente']);

        // 2. Usuarios
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido' => 'Api',
            'username' => 'adminapi',
            'telefono' => '3110000001',
            'email' => 'adminapi@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 1,
            'estado' => 'Activo',
        ]);

        $this->vendedor = User::create([
            'nombre' => 'Vendedor',
            'apellido' => 'Api',
            'username' => 'vendedorapi',
            'telefono' => '3110000002',
            'email' => 'vendedorapi@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 2,
            'estado' => 'Activo',
        ]);

        $this->cliente1 = User::create([
            'nombre' => 'Cliente',
            'apellido' => 'UnoApi',
            'username' => 'cliente1api',
            'telefono' => '3110000003',
            'email' => 'cliente1api@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 3,
            'estado' => 'Activo',
        ]);

        $this->cliente2 = User::create([
            'nombre' => 'Cliente',
            'apellido' => 'DosApi',
            'username' => 'cliente2api',
            'telefono' => '3110000004',
            'email' => 'cliente2api@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 3,
            'estado' => 'Activo',
        ]);

        // 3. Catálogo
        $this->categoria = Categoria::create([
            'nombre' => 'Vestidos Elegantes',
            'descripcion' => 'Moda femenina',
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Vestido Seda Rojo',
            'descripcion' => 'Vestido de noche',
            'precio_venta' => 150.00,
            'precio_compra' => 80.00,
            'stock' => 15,
            'stock_minimo' => 3,
            'talla' => 'S',
            'color' => 'Rojo',
            'estado' => 'Activo',
            'categoria_id' => $this->categoria->id,
        ]);

        // 4. Caja abierta
        $this->caja = Caja::create([
            'saldo_inicial' => 500.00,
            'total_ingresos' => 0.00,
            'total_egresos' => 0.00,
            'fecha_apertura' => now(),
            'estado' => 'Abierta',
            'usuario_id' => $this->admin->id,
        ]);
    }

    // =========================================================================
    // API SUITE: SANCTUM ROLE GUARDS & IDOR
    // =========================================================================

    public function test_api_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_api_cliente_forbidden_from_creating_products_or_managing_cash(): void
    {
        Sanctum::actingAs($this->cliente1);

        // Crear producto -> bloqueado con 403
        $prodRes = $this->postJson('/api/products', [
            'nombre' => 'Producto Hacker',
            'precio_venta' => 200,
            'precio_compra' => 100,
            'stock' => 5,
            'categoria_id' => $this->categoria->id,
            'talla' => 'M',
            'color' => 'Negro',
        ]);
        $prodRes->assertStatus(403);

        // Manejar caja -> bloqueado con 403
        $cajaRes = $this->getJson('/api/caja');
        $cajaRes->assertStatus(403);

        $movRes = $this->postJson('/api/caja/movimiento', [
            'tipo' => 'Egreso',
            'monto' => 50,
            'concepto' => 'Intento retiro',
        ]);
        $movRes->assertStatus(403);
    }

    public function test_api_vendedor_can_manage_products_but_cannot_approve_refunds(): void
    {
        Sanctum::actingAs($this->vendedor);

        // Puede consultar caja
        $cajaRes = $this->getJson('/api/caja');
        $cajaRes->assertOk();

        // No puede aprobar devoluciones (solo rol_id 1)
        $devRes = $this->postJson('/api/devoluciones/1/aprobar');
        $devRes->assertStatus(403);
    }

    public function test_api_cliente_idor_protection_on_sales(): void
    {
        $ventaCliente2 = Venta::create([
            'fecha' => now(),
            'total' => 150.00,
            'cliente_id' => $this->cliente2->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->cliente1);

        // Intentar ver venta ajena por API -> 403
        $response = $this->getJson("/api/ventas/{$ventaCliente2->id}");
        $response->assertStatus(403);
    }

    public function test_api_cliente_sale_creation_forces_client_ownership(): void
    {
        Sanctum::actingAs($this->cliente1);

        // El cliente intenta forzar que la venta se guarde a nombre del cliente 2
        $response = $this->postJson('/api/ventas', [
            'cliente_id' => $this->cliente2->id,
            'metodo_pago' => 'Transferencia bancaria',
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio_unitario' => 0.01, // Intento de manipulación de precio
                ]
            ],
        ]);

        $response->assertStatus(201);
        $ventaData = $response->json('venta');

        // La venta DEBE haber sido asignada a cliente1 (su propio ID), no a cliente2
        $this->assertEquals($this->cliente1->id, $ventaData['cliente_id']);

        // El precio cobrado debe ser el oficial de catálogo: 150.00, NO 0.01
        $this->assertEquals(150.00, (float)$ventaData['total']);
    }

    public function test_api_overselling_protection(): void
    {
        Sanctum::actingAs($this->vendedor);

        // Intentar comprar 50 unidades cuando solo hay 15 en stock
        $response = $this->postJson('/api/ventas', [
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Transferencia bancaria',
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 50,
                ]
            ],
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Stock insuficiente', $response->json('message'));
        $this->assertEquals(15, $this->producto->fresh()->stock);
    }

    public function test_api_cash_drawer_overdraft_protection(): void
    {
        Sanctum::actingAs($this->vendedor);

        // Saldo inicial es 500.00, intentar egreso de 1000.00 debe ser bloqueado con 422
        $response = $this->postJson('/api/caja/movimiento', [
            'tipo' => 'Egreso',
            'monto' => 1000.00,
            'concepto' => 'Egreso excesivo',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Fondos insuficientes', $response->json('message'));
    }
}
