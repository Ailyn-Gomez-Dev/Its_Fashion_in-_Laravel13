# 📱 Guía de Integración API REST - It's Fashion (Flutter Móvil)

Documentación técnica de la API REST de **It's Fashion** desarrollada en Laravel y desplegada en alta disponibilidad en **Wasmer Edge**, lista para su consumo directo desde la aplicación móvil en Flutter.

---

## 🌐 Configuración Base

| Parámetro | Valor |
| :--- | :--- |
| **URL Base de la API** | `https://its-fashion.wasmer.app/api` |
| **Formato de datos** | `JSON (application/json)` |
| **Autenticación** | `Bearer Token (Laravel Sanctum)` |
| **Servidor / Hosting** | Wasmer Edge (PHP 8.3 WASI + SSL HTTPS) |

---

## 🔑 Encabezados (Headers) Estándar

Todas las peticiones enviadas desde Flutter deben incluir:

```http
Content-Type: application/json
Accept: application/json
```

Para las rutas protegidas, agregar el token recibido en el login:

```http
Authorization: Bearer <TU_TOKEN_SANCTUM>
```

---

## 🧭 Tabla Resumen de Endpoints

### 1. Conectividad y Autenticación
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/health-check` | Público | Comprueba estado y conectividad del servidor |
| `POST` | `/register` | Público | Registro de nuevos clientes |
| `POST` | `/login` | Público | Inicio de sesión (Admin, Vendedor o Cliente) |
| `GET` | `/user` | Protegido | Obtiene datos del usuario en sesión |
| `POST` | `/logout` | Protegido | Cierra sesión y revoca el token activo |

### 2. Catálogo de Productos y Categorías
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/categories` | Público | Lista todas las categorías con conteo de prendas |
| `GET` | `/products` | Público | Catálogo con filtros (`categoria_id`, `search`, `page`) |
| `GET` | `/products/{id}` | Público | Información detallada de un producto |
| `POST` | `/products` | Admin / Vendedor | Crea un nuevo producto en catálogo |
| `PUT` / `POST` | `/products/{id}` | Admin / Vendedor | Actualiza información del producto |
| `DELETE` | `/products/{id}` | Admin / Vendedor | Elimina un producto |
| `POST` | `/products/{id}/adjust-stock` | Admin / Vendedor | Ajuste manual de stock (entrada/salida) |
| `POST` | `/products/{id}/toggle-estado` | Admin / Vendedor | Activa o inactiva un producto |

### 3. Ventas y Punto de Venta (POS)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/ventas` | Protegido | Historial de compras (Cliente) o ventas (Admin/Vendedor) |
| `GET` | `/ventas/{id}` | Protegido | Detalle de compra y comprobante |
| `POST` | `/ventas` | Protegido | Crea una nueva orden de venta o pedido |

### 4. Control de Caja (Turnos POS)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/caja` | Admin / Vendedor | Consulta el turno de caja actual y saldo |
| `POST` | `/caja/abrir` | Admin / Vendedor | Apertura de turno con monto inicial |
| `POST` | `/caja/cerrar` | Admin / Vendedor | Cierre y arqueo de turno de caja |
| `POST` | `/caja/movimiento` | Admin / Vendedor | Registra entrada o salida manual de dinero |

### 5. Clientes, Devoluciones y Notificaciones
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard` | Protegido | Métricas y KPIs según el rol autenticado |
| `GET` | `/clientes` | Admin / Vendedor | Directorio de clientes |
| `POST` | `/clientes` | Admin / Vendedor | Registra un nuevo cliente |
| `GET` | `/devoluciones` | Protegido | Lista solicitudes de devoluciones |
| `POST` | `/devoluciones` | Protegido | Radica solicitud de devolución |
| `POST` | `/devoluciones/{id}/aprobar` | Solo Admin | Aprueba devolución y reintegra stock |
| `POST` | `/devoluciones/{id}/rechazar`| Solo Admin | Rechaza solicitud de devolución |
| `GET` | `/notificaciones` | Protegido | Lista notificaciones del usuario |
| `POST` | `/notificaciones/leer-todas` | Protegido | Marca notificaciones como leídas |

---

## 📦 Detalle de Payloads y Respuestas

### 1. `GET /api/health-check`
**Respuesta (200 OK):**
```json
{
  "status": "ok",
  "app": "It's Fashion - Laravel API",
  "timestamp": "2026-11-19T15:05:12-05:00"
}
```

---

### 2. `POST /api/register`
**Body (JSON):**
```json
{
  "nombre": "Carolina",
  "apellido": "Herrera",
  "username": "carolina_fashion",
  "telefono": "3109876543",
  "email": "carolina@fashion.com",
  "password": "Password123*",
  "password_confirmation": "Password123*"
}
```
**Respuesta (201 Created):**
```json
{
  "message": "Cuenta creada exitosamente en It's Fashion.",
  "token": "1|sAnCtUmToKeN123456789...",
  "user": {
    "id": 15,
    "nombre": "Carolina",
    "apellido": "Herrera",
    "username": "carolina_fashion",
    "email": "carolina@fashion.com",
    "telefono": "3109876543",
    "rol_id": 3,
    "rol": "cliente",
    "estado": "Activo"
  }
}
```

---

### 3. `POST /api/login`
**Body (JSON):**
```json
{
  "login": "carolina@fashion.com", // O nombre de usuario
  "password": "Password123*"
}
```
**Respuesta (200 OK):**
```json
{
  "message": "Inicio de sesión exitoso.",
  "token": "2|9988776655aAbBcCdD...",
  "user": {
    "id": 15,
    "nombre": "Carolina",
    "apellido": "Herrera",
    "username": "carolina_fashion",
    "email": "carolina@fashion.com",
    "telefono": "3109876543",
    "rol_id": 3,
    "rol": "cliente",
    "estado": "Activo"
  }
}
```

---

### 4. `GET /api/categories`
**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Jeans",
      "descripcion": "Jeans para hombre y mujer",
      "productos_count": 8
    },
    {
      "id": 2,
      "nombre": "Vestidos",
      "descripcion": "Vestidos casuales y de fiesta",
      "productos_count": 5
    }
  ]
}
```

---

### 5. `GET /api/products`
**Query Parameters:**
* `categoria_id`: (Opcional) Filtrar por categoría.
* `search`: (Opcional) Buscar por nombre, código o descripción.
* `page`: (Opcional) Número de página.

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "codigo": "PROD-001",
        "nombre": "Jean Clásico Slim Fit",
        "descripcion": "Denim rígido premium color azul oscuro.",
        "precio": 89000,
        "precio_compra": 45000,
        "stock": 25,
        "stock_minimo": 5,
        "categoria_id": 1,
        "estado": "Activo",
        "imagen_url": "https://its-fashion.wasmer.app/storage/productos/jean.jpg"
      }
    ],
    "total": 35
  }
}
```

---

### 6. `POST /api/ventas`
**Body (JSON):**
```json
{
  "cliente_id": 15,
  "metodo_pago": "Efectivo", // Efectivo, Transferencia, Tarjeta
  "items": [
    {
      "producto_id": 1,
      "cantidad": 2,
      "precio": 89000
    }
  ],
  "observaciones": "Entrega en boutique"
}
```

---

## 🛠️ Servicio Base en Flutter / Dart (`api_service.dart`)

Puedes copiar directamente este servicio dentro de tu proyecto Flutter (`lib/services/api_service.dart`):

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  // URL oficial en producción (Wasmer Edge)
  static const String baseUrl = 'https://its-fashion.wasmer.app/api';
  
  // Almacenar el token en memoria o SecureStorage / SharedPreferences
  static String? authToken;

  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (authToken != null) 'Authorization': 'Bearer $authToken',
  };

  /// 1. Health check de conectividad
  static Future<bool> checkHealth() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/health-check'),
        headers: _headers,
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  /// 2. Inicio de Sesión
  static Future<Map<String, dynamic>> login(String login, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _headers,
      body: jsonEncode({
        'login': login,
        'password': password,
      }),
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['token'] != null) {
      authToken = data['token'];
    }
    return data;
  }

  /// 3. Registro de Cliente
  static Future<Map<String, dynamic>> register({
    required String nombre,
    required String apellido,
    required String username,
    required String telefono,
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/register'),
      headers: _headers,
      body: jsonEncode({
        'nombre': nombre,
        'apellido': apellido,
        'username': username,
        'telefono': telefono,
        'email': email,
        'password': password,
        'password_confirmation': password,
      }),
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 201 && data['token'] != null) {
      authToken = data['token'];
    }
    return data;
  }

  /// 4. Catálogo de Categorías
  static Future<List<dynamic>> getCategories() async {
    final response = await http.get(
      Uri.parse('$baseUrl/categories'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['data'] ?? [];
    }
    return [];
  }

  /// 5. Catálogo de Productos
  static Future<List<dynamic>> getProducts({int? categoryId, String? search}) async {
    final params = <String, String>{};
    if (categoryId != null) params['categoria_id'] = categoryId.toString();
    if (search != null && search.isNotEmpty) params['search'] = search;

    final uri = Uri.parse('$baseUrl/products').replace(queryParameters: params);
    final response = await http.get(uri, headers: _headers);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['data'] is Map && json['data']['data'] != null) {
        return json['data']['data'];
      }
      return json['data'] ?? [];
    }
    return [];
  }

  /// 6. Registrar Venta / Pedido
  static Future<Map<String, dynamic>> createSale({
    required int? clienteId,
    required String metodoPago,
    required List<Map<String, dynamic>> items,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/ventas'),
      headers: _headers,
      body: jsonEncode({
        if (clienteId != null) 'cliente_id': clienteId,
        'metodo_pago': metodoPago,
        'items': items,
      }),
    );

    return jsonDecode(response.body);
  }

  /// 7. Cerrar Sesión
  static Future<void> logout() async {
    if (authToken != null) {
      await http.post(Uri.parse('$baseUrl/logout'), headers: _headers);
      authToken = null;
    }
  }
}
```

---

## 🚦 Códigos de Estado HTTP y Manejo de Errores

| Código | Significado | Explicación para Flutter |
| :---: | :--- | :--- |
| **`200 OK`** | Petición exitosa | La operación se completó correctamente |
| **`201 Created`** | Recurso creado | Registro de usuario o venta exitosa |
| **`401 Unauthorized`** | Token inválido o ausente | Redirigir al usuario a la pantalla de login |
| **`403 Forbidden`** | Sin permisos | El rol del usuario no tiene acceso a esta acción |
| **`422 Unprocessable`**| Error de validación | Mostrar el mapa `errors` en los campos del formulario |
| **`429 Too Many Req`** | Rate Limit | Bloqueo por seguridad tras 5 intentos fallidos de login |
| **`500 Server Error`** | Error de servidor | Notificar fallo de conectividad temporal |
