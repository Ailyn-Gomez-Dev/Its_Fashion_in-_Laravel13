<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\DetalleDevolucion;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleSecurityAndStressTest extends TestCase
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

        // 1. Crear roles del sistema
        Rol::firstOrCreate(['id' => 1], ['descripcion' => 'admin']);
        Rol::firstOrCreate(['id' => 2], ['descripcion' => 'vendedor']);
        Rol::firstOrCreate(['id' => 3], ['descripcion' => 'cliente']);

        // 2. Crear usuarios para los 3 roles
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido' => 'Tester',
            'username' => 'admintester',
            'telefono' => '3000000001',
            'email' => 'admin@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 1,
            'estado' => 'Activo',
        ]);

        $this->vendedor = User::create([
            'nombre' => 'Vendedor',
            'apellido' => 'Tester',
            'username' => 'vendedortester',
            'telefono' => '3000000002',
            'email' => 'vendedor@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 2,
            'estado' => 'Activo',
        ]);

        $this->cliente1 = User::create([
            'nombre' => 'Cliente',
            'apellido' => 'Uno',
            'username' => 'clienteuno',
            'telefono' => '3000000003',
            'email' => 'cliente1@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 3,
            'estado' => 'Activo',
        ]);

        $this->cliente2 = User::create([
            'nombre' => 'Cliente',
            'apellido' => 'Dos',
            'username' => 'clientedos',
            'telefono' => '3000000004',
            'email' => 'cliente2@tester.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 3,
            'estado' => 'Activo',
        ]);

        // 3. Crear catálogo base
        $this->categoria = Categoria::create([
            'nombre' => 'Ropa Casual',
            'descripcion' => 'Categoría de prueba',
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Camisa Oxford',
            'descripcion' => 'Camisa manga larga',
            'precio_venta' => 60.00,
            'precio_compra' => 30.00,
            'stock' => 10,
            'stock_minimo' => 2,
            'talla' => 'M',
            'color' => 'Azul',
            'estado' => 'Activo',
            'categoria_id' => $this->categoria->id,
        ]);

        // 4. Crear turno de caja abierta
        $this->caja = Caja::create([
            'saldo_inicial' => 200.00,
            'total_ingresos' => 0.00,
            'total_egresos' => 0.00,
            'fecha_apertura' => now(),
            'estado' => 'Abierta',
            'usuario_id' => $this->admin->id,
        ]);
    }

    // =========================================================================
    // SUITE 1: PRUEBAS PARA ROL 1 (ADMINISTRADOR)
    // =========================================================================

    public function test_admin_can_access_user_management_and_create_employee(): void
    {
        $response = $this->actingAs($this->admin)->get(route('usuarios.index'));
        $response->assertOk();

        $createResponse = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'nombre' => 'Nuevo',
            'apellido' => 'Empleado',
            'username' => 'nuevoemp',
            'email' => 'nuevoemp@tester.com',
            'telefono' => '3009998888',
            'password' => 'PassWord123!',
            'estado' => 'Activo',
            'rol_id' => 2,
        ]);

        $createResponse->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['username' => 'nuevoemp', 'rol_id' => 2]);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->admin)->patchJson(route('usuarios.toggle-estado', $this->admin), [
            'estado' => 'Inactivo',
        ]);

        $response->assertStatus(403);
        $this->assertSame('Activo', $this->admin->fresh()->estado);
    }

    public function test_admin_cannot_deactivate_the_only_active_administrator(): void
    {
        $otherAdmin = User::create([
            'nombre' => 'Otro',
            'apellido' => 'Admin',
            'username' => 'otroadmin',
            'telefono' => '3001112233',
            'email' => 'otro@admin.com',
            'password' => Hash::make('Secret123!'),
            'rol_id' => 1,
            'estado' => 'Activo',
        ]);

        // Intentar desactivar al otro admin cuando quedan 2 es permitido
        $response1 = $this->actingAs($this->admin)->patchJson(route('usuarios.toggle-estado', $otherAdmin), [
            'estado' => 'Inactivo',
        ]);
        $response1->assertOk();
        $this->assertSame('Inactivo', $otherAdmin->fresh()->estado);

        // Ahora intentar desactivar al único admin activo restante desde otra sesión o intento debe ser bloqueado con 403 o 422
        $response2 = $this->actingAs($otherAdmin)->patchJson(route('usuarios.toggle-estado', $this->admin), [
            'estado' => 'Inactivo',
        ]);
        $response2->assertStatus(422);
    }

    public function test_admin_can_access_financial_reports_and_exports(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.index'));
        $response->assertOk();

        $excelResponse = $this->actingAs($this->admin)->get(route('reportes.exportar.excel'));
        $excelResponse->assertOk();
    }

    public function test_admin_can_approve_return_and_reintegrate_stock_and_egress_cash(): void
    {
        // 1. Crear una venta pagada en Efectivo
        $venta = Venta::create([
            'fecha' => now(),
            'total' => 120.00,
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Efectivo',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 60.00,
        ]);

        $this->producto->update(['stock' => 8]);

        // 2. Crear solicitud de devolución pendiente
        $devolucion = Devolucion::create([
            'fecha' => now(),
            'motivo' => 'Talla incorrecta',
            'estado' => 'Pendiente',
            'total_devolucion' => 120.00,
            'venta_id' => $venta->id,
            'usuario_id' => $this->cliente1->id,
        ]);

        DetalleDevolucion::create([
            'devolucion_id' => $devolucion->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 60.00,
        ]);

        // 3. Administrador aprueba la devolución
        $response = $this->actingAs($this->admin)->post(route('devoluciones.aprobar', $devolucion));
        $response->assertOk();

        // 4. Verificar resolución
        $this->assertSame('Aceptada', $devolucion->fresh()->estado);
        // Stock debe haber subido de 8 a 10
        $this->assertSame(10, $this->producto->fresh()->stock);

        // Movimiento de egreso en caja por devolución en efectivo
        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $this->caja->id,
            'tipo' => 'Egreso',
            'monto' => 120.00,
        ]);
    }

    // =========================================================================
    // SUITE 2: PRUEBAS PARA ROL 2 (VENDEDOR / EMPLEADO)
    // =========================================================================

    public function test_vendedor_can_access_pos_and_inventory_and_cash_drawer(): void
    {
        $this->actingAs($this->vendedor)->get(route('ventas.index'))->assertOk();
        $this->actingAs($this->vendedor)->get(route('inventario.index'))->assertOk();
        $this->actingAs($this->vendedor)->get(route('caja.index'))->assertOk();
    }

    public function test_vendedor_is_forbidden_from_user_management(): void
    {
        $response = $this->actingAs($this->vendedor)->get(route('usuarios.index'));
        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->vendedor)->post(route('usuarios.store'), [
            'nombre' => 'Hacker',
            'apellido' => 'Test',
            'username' => 'hack',
            'email' => 'hack@hack.com',
            'telefono' => '1234567890',
            'password' => 'PassWord123!',
            'estado' => 'Activo',
            'rol_id' => 1,
        ]);
        $postResponse->assertStatus(403);
    }

    public function test_vendedor_is_forbidden_from_financial_reports(): void
    {
        $response = $this->actingAs($this->vendedor)->get(route('reportes.index'));
        $response->assertStatus(403);

        $pdfResponse = $this->actingAs($this->vendedor)->get(route('reportes.exportar.pdf'));
        $pdfResponse->assertStatus(403);
    }

    public function test_vendedor_is_forbidden_from_approving_or_rejecting_returns(): void
    {
        $venta = Venta::create([
            'fecha' => now(),
            'total' => 60.00,
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        $devolucion = Devolucion::create([
            'fecha' => now(),
            'motivo' => 'Defecto de fábrica',
            'estado' => 'Pendiente',
            'total_devolucion' => 60.00,
            'venta_id' => $venta->id,
            'usuario_id' => $this->cliente1->id,
        ]);

        $responseAprobar = $this->actingAs($this->vendedor)->post(route('devoluciones.aprobar', $devolucion));
        $responseAprobar->assertStatus(403);

        $responseRechazar = $this->actingAs($this->vendedor)->post(route('devoluciones.rechazar', $devolucion));
        $responseRechazar->assertStatus(403);

        $this->assertSame('Pendiente', $devolucion->fresh()->estado);
    }

    // =========================================================================
    // SUITE 3: PRUEBAS PARA ROL 3 (CLIENTE) - PERMISOS E IDOR
    // =========================================================================

    public function test_cliente_is_forbidden_from_all_administrative_routes(): void
    {
        $restrictedRoutes = [
            ['GET', route('usuarios.index')],
            ['GET', route('reportes.index')],
            ['GET', route('caja.index')],
            ['GET', route('inventario.index')],
            ['GET', route('proveedores.index')],
            ['GET', route('compras.index')],
            ['GET', route('clientes.index')],
        ];

        foreach ($restrictedRoutes as [$method, $url]) {
            $response = $this->actingAs($this->cliente1)->call($method, $url);
            $this->assertEquals(403, $response->getStatusCode(), "Cliente should be blocked with 403 on {$url}");
        }
    }

    public function test_cliente_cannot_execute_pos_cashier_sales(): void
    {
        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'nombre' => $this->producto->nombre,
                'cantidad' => 1,
                'precio_unitario' => 60.00,
            ]
        ]);

        $response = $this->actingAs($this->cliente1)->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Efectivo',
            'items' => $itemsPayload,
        ]);

        $response->assertStatus(403);
    }

    public function test_cliente_idor_protection_on_sales(): void
    {
        $ventaCliente1 = Venta::create([
            'fecha' => now(),
            'total' => 60.00,
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        $ventaCliente2 = Venta::create([
            'fecha' => now(),
            'total' => 120.00,
            'cliente_id' => $this->cliente2->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        // Cliente 1 puede consultar su propia venta
        $responsePropia = $this->actingAs($this->cliente1)->getJson(route('ventas.show', $ventaCliente1));
        $responsePropia->assertOk();

        // Cliente 1 intenta consultar la venta del Cliente 2 (IDOR)
        $responseAjena = $this->actingAs($this->cliente1)->getJson(route('ventas.show', $ventaCliente2));
        $responseAjena->assertStatus(403);
    }

    public function test_cliente_cannot_request_refund_on_another_customers_purchase(): void
    {
        $ventaCliente2 = Venta::create([
            'fecha' => now(),
            'total' => 60.00,
            'cliente_id' => $this->cliente2->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        DetalleVenta::create([
            'venta_id' => $ventaCliente2->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'precio_unitario' => 60.00,
        ]);

        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'cantidad' => 1,
            ]
        ]);

        // Cliente 1 intenta solicitar devolución de la compra del Cliente 2 (Fraud / IDOR)
        $response = $this->actingAs($this->cliente1)->postJson(route('devoluciones.store'), [
            'venta_id' => $ventaCliente2->id,
            'motivo' => 'Intento de devolución fraudulenta',
            'items' => $itemsPayload,
        ]);

        $response->assertStatus(403);
    }

    public function test_cliente_cannot_view_other_customers_purchase_history(): void
    {
        $response = $this->actingAs($this->cliente1)->getJson(route('clientes.compras', $this->cliente2));
        $response->assertStatus(403);
    }

    // =========================================================================
    // SUITE 4: PRUEBAS ADVERSARIALES Y STRESS TESTING
    // =========================================================================

    public function test_overselling_protection_under_insufficient_stock(): void
    {
        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'nombre' => $this->producto->nombre,
                'cantidad' => 50, // Stock disponible es 10
                'precio_unitario' => 60.00,
            ]
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Efectivo',
            'items' => $itemsPayload,
        ]);

        $response->assertStatus(500);
        $data = $response->json();
        $this->assertFalse($data['ok']);
        $this->assertStringContainsString('Stock insuficiente', $data['msg']);
        $this->assertSame(10, $this->producto->fresh()->stock);
    }

    public function test_price_tampering_is_neutralized_and_uses_catalog_prices(): void
    {
        // El atacante intenta enviar un precio alterado de $0.01 en vez de $60.00
        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'nombre' => $this->producto->nombre,
                'cantidad' => 2,
                'precio_unitario' => 0.01,
            ]
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Transferencia bancaria',
            'items' => $itemsPayload,
        ]);

        $response->assertOk();
        $idVenta = $response->json('id_venta');
        $venta = Venta::find($idVenta);

        // El total registrado debe ser 2 * 60 = 120.00 (precio oficial de catálogo), NO 0.02
        $this->assertEquals(120.00, (float)$venta->total);
    }

    public function test_double_refund_fraud_protection(): void
    {
        $venta = Venta::create([
            'fecha' => now(),
            'total' => 60.00,
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Transferencia bancaria',
            'estado' => 'Completada',
            'usuario_id' => $this->admin->id,
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'precio_unitario' => 60.00,
        ]);

        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'cantidad' => 1,
            ]
        ]);

        // Primera devolución exitosa
        $res1 = $this->actingAs($this->cliente1)->postJson(route('devoluciones.store'), [
            'venta_id' => $venta->id,
            'motivo' => 'Devolución legítima',
            'items' => $itemsPayload,
        ]);
        $res1->assertOk();

        // Segunda devolución para el mismo producto que ya está en trámite
        $res2 = $this->actingAs($this->cliente1)->postJson(route('devoluciones.store'), [
            'venta_id' => $venta->id,
            'motivo' => 'Segundo intento fraudulento',
            'items' => $itemsPayload,
        ]);
        $res2->assertStatus(500);
        $this->assertStringContainsString('supera las unidades disponibles', $res2->json('msg'));
    }

    public function test_cash_drawer_overdraft_protection(): void
    {
        // Saldo inicial es 200.00, intentar retirar 500.00 debe fallar con 422
        $response = $this->actingAs($this->vendedor)->postJson(route('caja.movimiento'), [
            'id_caja' => $this->caja->id,
            'tipo' => 'Egreso',
            'monto' => 500.00,
            'concepto' => 'Retiro no autorizado',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Fondos insuficientes', $response->json('msg'));
    }

    public function test_cash_sale_blocked_when_cash_drawer_is_closed(): void
    {
        // Cerrar la caja
        $this->caja->update(['estado' => 'Cerrada', 'fecha_cierre' => now()]);

        $itemsPayload = json_encode([
            [
                'id_producto' => $this->producto->id,
                'nombre' => $this->producto->nombre,
                'cantidad' => 1,
                'precio_unitario' => 60.00,
            ]
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(route('ventas.store'), [
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Efectivo',
            'items' => $itemsPayload,
        ]);

        $this->assertFalse($response->json('ok'));
        $this->assertStringContainsString('abrir la caja', $response->json('msg'));
    }

    public function test_empleado_sin_permiso_de_caja_recibe_403(): void
    {
        $this->vendedor->update(['puede_operar_caja' => false]);

        // Intentar ver el módulo de caja
        $resView = $this->actingAs($this->vendedor)->get(route('caja.index'));
        $resView->assertStatus(403);

        // Intentar registrar movimiento
        $resMov = $this->actingAs($this->vendedor)->postJson(route('caja.movimiento'), [
            'id_caja' => $this->caja->id,
            'tipo' => 'Ingreso',
            'monto' => 10.00,
            'concepto' => 'Intento sin permiso',
        ]);
        $resMov->assertStatus(403);
    }

    public function test_empleado_con_permiso_de_caja_accede_correctamente(): void
    {
        $this->vendedor->update(['puede_operar_caja' => true]);

        $resView = $this->actingAs($this->vendedor)->get(route('caja.index'));
        $resView->assertStatus(200);
    }

    public function test_registro_cliente_con_documento_identidad_unico(): void
    {
        $payload = [
            'nombre' => 'Cliente',
            'apellido' => 'Nuevo',
            'username' => 'clientenuevo99',
            'documento' => '1020304050',
            'email' => 'nuevo@cliente.com',
            'telefono' => '3119876543',
            'password' => 'Pass12345!',
            'password_confirmation' => 'Pass12345!',
        ];

        $response = $this->post(route('register'), $payload);
        $response->assertRedirect();

        $user = User::where('email', 'nuevo@cliente.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('1020304050', $user->documento);
        $this->assertEquals(3, $user->rol_id);
        $this->assertFalse((bool) $user->puede_operar_caja);

        // Intentar registrar otro con el mismo documento
        $payload2 = $payload;
        $payload2['username'] = 'clientenuevo100';
        $payload2['email'] = 'otro@cliente.com';

        $response2 = $this->post(route('register'), $payload2);
        $response2->assertSessionHasErrors(['documento']);
    }

    public function test_comprobante_de_venta_imprimible_es_accesible_con_datos(): void
    {
        $venta = Venta::create([
            'fecha' => now(),
            'total' => 120.00,
            'cliente_id' => $this->cliente1->id,
            'metodo_pago' => 'Efectivo',
            'estado' => 'Completada',
            'usuario_id' => $this->vendedor->id,
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 60.00,
        ]);

        // Admin puede ver el comprobante
        $resAdmin = $this->actingAs($this->admin)->get(route('ventas.comprobante', $venta->id));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('COMPROBANTE DE VENTA');
        $resAdmin->assertSee($this->producto->nombre);
        $resAdmin->assertSee('120.00');

        // Vendedor puede ver el comprobante
        $resVendedor = $this->actingAs($this->vendedor)->get(route('ventas.comprobante', $venta->id));
        $resVendedor->assertStatus(200);
        $resVendedor->assertSee('COMPROBANTE DE VENTA');
    }
}
