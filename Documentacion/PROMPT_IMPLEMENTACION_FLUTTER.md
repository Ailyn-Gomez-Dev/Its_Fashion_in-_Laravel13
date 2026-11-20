# 📱 ESPECIFICACIÓN TÉCNICA MAESTRA Y PROMPT DE IMPLEMENTACIÓN
## APP MÓVIL EN FLUTTER - SISTEMA BOUTIQUE "IT'S FASHION"
### 100% Cobertura Funcional | 58 Endpoints | 3 Roles de Usuario

> **DESTINATARIO / AGENTE DE FLUTTER:**
> Este documento es la especificación técnica definitiva para construir la aplicación móvil en Flutter conectada al backend Laravel en **Wasmer Edge** (`https://its-fashion.wasmer.app/api`).
> El sistema implementa una arquitectura multi-rol estricta (**Cliente**, **Vendedor/Cajero** y **Administrador**) donde cada perfil cuenta con flujos de pantalla, permisos, reglas de negocio y endpoints específicos.

---

## 📑 ÍNDICE GENERAL
1. [Arquitectura General y Configuración de Red](#1-arquitectura-general-y-configuración-de-red)
2. [Matriz de Permisos y Acceso por Roles](#2-matriz-de-permisos-y-acceso-por-roles)
3. [Flujo 1: Experiencia y Pantallas del CLIENTE (Rol 3)](#3-flujo-1-experiencia-y-pantallas-del-cliente-rol-3)
4. [Flujo 2: Experiencia y Pantallas del VENDEDOR / POS (Rol 2)](#4-flujo-2-experiencia-y-pantallas-del-vendedor--pos-rol-2)
5. [Flujo 3: Experiencia y Pantallas del ADMINISTRADOR (Rol 1)](#5-flujo-3-experiencia-y-pantallas-del-administrador-rol-1)
6. [Catálogo Exhaustivo de los 58 Endpoints de la API](#6-catálogo-exhaustivo-de-los-58-endpoints-de-la-api)
7. [Modelos de Datos en Dart (Serialización JSON)](#7-modelos-de-datos-en-dart-serialización-json)
8. [Servicio HTTP Base con Interceptores en Flutter](#8-servicio-http-base-con-interceptores-en-flutter)
9. [Instrucciones de Arranque para la IA de Flutter](#9-instrucciones-de-arranque-para-la-ia-de-flutter)

---

## 🌐 1. ARQUITECTURA GENERAL Y CONFIGURACIÓN DE RED

* **Backend en Producción:** `https://its-fashion.wasmer.app/api`
* **Protocolo:** HTTPS con intercambio de datos en formato JSON estricto.
* **Autenticación:** Tokens Bearer gestionados por **Laravel Sanctum**.
* **Almacenamiento Seguro:** Uso de `flutter_secure_storage` para persistir:
  * `auth_token` (String)
  * `user_data` (JSON con `id`, `nombre`, `apellido`, `username`, `email`, `rol_id`, `rol`, `estado`, `puede_operar_caja`).
* **Headers requeridos en todas las peticiones:**
  ```http
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer <TOKEN_DE_SANCTUM>
  ```
* **Protocolo de Manejo de Errores HTTP:**
  * **`401 Unauthorized`:** Token caducado o inválido. La aplicación debe eliminar el token local, resetear el estado y redirigir inmediatamente a `LoginScreen`.
  * **`403 Forbidden`:** El usuario intentó acceder a una acción fuera de su rol. Mostrar `SnackBar` o diálogo de advertencia *"No tienes autorización para realizar esta acción"*.
  * **`422 Unprocessable Entity`:** Fallo de validación en backend. Mapear el objeto JSON `errors` mostrando los mensajes de error en rojo bajo cada input del formulario correspondiente.
  * **`429 Too Many Requests`:** Bloqueo temporal por intentos fallidos de login (5 intentos máximo). Extraer el campo `retry_after` (segundos) y mostrar un temporizador en pantalla.
  * **`500 Server Error`:** Error en servidor. Mostrar mensaje amigable: *"Error de comunicación con el servidor. Intenta nuevamente"*.

---

## 🛡️ 2. MATRIZ DE PERMISOS Y ACCESO POR ROLES

El sistema cuenta con tres roles definidos en la base de datos:

| ID Rol | Nombre Técnico | Etiqueta UI | Enfoque de la Interfaz |
| :---: | :--- | :--- | :--- |
| **`3`** | `cliente` | **Cliente** | Tienda digital, catálogo, compras personales, historial y garantías. |
| **`2`** | `vendedor` | **Vendedor / Cajero** | POS móvil, apertura/cierre de caja, ventas rápidas, consulta de stock y clientes. |
| **`1`** | `admin` | **Administrador** | Control total: POS, caja, inventario, compras a proveedores, usuarios y reportes. |

### Matriz de Acceso a Endpoints por Rol

| Módulo / Funcionalidad | Cliente (`3`) | Vendedor (`2`) | Admin (`1`) | Endpoints Relacionados |
| :--- | :---: | :---: | :---: | :--- |
| **Salud y Conectividad** | ✅ | ✅ | ✅ | `GET /health-check` |
| **Login y Registro Cliente** | ✅ | ✅ | ✅ | `POST /login`, `POST /register` |
| **Mi Perfil y Contraseña** | ✅ | ✅ | ✅ | `GET /user`, `PUT /user`, `POST /logout` |
| **Ver Catálogo y Precios** | ✅ | ✅ | ✅ | `GET /products`, `GET /products/{id}`, `GET /categories` |
| **Comprar / Registrar Venta** | ✅ (Solo online) | ✅ (POS físico) | ✅ (POS físico) | `POST /ventas`, `GET /ventas`, `GET /ventas/{id}` |
| **Imprimir Ticket de Venta** | ✅ (Recibo digital)| ✅ (Ticket POS) | ✅ (Ticket POS) | `GET /ventas/{id}/comprobante` |
| **Buscador Rápido de Clientes**| ❌ | ✅ | ✅ | `GET /ventas/buscar-cliente` |
| **Turnos de Caja (Abrir/Cerrar/Mov)**| ❌ | ✅ | ✅ | `GET /caja`, `POST /caja/abrir`, `POST /caja/cerrar`, `POST /caja/movimiento` |
| **Directorio de Clientes** | ❌ | ✅ | ✅ | `GET /clientes`, `POST /clientes`, `GET /clientes/{id}`, `/compras`, `/devoluciones` |
| **Activar / Inactivar Cliente**| ❌ | ❌ | ✅ | `PATCH /clientes/{id}/toggle-estado` |
| **Crear / Editar Productos** | ❌ | ✅ | ✅ | `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}` |
| **Ajustar Stock Manualmente** | ❌ | ✅ | ✅ | `POST /products/{id}/adjust-stock`, `PATCH /products/{id}/toggle-estado` |
| **CRUD de Categorías** | ❌ | ✅ | ✅ | `POST /categories`, `PUT /categories/{id}`, `DELETE /categories/{id}` |
| **Proveedores de Mercancía** | ❌ | ✅ | ✅ | `GET /proveedores`, `POST /proveedores`, `PUT /proveedores/{id}`, `DELETE /proveedores/{id}` |
| **Compras de Abastecimiento** | ❌ | ✅ | ✅ | `GET /compras`, `POST /compras`, `GET /compras/{id}` |
| **Kardex e Inventario** | ❌ | ✅ | ✅ | `GET /inventario`, `GET /inventario/kpis` |
| **Gestión de Empleados** | ❌ | ❌ | ✅ | `GET /usuarios`, `POST /usuarios`, `PUT /usuarios/{id}`, `PATCH .../toggle-estado`, `GET /roles` |
| **Solicitar Devolución** | ✅ | ❌ | ❌ | `POST /devoluciones`, `GET /devoluciones` |
| **Aprobar/Rechazar Devolución**| ❌ | ❌ | ✅ | `POST /devoluciones/{id}/aprobar`, `POST /devoluciones/{id}/rechazar` |
| **Reportes y Analítica** | ❌ | ❌ | ✅ | `GET /reportes/resumen`, `GET /reportes/metodos-pago`, `GET /reportes/top-productos` |
| **Notificaciones y Dashboard** | ✅ (Personal) | ✅ (Operativo) | ✅ (Gerencial) | `GET /dashboard`, `GET /notificaciones`, `POST /notificaciones/leer-todas` |

---

## 🛍️ 3. FLUJO 1: EXPERIENCIA Y PANTALLAS DEL CLIENTE (ROL 3)

El cliente utiliza la aplicación como una boutique de moda interactiva para explorar prendas, hacer compras y gestionar sus garantías.

```
[Splash Screen] ──> [Login / Registro]
                          │
                          ▼
             [Bottom Navigation Bar Cliente]
    ┌──────────────┬──────────────┬──────────────┬──────────────┐
    ▼              ▼              ▼              ▼              ▼
[Inicio/Tienda] [Buscador]     [Carrito]   [Mis Compras]   [Mi Perfil]
    │                             │              │              │
[Detalle Prenda]             [Checkout]    [Comprobante]  [Devoluciones]
```

### Pantalla 1.1: `ClientHomeScreen` (Inicio y Catálogo)
* **Componentes UI:**
  * Banner carrusel superior con promociones y colecciones de temporada.
  * Carrusel horizontal de Categorías (`GET /categories`): Jeans, Vestidos, Calzado, Chaquetas, Accesorios. Al pulsar una categoría, filtra automáticamente la lista inferior.
  * Grid de productos (`GET /products`): Card con foto de la prenda, nombre, precio formateado en pesos colombianos (`$ 89.000`), badge de stock disponible y botón flotante de *"Añadir al carrito"*.
  * Barra de búsqueda superior con icono de filtro.

### Pantalla 1.2: `ProductDetailScreen` (Ficha de la Prenda)
* **Componentes UI:**
  * Visor de imagen en alta resolución con zoom interactivo.
  * Nombre, código de referencia y descripción de materiales.
  * Precio de venta al público en tipografía destacada.
  * Selector de Tallas disponibles (`S`, `M`, `L`, `XL`, etc.).
  * Selector de Color de la prenda.
  * Selector de cantidad (limitado por el stock máximo devuelto por la API).
  * Botón inferior fijo: *"Agregar a la Bolsa"* y *"Comprar Ahora"*.
* **API:** `GET /products/{id}`.

### Pantalla 1.3: `CartScreen` y `CheckoutScreen` (Bolsa y Pago)
* **Componentes UI:**
  * Listado de prendas agregadas con foto, talla elegida, color y controles `+` / `-` para cambiar cantidad.
  * Botón para eliminar prenda individual o vaciar bolsa.
  * Desglose financiero: Subtotal, Envío (si aplica) y Total a Pagar.
  * Formulario de Checkout:
    * Selector de Método de Pago: Radio button entre **"Efectivo en Tienda"** o **"Transferencia Bancaria"** (Nequi / Bancolombia).
    * Campo opcional: Notas o instrucciones de entrega.
  * Botón final: *"Confirmar Pedido"*.
* **API:** `POST /ventas`.

### Pantalla 1.4: `ClientOrdersScreen` (Mis Compras)
* **Componentes UI:**
  * Listado cronológico de compras realizadas por el cliente autenticado (`GET /ventas`).
  * Cada tarjeta muestra: Número de pedido (`FAC-000012`), fecha, total pagado y badge de estado (`Completada`, `Pendiente`, `Cancelada`).
  * Al tocar una compra, abre la vista de detalle con la lista de productos adquiridos.
  * Botón **"Ver Comprobante de Pago"**: Consume `GET /ventas/{id}/comprobante` y renderiza el recibo oficial con opción de compartir imagen o guardar PDF.
  * Botón **"Solicitar Devolución"**: Solo visible en compras completadas. Abre el modal de solicitud de garantía.

### Pantalla 1.5: `ClientReturnsScreen` (Garantías y Devoluciones)
* **Componentes UI:**
  * Listado de devoluciones solicitadas por el cliente (`GET /devoluciones`).
  * Estado visual en cada tarjeta:
    * 🟡 **Pendiente:** En revisión por el administrador.
    * 🟢 **Aprobada:** Garantía aceptada, saldo a favor generado.
    * 🔴 **Rechazada:** Muestra el motivo exacto del rechazo escrito por el admin.
  * Botón flotante `+` para radicar nueva solicitud:
    * Selector de compra previa.
    * Selector de la prenda específica a devolver.
    * Cantidad a devolver.
    * Campo de texto obligatorio: Motivo detallado del daño, talla incorrecta o defecto de fábrica.
* **API:** `POST /devoluciones`.

### Pantalla 1.6: `ClientProfileScreen` (Mi Perfil)
* **Componentes UI:**
  * Datos personales del cliente (Nombre, Apellido, Email, Teléfono, Username).
  * Botón *"Editar Información"*: Permite actualizar nombre y teléfono (`PUT /user`).
  * Sección *"Seguridad"*: Formulario para cambiar contraseña (pide contraseña actual, nueva contraseña y confirmación).
  * Centro de notificaciones personales (`GET /notificaciones`).
  * Botón de *"Cerrar Sesión"* (`POST /logout`).

---

## 💼 4. FLUJO 2: EXPERIENCIA Y PANTALLAS DEL VENDEDOR / POS (ROL 2)

El vendedor/cajero utiliza la app como una terminal de punto de venta táctil, rápida y optimizada para operar en la boutique.

```
[Splash Screen] ──> [Login Vendedor]
                          │
                          ▼
             [Bottom Navigation Bar Vendedor]
    ┌──────────────┬──────────────┬──────────────┬──────────────┐
    ▼              ▼              ▼              ▼              ▼
  [POS Venta]    [Caja/Turno]   [Catálogo]     [Clientes]     [Compras]
    │                 │              │              │              │
[Ticket Venta]   [Arqueo Caja]  [Ajustar Stock][Ficha Cliente][Abastecimiento]
```

### Pantalla 2.1: `PosScreen` (Punto de Venta Móvil Rápido)
* **Componentes UI:**
  * **Barra superior de Cliente:**
    * Muestra el cliente asignado a la venta actual (por defecto: *"Cliente General / Consumidor Final"*).
    * Botón de búsqueda: Abre `SearchClientModal` que consume `GET /ventas/buscar-cliente?q=...` con autocompletado en tiempo real al escribir cédula o nombre.
    * Botón `+`: Abre formulario rápido para registrar un cliente nuevo sin perder la venta (`POST /clientes`).
  * **Sección de Productos:**
    * Botón de Cámara/Scanner: Activa la cámara para escanear código de barras de la etiqueta de la prenda.
    * Buscador rápido por código o nombre.
    * Grilla compacta de productos con foto miniatura, talla, stock actual y precio.
  * **Carrito de Caja lateral o inferior:**
    * Lista de ítems cargados a la factura con cantidad modificable.
    * Cálculo en vivo: Subtotal y Total.
  * **Modal de Cobro:**
    * Selector de método de pago: `Efectivo` o `Transferencia bancaria`.
    * Si selecciona `Efectivo`:
      * La app valida primero que la caja esté abierta (`GET /caja`). Si está cerrada, bloquea el cobro y muestra botón para abrir turno.
      * Input de *"Dinero Recibido"* y cálculo automático del cambio/vuelto a devolver.
    * Botón *"Completar Venta y Emitir Ticket"*.
* **APIs:** `GET /ventas/buscar-cliente`, `POST /ventas`, `GET /ventas/{id}/comprobante`.

### Pantalla 2.2: `ReceiptPosScreen` (Ticket de Venta Térmico)
* **Componentes UI:**
  * Vista previa del ticket con estilo de factura térmica de boutique:
    * Encabezado con datos del negocio (It's Fashion, NIT, dirección, teléfono).
    * Número de factura (`FAC-000045`), fecha y hora exacta.
    * Datos del cajero y cliente.
    * Tabla con: Cantidad, Descripción de la prenda, Talla, Precio Unitario y Subtotal.
    * Total cobrado y método de pago.
  * Botón **"Imprimir Bluetooth"**: Envía comandos ESC/POS a impresoras térmicas portátiles (58mm o 80mm).
  * Botón **"Compartir WhatsApp"**: Genera imagen o PDF del comprobante y lo envía al chat del cliente.
  * Botón **"Nueva Venta"**: Limpia el POS para la siguiente transacción.
* **API:** `GET /ventas/{id}/comprobante`.

### Pantalla 2.3: `CajaTurnoScreen` (Gestión de Turnos y Caja)
* **Componentes UI:**
  * **Si la caja está cerrada:**
    * Pantalla con ilustración y botón destacado: *"Abrir Turno de Caja"*.
    * Diálogo modal para ingresar el `monto_inicial` (fondo base de billetes y monedas).
    * API: `POST /caja/abrir`.
  * **Si la caja está abierta:**
    * Banner verde con badge *"Caja Activa"*, hora de apertura y usuario responsable.
    * Tarjeta resumen con saldo calculado en tiempo real:
      * Monto Base Inicial.
      * (+) Ventas en Efectivo registradas.
      * (+) Ingresos manuales en efectivo.
      * (-) Egresos o gastos de caja.
      * **(=) Total en Gaveta Esperado.**
    * Botón *"Registrar Movimiento"*: Modal para ingresar salidas (ej. $15.000 para almuerzo/insumos) o entradas extraordinarias (`POST /caja/movimiento`).
    * Botón *"Cerrar Turno (Arqueo)"*: Abre diálogo para ingresar el dinero físico contado. Muestra automáticamente si hay sobrante, faltante o cuadre exacto, permite escribir observaciones de cierre y confirma (`POST /caja/cerrar`).

### Pantalla 2.4: `VendedorCatalogScreen` (Consulta de Existencias)
* **Componentes UI:**
  * Lista completa de prendas con buscador instantáneo y filtro por categoría.
  * Indicador de existencias con alerta de colores:
    * 🟢 Verde: Stock óptimo (> stock mínimo).
    * 🟡 Amarillo: Stock crítico (queda poco inventario).
    * 🔴 Rojo: Agotado (0 unidades).
  * Al tocar una prenda:
    * Opciones para: Ver ficha, modificar datos básicos (`PUT /products/{id}`) o realizar un **Ajuste Rápido de Stock** (`POST /products/{id}/adjust-stock`) especificando si es Entrada o Salida física y el motivo.

### Pantalla 2.5: `VendedorComprasScreen` (Recepción de Mercancía)
* **Componentes UI:**
  * Permite al vendedor registrar la llegada de un lote de prendas enviado por un proveedor:
    * Selector de proveedor (`GET /proveedores`).
    * Lista de prendas recibidas, cantidades y costos unitarios.
    * Botón *"Guardar Compra"*: Consume `POST /compras`, lo cual suma las prendas inmediatamente al stock de la boutique y crea los registros correspondientes en el Kardex.

---

## 👑 5. FLUJO 3: EXPERIENCIA Y PANTALLAS DEL ADMINISTRADOR (ROL 1)

El Administrador tiene el control gerencial, financiero y operativo absoluto de la empresa.

```
[Splash Screen] ──> [Login Admin]
                          │
                          ▼
             [Bottom Navigation Bar Administrador]
    ┌──────────────┬──────────────┬──────────────┬──────────────┐
    ▼              ▼              ▼              ▼              ▼
[Dashboard KPI]   [POS / Caja]   [Gestión Cat.] [Administración] [Reportes]
                         ┌───────────────┴───────────────┐
                         ▼                               ▼
                 [Usuarios/Empleados]          [Devoluciones / Admin]
                 [Proveedores/Compras]         [Inventario Kardex]
```

### Pantalla 3.1: `AdminDashboardScreen` (Panel Ejecutivo)
* **Componentes UI:**
  * Resumen ejecutivo en tarjetas interactivas:
    * Ventas de Hoy (monto en $ y cantidad de transacciones).
    * Estado de la Caja (Abierta/Cerrada y saldo).
    * Alertas de Stock Crítico (prendas por agotarse).
    * Solicitudes de Devolución pendientes de aprobación.
  * Campana de notificaciones del sistema con badge contador (`GET /notificaciones`).
* **API:** `GET /dashboard`.

### Pantalla 3.2: `AdminUsersScreen` (Gestión de Empleados y Roles)
* **Componentes UI:**
  * Listado de todos los usuarios internos del sistema (`rol_id: 1` Admin y `rol_id: 2` Vendedor).
  * Cada tarjeta muestra: Nombre completo, username, documento, correo, rol asignado, estado (`Activo`/`Inactivo`) y switch de permisos de caja.
  * Botón flotante `+` para registrar nuevo empleado (`POST /usuarios`):
    * Formulario: Nombre, apellido, documento, username, correo, teléfono, contraseña temporal, selector de rol (`Admin` o `Vendedor`) y checkbox *"Puede operar caja"*.
  * Opciones por usuario:
    * Editar datos o restablecer contraseña (`PUT /usuarios/{id}`).
    * Switch *"Activo/Inactivo"* (`PATCH /usuarios/{id}/toggle-estado`): Suspende el acceso del empleado inmediatamente.
    * **Regla de seguridad:** El sistema bloquea automáticamente cualquier intento de desactivar o degradar al único administrador activo.

### Pantalla 3.3: `AdminReturnsApprovalScreen` (Gestión de Devoluciones)
* **Componentes UI:**
  * Bandeja de solicitudes de devolución enviadas por clientes (`GET /devoluciones`).
  * Filtro por pestañas: *Pendientes*, *Aprobadas*, *Rechazadas*.
  * Al abrir una solicitud:
    * Datos del cliente, factura de origen, prenda devuelta, cantidad y motivo declarado.
    * Botón verde **"Aprobar Devolución"**:
      * Consume `POST /devoluciones/{id}/aprobar`.
      * El backend reintegra automáticamente las prendas al stock de la tienda y registra la entrada en el Kardex.
    * Botón rojo **"Rechazar Devolución"**:
      * Abre modal para escribir el motivo formal del rechazo (ej. *"Prenda usada o sin etiquetas originales"*).
      * Consume `POST /devoluciones/{id}/rechazar`.

### Pantalla 3.4: `AdminSuppliersScreen` (Proveedores de Ropa)
* **Componentes UI:**
  * Listado de proveedores registrados con buscador por NIT o nombre (`GET /proveedores`).
  * Formulario de creación y edición (`POST` / `PUT /proveedores/{id}`):
    * Nombre de la empresa proveedora, persona de contacto, NIT/cédula, teléfono, email y dirección física.
  * Opción de eliminar (`DELETE /proveedores/{id}`): El backend valida que no tenga facturas de compra asociadas antes de permitir el borrado.

### Pantalla 3.5: `AdminPurchasesScreen` (Historial y Registro de Compras)
* **Componentes UI:**
  * Historial paginado de compras a proveedores (`GET /compras`) con filtros por fecha y proveedor.
  * Formulario de Nueva Compra en Lote (`POST /compras`):
    * Selección del proveedor.
    * Selector dinámico de prendas donde se añade: Producto, cantidad comprada y costo unitario de adquisición.
    * Totalización automática.
    * Confirmación con procesamiento en transacción SQL atómica.

### Pantalla 3.6: `AdminKardexScreen` (Kardex de Inventario)
* **Componentes UI:**
  * Indicadores de stock (`GET /inventario/kpis`): Total de productos, con stock normal, stock bajo el mínimo y agotados.
  * Tabla cronológica de movimientos de inventario (`GET /inventario`):
    * Fecha y hora.
    * Prenda involucrada.
    * Tipo de movimiento: `Entrada` (por compra a proveedor), `Salida` (por venta en POS/tienda) o `Ajuste` (ajuste manual).
    * Cantidad operada y stock resultante tras el movimiento.

### Pantalla 3.7: `AdminReportsScreen` (Analítica Financiera y Gráficos)
* **Componentes UI:**
  * Selector de rango de fechas: Selector rápido (*Esta semana*, *Este mes*, *Últimos 3 meses*, *Este año*) o selector de calendario personalizado (`desde` y `hasta`).
  * **Tarjetas KPI (`GET /reportes/resumen`):**
    * Total Ventas (número de operaciones).
    * Total Recaudado en Dinero ($).
    * Ticket Promedio por Venta ($).
    * Devoluciones Totales y Monto devuelto ($).
  * **Gráfico de Métodos de Pago (`GET /reportes/metodos-pago`):**
    * Gráfico circular (PieChart) mostrando el porcentaje y monto ingresado por Efectivo vs. Transferencia bancaria.
  * **Ranking Top Productos (`GET /reportes/top-productos`):**
    * Listado ordenado de las 10 prendas más vendidas con unidades colocadas y total de ingresos generados.

---

## 🔌 6. CATÁLOGO EXHAUSTIVO DE LOS 58 ENDPOINTS DE LA API

Todos los endpoints están prefijados por `https://its-fashion.wasmer.app/api`.

### Bloque 1: Conectividad y Autenticación (5 Endpoints)
1. `GET /health-check`: Verifica conectividad y timestamp del servidor.
2. `POST /register`: Registro de cliente. Body: `nombre`, `apellido`, `username`, `telefono`, `email`, `password`, `password_confirmation`.
3. `POST /login`: Autenticación de cualquier rol. Body: `login` (email o username), `password`. Retorna `token` y objeto `user`.
4. `GET /user`: Obtiene datos del perfil y rol del usuario autenticado (requiere Bearer Token).
5. `PUT /user`: Actualiza datos personales y contraseña del usuario autenticado. Body: `nombre`, `apellido`, `telefono`, `current_password` (opcional), `new_password` (opcional), `new_password_confirmation`.
6. `POST /logout`: Invalida y elimina el token Sanctum actual en el servidor.

### Bloque 2: Categorías de Prendas (4 Endpoints)
7. `GET /categories`: Lista todas las categorías con conteo de prendas activas.
8. `POST /categories`: (Admin/Vendedor) Crea categoría. Body: `nombre`, `descripcion`.
9. `PUT /categories/{id}`: (Admin/Vendedor) Actualiza categoría. Body: `nombre`, `descripcion`.
10. `DELETE /categories/{id}`: (Admin/Vendedor) Elimina categoría (si no tiene productos asociados).

### Bloque 3: Catálogo de Productos y Existencias (7 Endpoints)
11. `GET /products`: Catálogo público con query params: `?categoria_id={id}&search={texto}&page={n}`.
12. `GET /products/{id}`: Ficha técnica detallada de una prenda.
13. `POST /products`: (Admin/Vendedor) Crear producto. Body: `nombre`, `descripcion`, `precio_venta`, `precio_compra`, `stock`, `stock_minimo`, `talla`, `color`, `categoria_id`, `imagen` (archivo multipart o URL).
14. `PUT /products/{id}`: (Admin/Vendedor) Editar producto.
15. `DELETE /products/{id}`: (Admin/Vendedor) Eliminar producto.
16. `POST /products/{id}/adjust-stock`: (Admin/Vendedor) Ajuste manual de stock. Body: `tipo` (`entrada` o `salida`), `cantidad` (int > 0), `motivo` (string).
17. `PATCH /products/{id}/toggle-estado`: (Admin/Vendedor) Alterna entre estado `Activo` e `Inactivo`.

### Bloque 4: Ventas, POS y Comprobantes (5 Endpoints)
18. `GET /ventas`: Listado de ventas. Si es Cliente, filtra solo sus compras; si es Vendedor/Admin, lista todas con paginación y filtros.
19. `POST /ventas`: Registrar venta o pedido. Body:
    ```json
    {
      "metodo_pago": "Efectivo", // o "Transferencia bancaria"
      "cliente_id": 15,          // Opcional en POS físico
      "items": [
        {"producto_id": 1, "cantidad": 2, "precio": 89000}
      ]
    }
    ```
20. `GET /ventas/buscar-cliente`: (Admin/Vendedor) Query: `?q={cedula_o_nombre}`. Retorna lista de clientes coincidentes para autocompletar en el POS.
21. `GET /ventas/{id}`: Detalle de venta con productos, precios, cliente y cajero.
22. `GET /ventas/{id}/comprobante`: Retorna estructura JSON completa del ticket fiscal listo para imprimir en térmico o exportar a PDF.

### Bloque 5: Control de Caja y Turnos (4 Endpoints)
23. `GET /caja`: (Admin/Vendedor) Retorna estado actual (`Abierta`/`Cerrada`), monto inicial, ventas en efectivo y balance.
24. `POST /caja/abrir`: (Admin/Vendedor) Apertura de caja. Body: `{"monto_inicial": 100000}`.
25. `POST /caja/cerrar`: (Admin/Vendedor) Cierre de caja. Body: `{"monto_final": 450000, "observaciones": "Cuadre correcto"}`.
26. `POST /caja/movimiento`: (Admin/Vendedor) Movimiento manual. Body: `{"tipo": "Egreso" | "Ingreso", "monto": 20000, "concepto": "Motivo"}`.

### Bloque 6: Directorio de Clientes (5 Endpoints)
27. `GET /clientes`: (Admin/Vendedor) Directorio de clientes con buscador `?search=...`.
28. `POST /clientes`: (Admin/Vendedor) Registro rápido de cliente. Body: `nombre`, `apellido`, `username` (cédula), `telefono`, `email` (opcional).
29. `GET /clientes/{id}`: (Admin/Vendedor) Ficha de cliente.
30. `GET /clientes/{id}/compras`: (Admin/Vendedor) Historial de compras de un cliente específico.
31. `GET /clientes/{id}/devoluciones`: (Admin/Vendedor) Historial de garantías de un cliente específico.
32. `PATCH /clientes/{id}/toggle-estado`: (Solo Admin) Activar o inactivar cliente.

### Bloque 7: Proveedores de Mercancía (5 Endpoints)
33. `GET /proveedores`: (Admin/Vendedor) Lista de proveedores con buscador `?search=...`.
34. `POST /proveedores`: (Admin/Vendedor) Crear proveedor. Body: `nombre`, `contacto`, `telefono`, `email`, `direccion`, `documento`.
35. `GET /proveedores/{id}`: (Admin/Vendedor) Ficha de proveedor y total de compras realizadas.
36. `PUT /proveedores/{id}`: (Admin/Vendedor) Editar proveedor.
37. `DELETE /proveedores/{id}`: (Admin/Vendedor) Eliminar proveedor (valida integridad).

### Bloque 8: Compras y Abastecimiento de Stock (3 Endpoints)
38. `GET /compras`: (Admin/Vendedor) Historial de facturas de compras con proveedor y montos.
39. `POST /compras`: (Admin/Vendedor) Registrar compra en lote:
    ```json
    {
      "proveedor_id": 1,
      "items": [
        {"producto_id": 3, "cantidad": 10, "precio_unitario": 35000.00}
      ]
    }
    ```
40. `GET /compras/{id}`: (Admin/Vendedor) Ver factura de compra y productos recibidos.

### Bloque 9: Inventario y Kardex (2 Endpoints)
41. `GET /inventario`: (Admin/Vendedor) Movimientos cronológicos del Kardex (`?tipo_movimiento=Entrada|Salida&producto_id=...`).
42. `GET /inventario/kpis`: (Admin/Vendedor) KPIs de inventario: `total_productos`, `con_stock`, `stock_critico`, `sin_stock`.

### Bloque 10: Gestión de Usuarios y Empleados (6 Endpoints)
43. `GET /usuarios`: (Solo Admin) Listado de empleados y administradores.
44. `GET /roles`: (Solo Admin) Lista roles asignables (`Admin` id:1, `Vendedor` id:2).
45. `POST /usuarios`: (Solo Admin) Crear usuario interno. Body: `nombre`, `apellido`, `documento`, `username`, `email`, `telefono`, `password`, `rol_id`, `puede_operar_caja`, `estado`.
46. `GET /usuarios/{id}`: (Solo Admin) Ver ficha del usuario.
47. `PUT /usuarios/{id}`: (Solo Admin) Modificar datos, rol o credenciales.
48. `PATCH /usuarios/{id}/toggle-estado`: (Solo Admin) Activar o inactivar empleado.

### Bloque 11: Devoluciones y Garantías (4 Endpoints)
49. `GET /devoluciones`: Listar solicitudes (Cliente ve las suyas; Admin ve todas).
50. `POST /devoluciones`: Radicar solicitud. Body: `venta_id`, `producto_id`, `cantidad`, `motivo`.
51. `POST /devoluciones/{id}/aprobar`: (Solo Admin) Aprueba devolución e incrementa stock en Kardex.
52. `POST /devoluciones/{id}/rechazar`: (Solo Admin) Rechaza solicitud con motivo obligatorio en body: `{"motivo_rechazo": "Prenda lavada"}`.

### Bloque 12: Reportes y Analítica (3 Endpoints)
53. `GET /reportes/resumen`: (Solo Admin) Query: `?desde=YYYY-MM-DD&hasta=YYYY-MM-DD`. Retorna total ventas, dinero recaudado, ticket promedio y devoluciones.
54. `GET /reportes/metodos-pago`: (Solo Admin) Ingresos agrupados por método de pago.
55. `GET /reportes/top-productos`: (Solo Admin) Query: `?limit=10`. Top prendas más vendidas.

### Bloque 13: Dashboard y Notificaciones (3 Endpoints)
56. `GET /dashboard`: Métricas contextuales adaptadas al rol del usuario autenticado.
57. `GET /notificaciones`: Alertas del sistema y de pedidos del usuario.
58. `POST /notificaciones/leer-todas`: Marca todas las alertas como leídas.

---

## 📦 7. MODELOS DE DATOS EN DART (SERIALIZACIÓN JSON)

Copia estos modelos en la carpeta `lib/models/`:

### Modelo de Usuario (`lib/models/user_model.dart`)
```dart
class UserModel {
  final int id;
  final String nombre;
  final String apellido;
  final String username;
  final String email;
  final String telefono;
  final String? documento;
  final int rolId;
  final String rol;
  final String estado;
  final bool puedeOperarCaja;

  UserModel({
    required this.id,
    required this.nombre,
    required this.apellido,
    required this.username,
    required this.email,
    required this.telefono,
    this.documento,
    required this.rolId,
    required this.rol,
    required this.estado,
    this.puedeOperarCaja = false,
  });

  bool get isAdmin => rolId == 1 || rol.toLowerCase() == 'admin';
  bool get isVendedor => rolId == 2 || rol.toLowerCase() == 'vendedor';
  bool get isCliente => rolId == 3 || rol.toLowerCase() == 'cliente';

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      apellido: json['apellido'] ?? '',
      username: json['username'] ?? '',
      email: json['email'] ?? '',
      telefono: json['telefono'] ?? '',
      documento: json['documento'],
      rolId: json['rol_id'] ?? 3,
      rol: json['rol'] is Map ? (json['rol']['descripcion'] ?? 'cliente') : (json['rol'] ?? 'cliente'),
      estado: json['estado'] ?? 'Activo',
      puedeOperarCaja: json['puede_operar_caja'] == 1 || json['puede_operar_caja'] == true,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'nombre': nombre,
    'apellido': apellido,
    'username': username,
    'email': email,
    'telefono': telefono,
    'documento': documento,
    'rol_id': rolId,
    'rol': rol,
    'estado': estado,
    'puede_operar_caja': puedeOperarCaja,
  };
}
```

### Modelo de Producto (`lib/models/producto_model.dart`)
```dart
class ProductoModel {
  final int id;
  final String codigo;
  final String nombre;
  final String? descripcion;
  final double precioVenta;
  final double precioCompra;
  final int stock;
  final int stockMinimo;
  final String talla;
  final String color;
  final int categoriaId;
  final String? categoriaNombre;
  final String estado;
  final String? imagenUrl;

  ProductoModel({
    required this.id,
    required this.codigo,
    required this.nombre,
    this.descripcion,
    required this.precioVenta,
    required this.precioCompra,
    required this.stock,
    required this.stockMinimo,
    required this.talla,
    required this.color,
    required this.categoriaId,
    this.categoriaNombre,
    required this.estado,
    this.imagenUrl,
  });

  bool get isStockCritico => stock > 0 && stock <= stockMinimo;
  bool get isAgotado => stock <= 0;

  factory ProductoModel.fromJson(Map<String, dynamic> json) {
    return ProductoModel(
      id: json['id'] ?? 0,
      codigo: json['codigo'] ?? '',
      nombre: json['nombre'] ?? '',
      descripcion: json['descripcion'],
      precioVenta: double.tryParse(json['precio']?.toString() ?? json['precio_venta']?.toString() ?? '0') ?? 0.0,
      precioCompra: double.tryParse(json['precio_compra']?.toString() ?? '0') ?? 0.0,
      stock: json['stock'] ?? 0,
      stockMinimo: json['stock_minimo'] ?? 0,
      talla: json['talla'] ?? '',
      color: json['color'] ?? '',
      categoriaId: json['categoria_id'] ?? 0,
      categoriaNombre: json['categoria'] is Map ? json['categoria']['nombre'] : null,
      estado: json['estado'] ?? 'Activo',
      imagenUrl: json['imagen_url'] ?? (json['imagen'] != null ? 'https://its-fashion.wasmer.app/storage/productos/${json['imagen']}' : null),
    );
  }
}
```

---

## 🌐 8. SERVICIO HTTP BASE CON INTERCEPTORES EN FLUTTER

Implementa `lib/services/api_service.dart` utilizando `http`:

```dart
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'https://its-fashion.wasmer.app/api';
  static const _storage = FlutterSecureStorage();
  static String? _token;

  // Callback global para forzar logout si el token expira (401)
  static Function()? onUnauthorized;

  static Future<void> init() async {
    _token = await _storage.read(key: 'auth_token');
  }

  static Future<void> setToken(String token) async {
    _token = token;
    await _storage.write(key: 'auth_token', value: token);
  }

  static Future<void> clearAuth() async {
    _token = null;
    await _storage.deleteAll();
  }

  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // Método GET Genérico
  static Future<http.Response> get(String endpoint, {Map<String, String>? queryParams}) async {
    final uri = Uri.parse('$baseUrl$endpoint').replace(queryParameters: queryParams);
    final response = await http.get(uri, headers: _headers);
    _checkUnauthorized(response.statusCode);
    return response;
  }

  // Método POST Genérico
  static Future<http.Response> post(String endpoint, Map<String, dynamic> body) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    final response = await http.post(uri, headers: _headers, body: jsonEncode(body));
    _checkUnauthorized(response.statusCode);
    return response;
  }

  // Método PUT Genérico
  static Future<http.Response> put(String endpoint, Map<String, dynamic> body) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    final response = await http.put(uri, headers: _headers, body: jsonEncode(body));
    _checkUnauthorized(response.statusCode);
    return response;
  }

  // Método PATCH Genérico
  static Future<http.Response> patch(String endpoint, [Map<String, dynamic>? body]) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    final response = await http.patch(uri, headers: _headers, body: body != null ? jsonEncode(body) : null);
    _checkUnauthorized(response.statusCode);
    return response;
  }

  // Método DELETE Genérico
  static Future<http.Response> delete(String endpoint) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    final response = await http.delete(uri, headers: _headers);
    _checkUnauthorized(response.statusCode);
    return response;
  }

  static void _checkUnauthorized(int statusCode) {
    if (statusCode == 401) {
      clearAuth();
      if (onUnauthorized != null) {
        onUnauthorized!();
      }
    }
  }
}
```

---

## 🎯 9. INSTRUCCIONES DE ARRANQUE PARA LA IA DE FLUTTER

Pega este mensaje en tu nuevo chat para que la IA comience la construcción de la aplicación siguiendo este documento paso a paso:

```text
Actúa como un Desarrollador Senior en Flutter. Vamos a construir la aplicación móvil oficial de "It's Fashion" conectada al backend en producción en Wasmer Edge: https://its-fashion.wasmer.app/api.

El proyecto cuenta con una especificación maestra de 58 endpoints y 3 roles diferenciados:
- Rol 3 (Cliente): Catálogo, Carrito, Mis Compras, Solicitud de Devoluciones y Perfil.
- Rol 2 (Vendedor/Cajero): Punto de Venta (POS) rápido, escáner, emisión de ticket térmico, apertura y arqueo de turnos de caja, directorio de clientes y recepción de compras.
- Rol 1 (Administrador): Control total, aprobación/rechazo de garantías, gestión de empleados y roles, proveedores, compras de abastecimiento, Kardex y analítica de reportes con gráficos.

Por favor, lee el documento PROMPT_IMPLEMENTACION_FLUTTER.md adjunto y comencemos implementando la Fase 1:
1. Configuración de dependencias (http, flutter_secure_storage, provider, intl, fl_chart).
2. ApiService con interceptor de token y manejo de error 401.
3. Modelos base (UserModel y ProductoModel).
4. AuthProvider y Pantallas de Login y Registro de Cliente con redirección según rol_id.
```
