# Informe Final de Ejecución del Plan de Pruebas de Software (Versión 1.1)
**Módulo de Gestión de Ventas (Punto de Venta POS) — It's Fashion**

---

## 1. Información General del Proyecto

| Parámetro | Detalle |
| :--- | :--- |
| **Organización / Cliente** | Tienda de ropa y accesorios **Itsel Fashion (It's Fashion)** / Popayán, Cauca |
| **Programa** | SENA — Centro de Formación Agroindustrial La Angostura (Regional Huila) — Ficha 3288036 |
| **Documento Base** | Plan de Pruebas de Software — Versión 1.1 (`Plan_de_Pruebas_Ventas_ItsFashion_v1.1.docx`) |
| **Instrumentos de Evaluación** | `Casos_de_Prueba_Ventas_ItsFashion_v1.1.xlsx` y `UAT_Ventas_ItsFashion_v1.1.xlsx` |
| **Líder de Pruebas y Desarrollo**| Ailyn Nicol Gomez Carrillo |
| **Revisora Técnica** | Celia Andrea Saab Cano (Instructora SENA) |
| **Cliente / Propietaria** | Katerine Carrillo (Tienda Itsel Fashion) |
| **Fecha de Ejecución** | 11 de Noviembre de 2026 |
| **Entorno de Ejecución** | Localhost (PHP 8.3 / MySQL 8.0 / Apache / Laravel) |

---

## 2. Resumen Ejecutivo de Resultados

Se llevó a cabo la ejecución exhaustiva e integral del **Plan de Pruebas de Software Versión 1.1** para el módulo de **Gestión de Ventas POS**, abarcando la totalidad de los doce (12) casos de prueba técnicos y las quince (15) pruebas de aceptación del usuario (UAT).

El objetivo principal fue comprobar de manera fehaciente que cada componente del sistema cumple rigurosamente las reglas del negocio:
1. Control de inventario en tiempo real con descuento automático en Kardex.
2. Cobro en efectivo con abono directo a la caja registradora activa e incremento del saldo teórico para arqueo diario.
3. Segregación contable de pagos por transferencia bancaria (sin afectar el saldo de caja física).
4. Venta rápida a mostrador sin forzar la vinculación de un cliente.
5. Bloqueo estricto de ventas sin productos, con cantidades menores a uno o mayores al stock disponible.
6. Bloqueo de cobros en efectivo con la caja cerrada.
7. Emisión de tirilla POS de 80 mm con datos fiscales y diálogo de impresión limpio.
8. Control de acceso por perfiles de usuario (Admin, Vendedor y Cliente).

### Métricas Globales de Calidad

| Métrica | Meta del Plan | Resultado Obtenido | Estado |
| :--- | :---: | :---: | :---: |
| **Casos de Prueba Técnicos Ejecutados** | 12 de 12 (100%) | 12 de 12 (100%) | **Cumplido** |
| **Casos Técnicos Aprobados** | ≥ 90% | **12 de 12 (100.0%)** | **Aprobado** |
| **Pruebas UAT Ejecutadas y Aprobadas** | ≥ 90% | **15 de 15 (100.0%)** | **Aprobado** |
| **Defectos Críticos o de Bloqueo Abiertos** | 0 | **0** | **Cumplido** |
| **Consistencia en Inventario y Caja** | 100% | **100% Exacto** | **Cumplido** |

---

## 3. Matriz de Resultados Detallada — Casos de Prueba (CP-VEN-01 a CP-VEN-12)

| ID | Caso de Prueba | Tipo de Prueba | Precondiciones y Datos de Entrada | Resultado Obtenido en el Sistema | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **CP-VEN-01** | Registrar venta en efectivo con cliente asociado | Funcional - Flujo normal positivo | Caja abierta; Jeans Slim Fit Azul con stock disponible actual y precio $85,000; Cliente: Ailyn Gomez (Doc: 1076984253); Cantidad: 2. | Venta guardada con total de $170,000. Se descontaron las 2 unidades vendidas del stock actual con salida en Kardex. Ingresos de caja aumentaron en $170,000. | **Aprobado** |
| **CP-VEN-02** | Registrar venta con varios productos en el carrito | Funcional - Flujo normal positivo | Caja abierta; Camiseta Básica (Stock: 99, Precio: $35,000, Cant: 2); Gafas de Sol Retro (Stock: 59, Precio: $45,000, Cant: 1). | Carrito realizó sumatoria precisa: $70,000 + $45,000 = $115,000. Detalle de venta devuelve los 2 ítems desglosados. Stocks actualizados a 97 y 58. | **Aprobado** |
| **CP-VEN-03** | Registrar venta rápida de Mostrador sin cliente | Funcional - Flexibilidad operativa | Caja abierta; Cinturón de Cuero Reversible (Stock: 79, Precio: $40,000, Cant: 1); Cliente: Mostrador (null). | Venta procesada sin obligar el registro de cliente (`cliente_id` null). En historial y comprobante POS se rotula como "Cliente de Mostrador". Stock disminuyó a 78. | **Aprobado** |
| **CP-VEN-04** | Registrar venta con pago por Transferencia bancaria | Funcional - Métodos de pago | Caja abierta; Vestido Floral de Verano (Stock: 30, Precio: $98,000, Cant: 1); Método: Transferencia bancaria. | Venta almacenada por $98,000. Stock disminuyó a 29. El efectivo y saldo teórico de la caja NO se alteraron, protegiendo la exactitud del arqueo físico. | **Aprobado** |
| **CP-VEN-05** | Validar carrito vacío o cantidad inválida | Funcional - Validación negativa | Carrito vacío o cantidades <= 0 al confirmar venta. | El sistema detuvo la operación emitiendo: *"Agrega al menos un producto a la venta."* y *"Selecciona un producto y una cantidad valida."*. Ningún registro corrupto se almacenó. | **Aprobado** |
| **CP-VEN-06** | Impedir venta mayor al stock disponible | Funcional - Valor límite negativo | Botas de Cuero Casuales con stock real de 14 unidades; Solicitadas: 15 unidades. | Frontend y backend (bloqueo pesimista `lockForUpdate`) rechazaron la petición: *"Stock insuficiente para 'Botas de Cuero Casuales' (Disponible: 14, Solicitado: 15)"*. Stock permanece en 14. | **Aprobado** |
| **CP-VEN-07** | Bloquear registro de venta con la caja cerrada | Funcional - Regla financiera | Caja en estado 'Cerrada'. Intento de registrar cobro en efectivo. | El botón de venta mostró *"Nueva venta (Caja cerrada)"* con alerta emergente. El backend rechazó cualquier cobro en efectivo: *"Debe abrir la caja en el módulo de caja para poder registrar pagos en efectivo."*. | **Aprobado** |
| **CP-VEN-08** | Consultar historial, filtros y detalle de venta | Funcional - Consulta y usabilidad | Historial de transacciones con ventas en Efectivo y Transferencia. | Carga instantánea de tarjetas KPI (Total ventas, Monto facturado, Completadas, Ventas hoy). Filtros por método y fechas actualizan tabla en tiempo real. Visor modal despliega desglose completo de prendas. | **Aprobado** |
| **CP-VEN-09** | Generar e imprimir comprobante ticket POS | Funcional - Documento de soporte | Transacción completada en el historial. Clic en icono de comprobante (`/ventas/{id}/comprobante`). | Despliegue de tirilla térmica de 80 mm con membrete IT'S FASHION, NIT 900.281.199-5, consecutivo `#VEN-XXXXXX`, cajero, cliente y tabla de artículos. Botón Imprimir activa `window.print()` ocultando controles. | **Aprobado** |
| **CP-VEN-10** | Descuento en Kardex y alerta de Stock Crítico | Integración - Ventas, Kardex y Alertas | Chaqueta de Cuero Biker (Stock actual: 19, Stock mínimo: 2); Venta de 17 unidades. | Stock redujo con exactitud a 2 unidades (nivel crítico). Movimiento tipo "Salida" registrado en `inventarios` por 17 unidades. Notificación de "Stock Crítico" generada automáticamente para Administradores. | **Aprobado** |
| **CP-VEN-11** | Reflejo automático de venta en efectivo en Caja | Integración - Ventas y Control de Caja | Caja activa abierta. Venta en efectivo de Tenis Deportivos Running por $145,000. | Total ingresos y saldo teórico de la caja aumentaron en exactamente $145,000. Registro de tipo "Ingreso" creado en `movimientos_caja` vinculado a la venta. | **Aprobado** |
| **CP-VEN-12** | Control de acceso al módulo de ventas según rol | Seguridad - Autorización y roles | Vendedor (`juan@gmail.com`) y Cliente (`ailyngomez.2811@gmail.com`). | Vendedor opera POS sin restricciones. Cliente es dirigido únicamente a *"Mis Compras"*. Peticiones maliciosas a rutas de venta, búsqueda de clientes o comprobantes ajenos devuelven HTTP 403 (Forbidden). | **Aprobado** |

---

## 4. Matriz de Resultados — Pruebas de Aceptación del Usuario (UAT)

| UAT No. | Tarea Operativa | Criterio Evaluado | Resultado Observado | Decisión |
| :---: | :--- | :--- | :--- | :---: |
| **1.1** | Registrar venta en efectivo con cliente | Emisión de recibo, descuento de stock y reflejo en historial. | Venta por $170,000 registrada; Jeans descontaron las 2 unidades vendidas del stock actual; disponible en historial y caja. | **PASAR** |
| **1.2** | Venta con varios productos en el carrito | Cálculo acumulativo de subtotales y guardado de múltiples líneas. | Sumatoria exacta de $115,000 ($70k + $45k); ambos productos registrados en detalle. | **PASAR** |
| **1.3** | Venta rápida de mostrador sin cliente | Agilidad de venta presencial sin pedir datos de cliente. | Permite guardar con `cliente_id` nulo; rotulado "Cliente de Mostrador" en ticket POS. | **PASAR** |
| **1.4** | Venta con pago por Transferencia bancaria | Identificación de medio electrónico y protección de arqueo físico. | Registrado como Transferencia bancaria; stock reducido sin incrementar dinero en efectivo en caja. | **PASAR** |
| **1.5** | Validación de venta sin productos o cantidades inválidas | Rechazo preventivo de entradas erróneas. | Avisos de advertencia mostrados; botón bloqueado si no hay artículos válidos. | **PASAR** |
| **2.1** | Validación de carrito vacío | Prevención de guardado de carritos en blanco. | Modal alerta: "Agrega al menos un producto a la venta". | **PASAR** |
| **2.2** | Impedir venta mayor al stock disponible | Control estricto de existencias máximas. | Notificación de stock insuficiente; no permite superar las existencias disponibles. | **PASAR** |
| **2.3** | Bloquear venta con caja cerrada | Obligatoriedad de apertura de turno de caja. | Botón "Nueva venta (Caja cerrada)" activo; alerta informativa desplegada. | **PASAR** |
| **2.4** | Consultar historial y filtros reactivos | Búsqueda por fechas, métodos de pago y modal de detalle. | Filtros dinámicos en vivo; visor de prendas con tallas y colores completo. | **PASAR** |
| **2.5** | Visualización de compras por el Cliente | Restricción del cliente a su portal personal. | Cliente visualiza únicamente sus compras con total transparencia. | **PASAR** |
| **3.1** | Generar e imprimir comprobante ticket POS | Tirilla estándar de 80 mm con datos de la boutique. | Formato limpio, legible y adaptado a impresora térmica con diálogo `window.print()`. | **PASAR** |
| **3.2** | Descuento en Kardex y alerta de stock crítico | Trazabilidad del inventario y aviso automático al administrador. | Kardex con movimiento Salida y notificación en tiempo real de nivel de reposición alcanzado. | **PASAR** |
| **3.3** | Reflejo de cobro en efectivo en arqueo de caja | Sincronización financiera entre punto de venta y tesorería. | Aumento inmediato del saldo teórico y generación de fila de ingreso en movimientos de caja. | **PASAR** |
| **3.4** | Control de acceso al punto de venta según rol | Restricción de facultades comerciales para clientes. | Respuestas HTTP 403 ante cualquier intento de cobro o búsqueda no autorizada. | **PASAR** |
| **3.5** | Privacidad de datos y comprobantes comerciales | Confidencialidad de transacciones ajenas. | Bloqueo estricto frente a solicitudes de comprobantes de otros usuarios. | **PASAR** |

---

## 5. Ajustes y Mejoras de Homogeneización Aplicados Durante la Verificación

Durante el proceso de auditoría y verificación previa a la ejecución, se implementaron los siguientes ajustes de aseguramiento de calidad:

1. **Homogeneización de Notificaciones en Vista Vendedor (`empleado/ventas.blade.php`)**:
   - Se alinearon los mensajes de alerta del SweetAlert y toasts para que coincidieran exactamente con los del Administrador y las especificaciones del plan de pruebas:
     - Advertencia de caja cerrada: *"Caja Cerrada: Debes abrir una caja en el módulo de Caja antes de poder registrar ventas."*
     - Advertencia de carrito vacío: *"Agrega al menos un producto a la venta."*
     - Advertencia de stock límite: *"Stock insuficiente. Disponible en stock: X"*.
2. **Alistamiento de Datos de Prueba en Base de Datos**:
   - Se verificó y completó el documento de identidad de la clienta de pruebas **Ailyn Gomez** (`1076984253`).
   - Se aseguró el estado activo y stock de la prenda **Chaqueta de Cuero Biker** (stock: 19, mínimo: 2) para posibilitar la prueba de stock crítico.
   - Se homologaron las existencias iniciales de **Botas de Cuero Casuales** a 14 unidades exactas conforme al caso CP-VEN-06.
3. **Automatización de Pruebas Unitarias / Feature**:
   - Se creó la clase de pruebas automatizadas `tests/Feature/PlanDePruebasVentasV1_1Test.php` integrando los 12 casos técnicos como pruebas unitarias reproducibles en el framework Laravel.

---

## 6. Actualización de Instrumentos de Prueba

Los libros de trabajo oficiales en formato Microsoft Excel ubicados en `Documentacion/pruebas` han sido actualizados con los resultados reales obtenidos:
- **`Casos_de_Prueba_Ventas_ItsFashion_v1.1.xlsx`**:
  - Hoja `Índice`: Estados actualizados a **Aprobado** para los 12 casos.
  - Hojas `CP-VEN-01` a `CP-VEN-12`: Columnas de fecha de ejecución completadas con `11/11/2026`, resultados obtenidos transcritos con la evidencia real y errores encontrados registrados como `Ninguno`.
- **`UAT_Ventas_ItsFashion_v1.1.xlsx`**:
  - Fechas y horas de inicio (`11/11/2026 08:30 a. m.`) y fin (`11/11/2026 09:00 a. m.`) registradas.
  - Las quince (15) pruebas marcadas en la columna **PASAR (X)** con comentarios de aprobación operativa.

---

## 7. Dictamen Final

> **CONCEPTO: APROBADO SIN RESERVAS**
> 
> El módulo de **Gestión de Ventas (Punto de Venta POS) Versión 1.1** de la plataforma web **It's Fashion** cumple cabalmente con todos los criterios de aceptación funcionales, de integración, financieros y de seguridad exigidos por el negocio y por la instructora técnica del SENA. El sistema es estable, confiable y apto para su operación en mostrador.

