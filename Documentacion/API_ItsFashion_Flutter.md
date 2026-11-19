# 📱 Guía Oficial de Integración API REST (100% Cobertura) - It's Fashion (Flutter Móvil)

Documentación técnica integral de la API REST de **It's Fashion** desarrollada en Laravel y desplegada en alta disponibilidad en **Wasmer Edge**. Esta API cubre el **100% de las funcionalidades del sistema**, abarcando la tienda del cliente, el punto de venta (POS móvil), y todo el módulo de administración avanzada (usuarios, proveedores, compras y reportes).

---

## 🌐 Configuración Base de Conexión

| Parámetro | Valor de Producción |
| :--- | :--- |
| **URL Base de la API** | `https://its-fashion.wasmer.app/api` |
| **Formato de intercambio**| `JSON (application/json)` |
| **Seguridad / Token** | `Bearer Token (Laravel Sanctum)` |
| **Arquitectura** | RESTful / WebAssembly Edge (Wasmer) |

---

## 🔑 Encabezados (Headers) Obligatorios

Todas las peticiones desde Flutter deben incluir:

```http
Content-Type: application/json
Accept: application/json
```

Para las rutas protegidas, adjuntar el token recibido en `/login` o `/register`:

```http
Authorization: Bearer <TOKEN_SANCTUM>
```

---

## 🧭 Mapeo Completo de Endpoints (51 Rutas)

### 1. Conectividad y Autenticación
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/health-check` | Público | Comprobación de estado y conectividad del servidor |
| `POST` | `/register` | Público | Registro de nuevos clientes |
| `POST` | `/login` | Público | Inicio de sesión (Admin, Vendedor o Cliente) |
| `GET` | `/user` | Protegido | Perfil del usuario autenticado |
| `POST` | `/logout` | Protegido | Cierre de sesión y revocación de token |

---

### 2. Catálogo de Productos y Categorías
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/categories` | Público | Listado de categorías con conteo de prendas |
| `GET` | `/products` | Público | Catálogo con filtros (`categoria_id`, `search`, `page`) |
| `GET` | `/products/{id}` | Público | Ficha técnica y detalle del producto |
| `POST` | `/products` | Admin / Vendedor | Registro de nuevo producto |
| `PUT/POST` | `/products/{id}` | Admin / Vendedor | Edición de producto |
| `DELETE` | `/products/{id}` | Admin / Vendedor | Eliminación de producto |
| `POST` | `/products/{id}/adjust-stock` | Admin / Vendedor | Ajuste manual de existencias (Entrada / Salida) |
| `POST/PATCH`| `/products/{id}/toggle-estado`| Admin / Vendedor | Activar / Inactivar producto |
| `POST` | `/categories` | Admin / Vendedor | Creación de nueva categoría |

---

### 3. Ventas y Punto de Venta (POS Móvil)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/ventas` | Protegido | Historial de compras (Cliente) o ventas (Admin/Vendedor) |
| `GET` | `/ventas/{id}` | Protegido | Detalle de la venta con desglose y comprobante |
| `POST` | `/ventas` | Protegido | Creación de orden de compra o cobro en caja |

---

### 4. Control de Turnos y Caja (POS)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/caja` | Admin / Vendedor | Estado de caja, balance actual y movimientos |
| `POST` | `/caja/abrir` | Admin / Vendedor | Apertura de turno con monto base |
| `POST` | `/caja/cerrar` | Admin / Vendedor | Cierre de caja y arqueo final |
| `POST` | `/caja/movimiento` | Admin / Vendedor | Ingreso o egreso manual de efectivo |

---

### 5. Directorio de Clientes
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/clientes` | Admin / Vendedor | Lista de clientes con opción de búsqueda |
| `POST` | `/clientes` | Admin / Vendedor | Registro rápido de cliente desde POS móvil |
| `GET` | `/clientes/{id}` | Admin / Vendedor | Ficha del cliente y compras |
| `POST/PATCH`| `/clientes/{id}/toggle-estado`| Solo Admin | Activar / Inactivar cliente |

---

### 6. Proveedores de Mercancía
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/proveedores` | Admin / Vendedor | Listado de proveedores de la boutique |
| `POST` | `/proveedores` | Admin / Vendedor | Registrar nuevo proveedor |
| `GET` | `/proveedores/{id}` | Admin / Vendedor | Detalle del proveedor y total de compras |
| `PUT/PATCH` | `/proveedores/{id}` | Admin / Vendedor | Actualizar datos del proveedor |
| `DELETE` | `/proveedores/{id}` | Admin / Vendedor | Eliminar proveedor (si no tiene compras asociadas) |

---

### 7. Compras a Proveedores (Abastecimiento de Stock)
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/compras` | Admin / Vendedor | Historial de compras con proveedor y montos |
| `POST` | `/compras` | Admin / Vendedor | Registrar compra en lote (incrementa stock y Kardex) |
| `GET` | `/compras/{id}` | Admin / Vendedor | Ver desglose de productos de la compra |

---

### 8. Kardex y Movimientos de Inventario
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/inventario` | Admin / Vendedor | Historial de movimientos (Entradas, Salidas, Ajustes) |
| `GET` | `/inventario/kpis` | Admin / Vendedor | Métricas de prendas: Total, Con Stock, Crítico y Sin Stock |

---

### 9. Gestión de Usuarios y Empleados
*(Acceso restringido a Administrador)*

| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/usuarios` | Solo Admin | Listar administradores y vendedores |
| `GET` | `/roles` | Solo Admin | Listar roles disponibles para asignar |
| `POST` | `/usuarios` | Solo Admin | Crear cuenta de empleado/administrador |
| `GET` | `/usuarios/{id}` | Solo Admin | Detalle y permisos del usuario |
| `PUT/PATCH` | `/usuarios/{id}` | Solo Admin | Modificar datos, contraseña o rol |
| `POST/PATCH`| `/usuarios/{id}/toggle-estado`| Solo Admin | Activar o inactivar empleado |

---

### 10. Devoluciones y Garantías
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/devoluciones` | Protegido | Listar solicitudes de devolución |
| `POST` | `/devoluciones` | Protegido | Radicar nueva solicitud de devolución |
| `POST` | `/devoluciones/{id}/aprobar` | Solo Admin | Aprobar devolución (reintegra stock) |
| `POST` | `/devoluciones/{id}/rechazar`| Solo Admin | Rechazar solicitud de devolución |

---

### 11. Reportes y Analítica Comercial
*(Acceso restringido a Administrador)*

| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/reportes/resumen` | Solo Admin | KPIs de ventas, total recaudado y ticket promedio |
| `GET` | `/reportes/metodos-pago` | Solo Admin | Desglose de ingresos por método de pago |
| `GET` | `/reportes/top-productos` | Solo Admin | Top prendas más vendidas |

---

### 12. Notificaciones y Dashboard
| Método | Endpoint | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard` | Protegido | KPIs personalizados según el rol |
| `GET` | `/notificaciones` | Protegido | Listar notificaciones del sistema |
| `POST` | `/notificaciones/leer-todas` | Protegido | Marcar todas como leídas |

---

## 📦 Ejemplos de Payloads para Nuevos Módulos

### Registrar Compra de Mercancía (`POST /api/compras`)
```json
{
  "proveedor_id": 1,
  "items": [
    {
      "producto_id": 1,
      "cantidad": 10,
      "precio_unitario": 45000.00
    },
    {
      "producto_id": 2,
      "cantidad": 5,
      "precio_unitario": 32000.00
    }
  ]
}
```

### Crear Nuevo Empleado (`POST /api/usuarios`)
```json
{
  "nombre": "Andrés",
  "apellido": "Gómez",
  "documento": "1098765432",
  "username": "andres_pos",
  "email": "andres@itsfashion.com",
  "telefono": "3001234567",
  "password": "Password123*",
  "rol_id": 2, // 1 = Admin, 2 = Vendedor
  "puede_operar_caja": true,
  "estado": "Activo"
}
```

### Resumen de Reportes con Fechas (`GET /api/reportes/resumen?desde=2026-11-01&hasta=2026-11-30`)
```json
{
  "success": true,
  "rango": {
    "desde": "2026-11-01",
    "hasta": "2026-11-30"
  },
  "kpis": {
    "total_ventas": 45,
    "total_recaudado": 4250000.00,
    "ticket_promedio": 94444.44,
    "total_devoluciones": 2,
    "monto_devuelto": 120000.00
  }
}
```

---

## 🛠️ Servicio Completo en Dart / Flutter (`api_service.dart`)

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

  // 3. Obtener Productos
  static Future<List<dynamic>> getProducts({int? categoryId, String? search}) async {
    final query = <String, String>{};
    if (categoryId != null) query['categoria_id'] = categoryId.toString();
    if (search != null) query['search'] = search;

    final uri = Uri.parse('$baseUrl/products').replace(queryParameters: query);
    final res = await http.get(uri, headers: _headers);
    if (res.statusCode == 200) {
      final json = jsonDecode(res.body);
      return json['data']['data'] ?? json['data'] ?? [];
    }
    return [];
  }

  // 4. Crear Venta
  static Future<Map<String, dynamic>> createSale({
    int? clienteId,
    required String metodoPago,
    required List<Map<String, dynamic>> items,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/ventas'),
      headers: _headers,
      body: jsonEncode({
        if (clienteId != null) 'cliente_id': clienteId,
        'metodo_pago': metodoPago,
        'items': items,
      }),
    );
    return jsonDecode(res.body);
  }

  // 5. Proveedores
  static Future<List<dynamic>> getSuppliers() async {
    final res = await http.get(Uri.parse('$baseUrl/proveedores'), headers: _headers);
    if (res.statusCode == 200) {
      return jsonDecode(res.body)['data'] ?? [];
    }
    return [];
  }

  // 6. Reporte de Ventas
  static Future<Map<String, dynamic>> getSalesSummary({String? desde, String? hasta}) async {
    final query = <String, String>{};
    if (desde != null) query['desde'] = desde;
    if (hasta != null) query['hasta'] = hasta;

    final uri = Uri.parse('$baseUrl/reportes/resumen').replace(queryParameters: query);
    final res = await http.get(uri, headers: _headers);
    if (res.statusCode == 200) {
      return jsonDecode(res.body);
    }
    return {};
  }
}
```
