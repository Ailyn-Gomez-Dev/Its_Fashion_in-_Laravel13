# 📱 Guía Oficial de Integración API REST (100% Cobertura Total) - It's Fashion (Flutter Móvil)

Documentación técnica definitiva de la API REST de **It's Fashion** desarrollada en Laravel y desplegada en **Wasmer Edge**. Esta API cubre **el 100% absoluto de cada una de las funcionalidades del sistema** (58 endpoints), garantizando paridad total entre el sistema web y la aplicación móvil en Flutter.

---

## 🌐 Configuración Base de Conexión

| Parámetro | Valor de Producción |
| :--- | :--- |
| **URL Base de la API** | `https://its-fashion.wasmer.app/api` |
| **Formato de datos** | `JSON (application/json)` |
| **Autenticación** | `Bearer Token (Laravel Sanctum)` |
| **Servidor / Hosting** | Wasmer Edge (PHP 8.3 WASI + SSL HTTPS) |

---

## 🔑 Encabezados (Headers) Obligatorios

```http
Content-Type: application/json
Accept: application/json
```

Para las rutas protegidas, incluir el token recibido en el login:

```http
Authorization: Bearer <TOKEN_SANCTUM>
```

---

## 🧭 Mapeo Completo de Endpoints (58 Rutas Oficiales)

### 1. Conectividad y Perfil de Usuario
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/health-check` | Público | Comprobación de conectividad del servidor |
| `POST` | `/register` | Público | Registro de nuevos clientes |
| `POST` | `/login` | Público | Inicio de sesión (Admin, Vendedor o Cliente) |
| `GET` | `/user` | Protegido | Ver perfil del usuario autenticado |
| `PUT/PATCH` | `/user` | Protegido | Actualizar datos personales y cambiar contraseña |
| `POST` | `/logout` | Protegido | Cierre de sesión y revocación de token |

---

### 2. Catálogo de Productos y Categorías (CRUD Completo)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/categories` | Público | Listado de categorías con conteo de productos |
| `POST` | `/categories` | Admin / Vendedor | Crear nueva categoría |
| `PUT/PATCH` | `/categories/{id}` | Admin / Vendedor | Editar categoría existente |
| `DELETE` | `/categories/{id}` | Admin / Vendedor | Eliminar categoría (si no tiene productos) |
| `GET` | `/products` | Público | Catálogo con filtros (`categoria_id`, `search`, `page`) |
| `GET` | `/products/{id}` | Público | Ficha técnica y detalle del producto |
| `POST` | `/products` | Admin / Vendedor | Crear nuevo producto con imágenes |
| `PUT/POST` | `/products/{id}` | Admin / Vendedor | Modificar producto existente |
| `DELETE` | `/products/{id}` | Admin / Vendedor | Eliminar producto |
| `POST` | `/products/{id}/adjust-stock` | Admin / Vendedor | Ajuste manual de existencias (Entrada / Salida) |
| `POST/PATCH`| `/products/{id}/toggle-estado`| Admin / Vendedor | Activar / Inactivar producto |

---

### 3. Ventas y Punto de Venta (POS Móvil)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/ventas` | Protegido | Historial de compras (Cliente) o ventas (Admin/Vendedor) |
| `POST` | `/ventas` | Protegido | Registrar nueva venta o compra de cliente |
| `GET` | `/ventas/buscar-cliente` | Admin / Vendedor | Buscador rápido de clientes por cédula o nombre en POS |
| `GET` | `/ventas/{id}` | Protegido | Ver detalle completo de una venta |
| `GET` | `/ventas/{id}/comprobante` | Protegido | Datos estructurados del ticket para impresión térmica / PDF |

---

### 4. Control de Turnos y Movimientos de Caja (POS)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/caja` | Admin / Vendedor | Estado de caja, balance actual y saldo en efectivo |
| `POST` | `/caja/abrir` | Admin / Vendedor | Apertura de turno con monto base |
| `POST` | `/caja/cerrar` | Admin / Vendedor | Cierre de caja y arqueo final |
| `POST` | `/caja/movimiento` | Admin / Vendedor | Ingreso o egreso manual de efectivo |

---

### 5. Directorio de Clientes
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/clientes` | Admin / Vendedor | Listado de clientes registrados con buscador |
| `POST` | `/clientes` | Admin / Vendedor | Registro rápido de cliente desde POS móvil |
| `GET` | `/clientes/{id}` | Admin / Vendedor | Ficha del cliente |
| `GET` | `/clientes/{id}/compras` | Admin / Vendedor | Historial completo de compras del cliente |
| `GET` | `/clientes/{id}/devoluciones` | Admin / Vendedor | Historial de devoluciones solicitadas por el cliente |
| `POST/PATCH`| `/clientes/{id}/toggle-estado`| Solo Admin | Activar / Inactivar cliente |

---

### 6. Proveedores de Mercancía
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/proveedores` | Admin / Vendedor | Listado de proveedores de la boutique |
| `POST` | `/proveedores` | Admin / Vendedor | Registrar nuevo proveedor |
| `GET` | `/proveedores/{id}` | Admin / Vendedor | Ficha del proveedor y conteo de compras |
| `PUT/PATCH` | `/proveedores/{id}` | Admin / Vendedor | Actualizar información del proveedor |
| `DELETE` | `/proveedores/{id}` | Admin / Vendedor | Eliminar proveedor (si no tiene compras) |

---

### 7. Compras a Proveedores (Abastecimiento de Stock)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/compras` | Admin / Vendedor | Historial de compras a proveedores con montos |
| `POST` | `/compras` | Admin / Vendedor | Registrar compra en lote (incrementa stock y Kardex) |
| `GET` | `/compras/{id}` | Admin / Vendedor | Ver desglose de productos de la compra |

---

### 8. Kardex y Control de Inventario
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/inventario` | Admin / Vendedor | Movimientos del Kardex (Entradas, Salidas, Ajustes) |
| `GET` | `/inventario/kpis` | Admin / Vendedor | Métricas de prendas: Total, Con Stock, Crítico y Agotadas |

---

### 9. Gestión de Usuarios y Empleados
*(Acceso restringido a Administrador)*

| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/usuarios` | Solo Admin | Listar administradores y vendedores |
| `GET` | `/roles` | Solo Admin | Listar roles disponibles para asignar |
| `POST` | `/usuarios` | Solo Admin | Crear cuenta de empleado o administrador |
| `GET` | `/usuarios/{id}` | Solo Admin | Ficha técnica y permisos del usuario |
| `PUT/PATCH` | `/usuarios/{id}` | Solo Admin | Modificar datos personales, rol o contraseña |
| `POST/PATCH`| `/usuarios/{id}/toggle-estado`| Solo Admin | Activar o inactivar empleado |

---

### 10. Devoluciones y Garantías
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/devoluciones` | Protegido | Listar solicitudes de devolución |
| `POST` | `/devoluciones` | Protegido | Radicar nueva solicitud de devolución |
| `POST` | `/devoluciones/{id}/aprobar` | Solo Admin | Aprobar devolución (reintegra stock al inventario) |
| `POST` | `/devoluciones/{id}/rechazar`| Solo Admin | Rechazar solicitud de devolución |

---

### 11. Reportes y Analítica Comercial
*(Acceso restringido a Administrador)*

| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/reportes/resumen` | Solo Admin | KPIs de ventas, total recaudado y devoluciones por fecha |
| `GET` | `/reportes/metodos-pago` | Solo Admin | Desglose de ingresos por método de pago |
| `GET` | `/reportes/top-productos` | Solo Admin | Top prendas con mayor volumen de ventas |

---

### 12. Notificaciones y Dashboard
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard` | Protegido | Métricas y accesos directos según el rol |
| `GET` | `/notificaciones` | Protegido | Listar notificaciones del sistema |
| `POST` | `/notificaciones/leer-todas` | Protegido | Marcar todas las notificaciones como leídas |

---

## 🛠️ Servicio Base en Flutter / Dart (`api_service.dart`)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'https://its-fashion.wasmer.app/api';
  static String? authToken;

  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (authToken != null) 'Authorization': 'Bearer $authToken',
  };

  // 1. Health check
  static Future<bool> checkHealth() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/health-check'), headers: _headers);
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 2. Login
  static Future<Map<String, dynamic>> login(String login, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: _headers,
      body: jsonEncode({'login': login, 'password': password}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['token'] != null) {
      authToken = data['token'];
    }
    return data;
  }

  // 3. Actualizar Perfil de Usuario
  static Future<Map<String, dynamic>> updateProfile({
    required String nombre,
    required String apellido,
    required String telefono,
    String? currentPassword,
    String? newPassword,
  }) async {
    final body = <String, dynamic>{
      'nombre': nombre,
      'apellido': apellido,
      'telefono': telefono,
    };
    if (newPassword != null && newPassword.isNotEmpty) {
      body['current_password'] = currentPassword;
      body['new_password'] = newPassword;
      body['new_password_confirmation'] = newPassword;
    }

    final res = await http.put(Uri.parse('$baseUrl/user'), headers: _headers, body: jsonEncode(body));
    return jsonDecode(res.body);
  }

  // 4. Buscar cliente para POS
  static Future<List<dynamic>> searchClient(String query) async {
    final res = await http.get(Uri.parse('$baseUrl/ventas/buscar-cliente?q=$query'), headers: _headers);
    if (res.statusCode == 200) {
      return jsonDecode(res.body)['data'] ?? [];
    }
    return [];
  }

  // 5. Obtener Comprobante / Ticket de Venta
  static Future<Map<String, dynamic>> getReceipt(int ventaId) async {
    final res = await http.get(Uri.parse('$baseUrl/ventas/$ventaId/comprobante'), headers: _headers);
    if (res.statusCode == 200) {
      return jsonDecode(res.body)['ticket'] ?? {};
    }
    return {};
  }
}
```
