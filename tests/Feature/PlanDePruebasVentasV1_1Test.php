<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanDePruebasVentasV1_1Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendedor;
    protected User $cliente;
    protected Categoria $categoria;
    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Rol::firstOrCreate(['id' => 1], ['descripcion' => 'admin']);
        Rol::firstOrCreate(['id' => 2], ['descripcion' => 'vendedor']);
        Rol::firstOrCreate(['id' => 3], ['descripcion' => 'cliente']);

        // Usuarios
        $this->admin = User::create([
            'nombre' => 'Admin',
            'apellido' => 'General',
            'username' => 'admingeneral',
            'telefono' => '3001112233',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'rol_id' => 1,
            'estado' => 'Activo',
        ]);

        $this->vendedor = User::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'username' => 'juanperez',
            'telefono' => '3002223344',
            'email' => 'juan@gmail.com',
            'password' => Hash::make('password'),
            'rol_id' => 2,
            'estado' => 'Activo',
        ]);

        $this->cliente = User::create([
            'nombre' => 'Ailyn',
            'apellido' => 'Gomez',
            'documento' => '1076984253',
            'username' => 'ailyngomez',
            'telefono' => '3003334455',
            'email' => 'ailyngomez.2811@gmail.com',
            'password' => Hash::make('password'),
            'rol_id' => 3,
            'estado' => 'Activo',
        ]);

        $this->categoria = Categoria::create([
            'nombre' => 'Ropa Femenina',
            'descripcion' => 'Moda y accesorios',
        ]);

        $this->caja = Caja::create([
            'usuario_id' => $this->admin->id,
            'saldo_inicial' => 10000.00,
            'total_ingresos' => 0.00,
            'total_egresos' => 0.00,
            'estado' => 'Abierta',
            'fecha_apertura' => now(),
        ]);
    }

    /**
     * CP-VEN-01: Registrar venta en efectivo con cliente asociado
     */
    public function test_cp_ven_01_registrar_venta_en_efectivo_con_cliente(): void
    {
        $jeans = Producto::create([
            'nombre' => 'Jeans Slim Fit Azul',
            'descripcion' => 'Talla M, Color Azul Oscuro',
            'precio_venta' => 85000.00,
            'precio_compra' => 45000.00,
            'stock' => 49,
            'stock_minimo' => 5,
            'talla' => 'M',
            'color' => 'Azul Oscuro',
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $jeans->id, 'nombre' => $jeans->nombre, 'cantidad' => 2]
            ])
        ]);

        $response->assertOk()
            ->assertJson(['ok' => true, 'msg' => 'Venta registrada correctamente.']);

        $jeans->refresh();
        $this->assertEquals(47, $jeans->stock);

        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'total' => 170000.00,
            'estado' => 'Completada',
        ]);

        $this->caja->refresh();
        $this->assertEquals(170000.00, (float)$this->caja->total_ingresos);
    }

    /**
     * CP-VEN-02: Registrar venta con varios productos en el carrito
     */
    public function test_cp_ven_02_registrar_venta_con_varios_productos(): void
    {
        $camiseta = Producto::create([
            'nombre' => 'Camiseta Basica de Algodon',
            'precio_venta' => 35000.00,
            'precio_compra' => 15000.00,
            'stock' => 99,
            'stock_minimo' => 10,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $gafas = Producto::create([
            'nombre' => 'Gafas de Sol Retro',
            'precio_venta' => 45000.00,
            'precio_compra' => 20000.00,
            'stock' => 59,
            'stock_minimo' => 5,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $camiseta->id, 'nombre' => $camiseta->nombre, 'cantidad' => 2],
                ['id_producto' => $gafas->id, 'nombre' => $gafas->nombre, 'cantidad' => 1]
            ])
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $camiseta->refresh();
        $gafas->refresh();
        $this->assertEquals(97, $camiseta->stock);
        $this->assertEquals(58, $gafas->stock);

        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $this->cliente->id,
            'total' => 115000.00,
        ]);
    }

    /**
     * CP-VEN-03: Registrar venta rapida de Mostrador sin cliente
     */
    public function test_cp_ven_03_registrar_venta_rapida_mostrador_sin_cliente(): void
    {
        $cinturon = Producto::create([
            'nombre' => 'Cinturon de Cuero Reversible',
            'precio_venta' => 40000.00,
            'precio_compra' => 18000.00,
            'stock' => 79,
            'stock_minimo' => 5,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => null,
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $cinturon->id, 'nombre' => $cinturon->nombre, 'cantidad' => 1]
            ])
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $cinturon->refresh();
        $this->assertEquals(78, $cinturon->stock);

        $venta = Venta::latest('id')->first();
        $this->assertNull($venta->cliente_id);
        $this->assertEquals(40000.00, (float)$venta->total);

        // Comprobar vista comprobante para mostrador
        $viewResp = $this->actingAs($this->vendedor)->get("/ventas/{$venta->id}/comprobante");
        $viewResp->assertOk();
        $viewResp->assertSee('Cliente de Mostrador');
    }

    /**
     * CP-VEN-04: Registrar venta con pago por Transferencia bancaria
     */
    public function test_cp_ven_04_registrar_venta_transferencia_bancaria(): void
    {
        $vestido = Producto::create([
            'nombre' => 'Vestido Floral de Verano',
            'precio_venta' => 98000.00,
            'precio_compra' => 50000.00,
            'stock' => 30,
            'stock_minimo' => 3,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $ingresosAntes = (float)$this->caja->total_ingresos;

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Transferencia bancaria',
            'items' => json_encode([
                ['id_producto' => $vestido->id, 'nombre' => $vestido->nombre, 'cantidad' => 1]
            ])
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $vestido->refresh();
        $this->assertEquals(29, $vestido->stock);

        $this->caja->refresh();
        $this->assertEquals($ingresosAntes, (float)$this->caja->total_ingresos);
    }

    /**
     * CP-VEN-05: Validar carrito vacio o cantidad invalida
     */
    public function test_cp_ven_05_validar_carrito_vacio(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([])
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'msg' => 'Agrega al menos un producto a la venta.'
            ]);
    }

    /**
     * CP-VEN-06: Impedir venta mayor al stock disponible
     */
    public function test_cp_ven_06_impedir_venta_mayor_al_stock(): void
    {
        $botas = Producto::create([
            'nombre' => 'Botas de Cuero Casuales',
            'precio_venta' => 230000.00,
            'precio_compra' => 120000.00,
            'stock' => 14,
            'stock_minimo' => 2,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $botas->id, 'nombre' => $botas->nombre, 'cantidad' => 15]
            ])
        ]);

        $response->assertStatus(500)
            ->assertJson(['ok' => false]);

        $botas->refresh();
        $this->assertEquals(14, $botas->stock);
    }

    /**
     * CP-VEN-07: Bloquear registro de venta con la caja cerrada
     */
    public function test_cp_ven_07_bloquear_venta_con_caja_cerrada(): void
    {
        $this->caja->update(['estado' => 'Cerrada']);

        $producto = Producto::create([
            'nombre' => 'Prenda Test',
            'precio_venta' => 50000.00,
            'precio_compra' => 25000.00,
            'stock' => 10,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $producto->id, 'nombre' => $producto->nombre, 'cantidad' => 1]
            ])
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'msg' => 'Debe abrir la caja en el módulo de caja para poder registrar pagos en efectivo.'
            ]);
    }

    /**
     * CP-VEN-08: Consultar historial, filtros y detalle de venta
     */
    public function test_cp_ven_08_consultar_historial_y_detalle(): void
    {
        $response = $this->actingAs($this->admin)->get('/ventas');
        $response->assertOk();
        $response->assertViewHasAll(['ventas', 'clientes', 'productos', 'cajaAbierta', 'kpi']);
    }

    /**
     * CP-VEN-09: Generar e imprimir comprobante ticket POS
     */
    public function test_cp_ven_09_comprobante_ticket_pos(): void
    {
        $venta = Venta::create([
            'fecha' => now(),
            'total' => 85000.00,
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'estado' => 'Completada',
            'usuario_id' => $this->vendedor->id,
        ]);

        $response = $this->actingAs($this->vendedor)->get("/ventas/{$venta->id}/comprobante");
        $response->assertOk();
        $response->assertSee("IT'S FASHION");
        $response->assertSee('NIT: 900.281.199-5');
        $response->assertSee('#VEN-' . str_pad($venta->id, 6, '0', STR_PAD_LEFT));
        $response->assertSee('window.print()');
    }

    /**
     * CP-VEN-10: Descuento en Kardex y alerta de Stock Critico
     */
    public function test_cp_ven_10_kardex_y_alerta_stock_critico(): void
    {
        $chaqueta = Producto::create([
            'nombre' => 'Chaqueta de Cuero Biker',
            'precio_venta' => 180000.00,
            'precio_compra' => 90000.00,
            'stock' => 19,
            'stock_minimo' => 2,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $chaqueta->id, 'nombre' => $chaqueta->nombre, 'cantidad' => 17]
            ])
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $chaqueta->refresh();
        $this->assertEquals(2, $chaqueta->stock);

        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $chaqueta->id,
            'tipo_movimiento' => 'Salida',
            'cantidad' => 17,
            'stock_disponible' => 2,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->admin->id,
        ]);
    }

    /**
     * CP-VEN-11: Reflejo automatico de venta en efectivo en Caja
     */
    public function test_cp_ven_11_reflejo_automatico_en_caja(): void
    {
        $tenis = Producto::create([
            'nombre' => 'Tenis Deportivos Running',
            'precio_venta' => 145000.00,
            'precio_compra' => 70000.00,
            'stock' => 39,
            'stock_minimo' => 4,
            'categoria_id' => $this->categoria->id,
            'estado' => 'Activo',
        ]);

        $ingresosBase = (float)$this->caja->total_ingresos;

        $response = $this->actingAs($this->vendedor)->postJson('/ventas', [
            'cliente_id' => $this->cliente->id,
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([
                ['id_producto' => $tenis->id, 'nombre' => $tenis->nombre, 'cantidad' => 1]
            ])
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->caja->refresh();
        $this->assertEquals($ingresosBase + 145000.00, (float)$this->caja->total_ingresos);

        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $this->caja->id,
            'tipo' => 'Ingreso',
            'monto' => 145000.00,
        ]);
    }

    /**
     * CP-VEN-12: Control de acceso al modulo de ventas segun rol
     */
    public function test_cp_ven_12_control_de_acceso_segun_rol(): void
    {
        // 1. Vendedor accede a panel de ventas
        $respVendedor = $this->actingAs($this->vendedor)->get('/ventas');
        $respVendedor->assertOk();
        $respVendedor->assertViewIs('empleado.ventas');

        // 2. Cliente redirigido a vista personal Mis Compras
        $respCliente = $this->actingAs($this->cliente)->get('/ventas');
        $respCliente->assertOk();
        $respCliente->assertViewIs('cliente.mis_compras');

        // 3. Cliente intenta ruta exclusiva /ventas/buscar-cliente -> 403
        $respBuscar = $this->actingAs($this->cliente)->get('/ventas/buscar-cliente');
        $respBuscar->assertStatus(403);

        // 4. Cliente intenta registrar venta por POST -> 403
        $respStore = $this->actingAs($this->cliente)->postJson('/ventas', [
            'metodo_pago' => 'Efectivo',
            'items' => json_encode([['id_producto' => 1, 'cantidad' => 1]])
        ]);
        $respStore->assertStatus(403);

        // 5. Cliente intenta ver comprobante de una venta ajena
        $otraVenta = Venta::create([
            'fecha' => now(),
            'total' => 100000.00,
            'cliente_id' => null,
            'metodo_pago' => 'Efectivo',
            'estado' => 'Completada',
            'usuario_id' => $this->vendedor->id,
        ]);

        $respComp = $this->actingAs($this->cliente)->get("/ventas/{$otraVenta->id}/comprobante");
        $respComp->assertStatus(403);
    }
}

