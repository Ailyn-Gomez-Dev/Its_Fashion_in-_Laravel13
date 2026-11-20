# 🚀 PROMPT MAESTRO PARA IMPLEMENTACIÓN DE APP MÓVIL FLUTTER
## Sistema Integral de Boutique: "It's Fashion"

> **Instrucciones para la IA / Desarrollador:**
> Este documento contiene la arquitectura, reglas de negocio y especificación técnica módulo por módulo para construir la aplicación móvil en **Flutter** conectada al backend Laravel en producción.
> Cada módulo especifica: la pantalla requerida, la interacción de usuario, la lógica de negocio y el endpoint exacto de la API REST que debe consumir.

---

## 🌐 1. CONFIGURACIÓN DEL ENTORNO Y REGLAS GENERALES

* **Backend en Producción:** `https://its-fashion.wasmer.app/api`
* **Protocolo de Comunicación:** RESTful JSON con HTTPS.
* **Autenticación:** Tokens de portador con **Laravel Sanctum**.
* **Cabeceras globales en cada petición:**
  ```http
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer <TOKEN_ALMACENADO>
  ```
* **Manejo Global de Errores HTTP:**
  * `200 / 201`: Éxito en la operación.
  * `401 Unauthorized`: El token expiró o es inválido. La app debe limpiar el almacenamiento local y redirigir automáticamente a la pantalla de Login.
  * `403 Forbidden`: El usuario no tiene permisos para esta acción según su rol.
  * `422 Unprocessable Entity`: Errores de validación de campos. Mostrar los mensajes del mapa `errors` debajo de cada input correspondiente.
  * `429 Too Many Requests`: Bloqueo por seguridad tras 5 intentos fallidos. Mostrar cuenta regresiva (`retry_after`).

---

## 🏗️ 2. ESTRUCTURA RECOMENDADA EN FLUTTER (`lib/`)

```text
lib/
├── config/              # Constantes de API, temas de color y estilos
├── models/              # Modelos Dart con fromJson() y toJson()
├── services/            # Servicios HTTP (AuthService, ProductService, etc.)
├── providers/           # Gestor de estado (Provider / Riverpod / Bloc)
├── views/               # Pantallas organizadas por módulo y rol
│   ├── auth/            # Login, Registro, Recuperación
│   ├── cliente/         # Catálogo, Carrito, Mis Compras, Devoluciones
│   ├── pos/             # Venta rápida, Caja, Búsqueda de cliente
│   ├── admin/           # Inventario, Compras, Proveedores, Empleados, Reportes
│   └── shared/          # Widgets reutilizables, Navbar, Drawer
└── main.dart            # Inicialización de la app y rutas
```

---

## 📱 3. ESPECIFICACIÓN DETALLADA POR MÓDULOS

---

### MÓDULO 1: Conectividad y Splash Screen
* **Objetivo:** Verificar disponibilidad del backend antes de cargar la interfaz.
* **Pantalla:** `SplashScreen`.
* **Lógica:**
  1. Realizar una llamada a la API de salud.
  2. Si responde `ok`, verificar si existe un token guardado en almacenamiento seguro (`flutter_secure_storage`).
  3. Si hay token, consultar `/user` para identificar el rol y redirigir a su vista principal (`Cliente`, `Vendedor` o `Admin`). Si no hay token, ir a `LoginScreen`.
* **API a consumir:**
  * `GET /health-check` (Público)
  * `GET /user` (Protegido)

---

### MÓDULO 2: Autenticación y Perfil
* **Pantallas:** `LoginScreen`, `RegisterClientScreen`, `ProfileScreen`.
* **Funcionalidades:**
  1. **Inicio de sesión:** Input de usuario/email y contraseña. Guardar el `token` y los datos del `user`.
  2. **Registro de cliente:** Formulario con nombre, apellido, usuario, teléfono, email y contraseña con confirmación.
  3. **Editar Perfil:** Cambiar nombre, teléfono y contraseña (requiere validar contraseña actual).
  4. **Cerrar sesión:** Revocar token en servidor y limpiar almacenamiento local.
* **APIs a consumir:**
  * `POST /login` -> Body: `{"login": "...", "password": "..."}`
  * `POST /register` -> Body: `{"nombre": "...", "apellido": "...", "username": "...", "telefono": "...", "email": "...", "password": "...", "password_confirmation": "..."}`
  * `GET /user` -> Obtener datos actualizados del perfil.
  * `PUT /user` -> Body: `{"nombre": "...", "apellido": "...", "telefono": "...", "current_password": "...", "new_password": "...", "new_password_confirmation": "..."}`
  * `POST /logout` -> Cerrar sesión.

---

### MÓDULO 3: Catálogo y Tienda Móvil (Cliente y Vendedor)
* **Pantallas:** `HomeScreen`, `ProductDetailScreen`, `CategoryProductsScreen`.
* **Funcionalidades:**
  1. Carrusel de categorías con conteo de prendas.
  2. Barra de búsqueda en tiempo real con debounce.
  3. Grid de productos con imagen, nombre, precio, stock disponible y botón "Agregar al Carrito".
  4. Vista detalle: selector de tallas, colores y botón de compra directa.
* **APIs a consumir:**
  * `GET /categories` -> Lista de categorías.
  * `GET /products` -> Parámetros opcionales: `?categoria_id=...&search=...&page=...`
  * `GET /products/{id}` -> Ficha técnica de la prenda.

---

### MÓDULO 4: Carrito de Compras y Pedidos Móviles
* **Pantallas:** `CartScreen`, `OrderSuccessScreen`.
* **Funcionalidades:**
  1. Listar prendas agregadas, selector para aumentar/disminuir cantidad (validar contra stock).
  2. Cálculo de subtotal, descuentos e impuestos.
  3. Selección de método de pago: `Efectivo` o `Transferencia bancaria`.
  4. Confirmación de compra y generación de orden.
* **APIs a consumir:**
  * `POST /ventas` -> Registrar venta.
    ```json
    {
      "metodo_pago": "Efectivo",
      "items": [
        {"producto_id": 1, "cantidad": 2, "precio": 89000}
      ]
    }
    ```

---

### MÓDULO 5: Punto de Venta Móvil (POS) para Cajeros y Vendedores
* **Pantallas:** `PosScreen`, `SearchClientModal`, `ReceiptScreen`.
* **Funcionalidades:**
  1. Buscador instantáneo de clientes por nombre o cédula para asociar a la factura.
  2. Lector de código de barras con la cámara para agregar prendas a la venta.
  3. Selector de pago. Si es `Efectivo`, valida automáticamente que la caja esté abierta y suma el dinero.
  4. Pantalla de Comprobante / Ticket con opción de compartir por WhatsApp o imprimir en impresora térmica Bluetooth.
* **APIs a consumir:**
  * `GET /ventas/buscar-cliente?q={cedula_o_nombre}` -> Autocompletado de cliente.
  * `POST /clientes` -> Registro exprés de un cliente nuevo sin salir del POS.
  * `POST /ventas` -> Registrar venta de caja.
  * `GET /ventas/{id}/comprobante` -> Retorna JSON con formato listo para ticket fiscal.

---

### MÓDULO 6: Control y Arqueo de Caja (Turnos POS)
* **Pantallas:** `CajaStatusScreen`, `AbrirCajaModal`, `CerrarCajaModal`, `MovimientoCajaModal`.
* **Funcionalidades:**
  1. Indicador visual: Estado `Abierta` o `Cerrada`.
  2. **Abrir Caja:** Solicitar monto base en efectivo para iniciar el turno.
  3. **Cerrar Caja (Arqueo):** Ingresar monto contado en gaveta, comparar contra ventas en efectivo y registrar observaciones del balance.
  4. **Movimientos:** Registrar salidas (pago de fletes, tintorería, etc.) o entradas manuales de dinero.
* **APIs a consumir:**
  * `GET /caja` -> Saldo actual, ingresos en efectivo y estado.
  * `POST /caja/abrir` -> Body: `{"monto_inicial": 100000}`
  * `POST /caja/cerrar` -> Body: `{"monto_final": 450000, "observaciones": "Turno tarde sin novedades"}`
  * `POST /caja/movimiento` -> Body: `{"tipo": "Egreso", "monto": 25000, "concepto": "Compra de bolsas boutique"}`

---

### MÓDULO 7: Gestión de Clientes
* **Pantallas:** `ClientesListScreen`, `ClienteDetailScreen`.
* **Funcionalidades:**
  1. Listado paginado de clientes con filtro por nombre, cédula o teléfono.
  2. Ficha de cliente: datos de contacto, historial de compras realizadas y solicitudes de devolución.
  3. Switch para activar o inactivar cliente (solo Admin).
* **APIs a consumir:**
  * `GET /clientes` -> Lista de clientes.
  * `GET /clientes/{id}` -> Perfil del cliente.
  * `GET /clientes/{id}/compras` -> Historial de compras.
  * `GET /clientes/{id}/devoluciones` -> Historial de garantías.
  * `PATCH /clientes/{id}/toggle-estado` -> Cambiar estado activo/inactivo.

---

### MÓDULO 8: Gestión de Productos y Categorías (Admin / Vendedor)
* **Pantallas:** `AdminProductsScreen`, `ProductFormScreen`, `CategoriesManagerScreen`.
* **Funcionalidades:**
  1. Crear y editar productos (nombre, categoría, precio venta, costo de compra, stock, talla, color).
  2. Ajuste manual de stock por mermas, daños o conteo físico.
  3. Activar/Inactivar producto para ocultarlo de la tienda móvil.
  4. CRUD completo de categorías de prendas.
* **APIs a consumir:**
  * `POST /products` -> Crear producto.
  * `PUT /products/{id}` -> Editar producto.
  * `DELETE /products/{id}` -> Eliminar producto.
  * `POST /products/{id}/adjust-stock` -> Body: `{"tipo": "entrada" | "salida", "cantidad": 5, "motivo": "Ajuste por inventario físico"}`
  * `PATCH /products/{id}/toggle-estado` -> Ocultar/Mostrar prenda.
  * `POST /categories` / `PUT /categories/{id}` / `DELETE /categories/{id}` -> CRUD categorías.

---

### MÓDULO 9: Proveedores de Mercancía
* **Pantallas:** `ProveedoresListScreen`, `ProveedorFormScreen`.
* **Funcionalidades:**
  1. Directorio de proveedores de ropa y calzado (nombre, contacto, NIT/cédula, teléfono, email, dirección).
  2. Crear, editar y eliminar proveedores (valida que no tengan compras activas antes de borrar).
* **APIs a consumir:**
  * `GET /proveedores` -> Listar proveedores.
  * `POST /proveedores` -> Crear proveedor.
  * `GET /proveedores/{id}` -> Ver proveedor y total de compras.
  * `PUT /proveedores/{id}` -> Modificar datos.
  * `DELETE /proveedores/{id}` -> Eliminar proveedor.

---

### MÓDULO 10: Compras de Mercancía (Abastecimiento de Stock)
* **Pantallas:** `ComprasHistoryScreen`, `NuevaCompraScreen`, `CompraDetailScreen`.
* **Funcionalidades:**
  1. Registrar compras en lote a un proveedor seleccionado.
  2. Agregar múltiples productos con su cantidad recibida y costo unitario.
  3. Al guardar, el backend automáticamente:
     * Incrementa el stock de cada prenda en la tienda.
     * Actualiza el costo de adquisición de la prenda.
     * Crea los registros de entrada en el Kardex de inventario.
* **APIs a consumir:**
  * `GET /compras` -> Historial de compras.
  * `POST /compras` -> Registrar compra en lote:
    ```json
    {
      "proveedor_id": 1,
      "items": [
        {"producto_id": 3, "cantidad": 12, "precio_unitario": 35000.00}
      ]
    }
    ```
  * `GET /compras/{id}` -> Ver factura de compra y productos recibidos.

---

### MÓDULO 11: Inventario y Kardex
* **Pantallas:** `InventarioKardexScreen`, `StockAlertsWidget`.
* **Funcionalidades:**
  1. Tarjetas KPI de estado: Total de prendas, prendas con stock, prendas en stock crítico (bajo mínimo) y agotadas.
  2. Tabla o listado del Kardex con filtro por producto y tipo de movimiento (`Entrada` por compra, `Salida` por venta, `Ajuste manual`).
* **APIs a consumir:**
  * `GET /inventario/kpis` -> Contadores de stock.
  * `GET /inventario` -> Movimientos cronológicos del Kardex.

---

### MÓDULO 12: Gestión de Usuarios y Empleados (Solo Administrador)
* **Pantallas:** `UsuariosListScreen`, `UsuarioFormScreen`.
* **Funcionalidades:**
  1. Listar empleados y administradores del sistema.
  2. Registrar nuevo vendedor asignándole rol, permisos de caja y contraseña.
  3. Editar usuario o suspender su acceso (`Inactivo`).
  4. El sistema previene automáticamente desactivar o degradar al último administrador activo.
* **APIs a consumir:**
  * `GET /usuarios` -> Listar empleados y admins.
  * `GET /roles` -> Obtener roles asignables (`Admin`, `Vendedor`).
  * `POST /usuarios` -> Crear empleado.
  * `PUT /usuarios/{id}` -> Editar datos o credenciales.
  * `PATCH /usuarios/{id}/toggle-estado` -> Activar / Inactivar empleado.

---

### MÓDULO 13: Devoluciones y Garantías
* **Pantallas:** `DevolucionesScreen`, `SolicitarDevolucionModal`.
* **Funcionalidades:**
  1. **Clientes:** Pueden solicitar devolución seleccionando una compra previa, prenda, cantidad y motivo.
  2. **Administradores:** Panel para revisar solicitudes y botones para `Aprobar` (reintegra stock automáticamente) o `Rechazar`.
* **APIs a consumir:**
  * `GET /devoluciones` -> Historial de solicitudes.
  * `POST /devoluciones` -> Radicar solicitud.
  * `POST /devoluciones/{id}/aprobar` -> Aprobar garantía y devolver stock.
  * `POST /devoluciones/{id}/rechazar` -> Rechazar solicitud con motivo.

---

### MÓDULO 14: Reportes y Analítica Comercial (Solo Administrador)
* **Pantallas:** `ReportesDashboardScreen`, `MetodosPagoChartWidget`, `TopProductosWidget`.
* **Funcionalidades:**
  1. Selector de periodo: *Esta semana*, *Este mes*, *Últimos 3 meses*, o *Rango personalizado*.
  2. Tarjetas con: Ventas totales, Dinero total recaudado, Ticket promedio y Devoluciones.
  3. Gráfico circular de distribución de pagos (`Efectivo` vs `Transferencia`).
  4. Ranking de las 10 prendas más vendidas del negocio.
* **APIs a consumir:**
  * `GET /reportes/resumen?desde=YYYY-MM-DD&hasta=YYYY-MM-DD`
  * `GET /reportes/metodos-pago?desde=YYYY-MM-DD&hasta=YYYY-MM-DD`
  * `GET /reportes/top-productos?limit=10`

---

### MÓDULO 15: Notificaciones y Dashboard
* **Pantallas:** `DashboardScreen`, `NotificationsModal`.
* **Funcionalidades:**
  1. Resumen inicial según el rol del usuario que inicia sesión:
     * **Admin:** Ventas de hoy, stock crítico y accesos a reportes.
     * **Vendedor:** Estado de la caja y botón de venta rápida.
     * **Cliente:** Novedades, promociones y pedidos recientes.
  2. Campana de notificaciones con badge de alertas no leídas (ej. "Stock Crítico en Jeans", "Nueva venta").
* **APIs a consumir:**
  * `GET /dashboard` -> Métricas adaptadas al rol del token.
  * `GET /notificaciones` -> Listar alertas.
  * `POST /notificaciones/leer-todas` -> Marcar leídas.

---

## 🏁 INSTRUCCIÓN PARA INICIAR EL DESARROLLO EN FLUTTER

Copia el siguiente mensaje para iniciar la conversación en el chat de Flutter:

> *"Actúa como un desarrollador experto en Flutter. Vamos a construir la aplicación móvil de **It's Fashion** conectada al backend en producción `https://its-fashion.wasmer.app/api`. El sistema cuenta con 58 endpoints que cubren el 100% de la funcionalidad (Autenticación, Tienda de Cliente, POS Móvil, Caja, Clientes, Inventario, Compras, Proveedores, Usuarios y Reportes). Revisa la especificación técnica en este documento e implementemos la arquitectura base (`api_service.dart`, modelos y autenticación) para comenzar."*
