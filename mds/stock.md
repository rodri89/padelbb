# 📊 Estructura de Base de Datos - Sistema de Gestión de Torneo de Pádel

## 📋 Índice
1. [Descripción General](#descripción-general)
2. [Diagrama de Entidades](#diagrama-de-entidades)
3. [Tablas Principales](#tablas-principales)
4. [Relaciones entre Tablas](#relaciones-entre-tablas)
5. [Vistas Útiles](#vistas-útiles)
6. [Querys Comunes](#querys-comunes)
7. [Flujo de Datos](#flujo-de-datos)

---

## 📌 Descripción General

Este sistema está diseñado para gestionar:
- **Stock de Productos**: Bebidas, snacks, equipamiento y accesorios
- **Ventas Diarias**: Registro completo de cada venta realizada
- **Métodos de Pago**: Efectivo y Transferencia
- **Estado de Pago**: Pagado o Pendiente
- **Ubicación**: Por cancha (3 disponibles)
- **Horario**: Por turno asignado (hasta 8 turnos por día)
- **Clientes**: Información completa de quien compra

---

## 🏗️ Diagrama de Entidades

```
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA DE VENTAS                        │
└─────────────────────────────────────────────────────────────┘

CONFIGURACIÓN DEL TORNEO:
    └── CANCHAS (3 canchas disponibles)

GESTIÓN DE STOCK:
    ├── CATEGORIAS_PRODUCTOS
    ├── PRODUCTOS
    └── MOVIMIENTOS_STOCK (historial de cambios)

VENTAS:
    ├── VENTAS (transacciones principales - con cliente y turno como texto)
    ├── DETALLES_VENTA (productos por venta)
    ├── HISTORIAL_PAGOS (registro de pagos)
    └── AUDITORIA (log de cambios)
```

---

## 📊 Tablas Principales

### 1️⃣ **CANCHAS**
Almacena las 3 canchas del torneo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_cancha` | INT | Identificador único (PK) |
| `nombre` | VARCHAR(50) | Nombre de la cancha (Cancha 1, 2, 3) |
| `descripcion` | VARCHAR(255) | Tipo de superficie o detalles |
| `activa` | BOOLEAN | Estado de la cancha |
| `created_at` | TIMESTAMP | Fecha de creación |

**Datos típicos:**
- Cancha 1, Cancha 2, Cancha 3

---

### 3️⃣ **CATEGORIAS_PRODUCTOS**
Clasificación de productos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_categoria` | INT | Identificador único (PK) |
| `nombre` | VARCHAR(100) | Nombre de categoría |
| `descripcion` | VARCHAR(255) | Descripción |
| `activa` | BOOLEAN | Estado |
| `created_at` | TIMESTAMP | Fecha de creación |

**Categorías típicas:**
- Bebidas
- Snacks
- Equipamiento
- Accesorios

---

### 4️⃣ **PRODUCTOS**
Inventario de productos disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_producto` | INT | Identificador único (PK) |
| `nombre` | VARCHAR(100) | Nombre del producto |
| `descripcion` | VARCHAR(255) | Descripción detallada |
| `id_categoria` | INT | Categoría (FK) |
| `precio_unitario` | DECIMAL(10,2) | Precio por unidad |
| `stock_actual` | INT | Cantidad disponible |
| `stock_minimo` | INT | Cantidad mínima de reorden |
| `activo` | BOOLEAN | Disponible para venta |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

**Ejemplo:**
- Agua embotellada 500ml: $50.00, Stock: 20 unidades
- Pelota de pádel (Pack 3): $450.00, Stock: 8 packs

---

### 5️⃣ **VENTAS** ⭐ TABLA PRINCIPAL
Registro de cada transacción de venta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_venta` | INT | Identificador único (PK) |
| `nombre_cliente` | VARCHAR(100) | Nombre del cliente (texto simple) |
| `nombre_turno` | VARCHAR(50) | Nombre del turno (texto simple) |
| `id_cancha` | INT | Cancha donde se realiza (FK) |
| `fecha_venta` | DATE | Fecha de la venta |
| `hora_venta` | TIME | Hora exacta de la venta |
| `precio_total` | DECIMAL(10,2) | Monto total |
| `metodo_pago` | ENUM | 'efectivo' o 'transferencia' |
| `estado_pago` | ENUM | 'pagado' o 'pendiente' |
| `fecha_pago` | DATE | Cuándo se pagó |
| `referencia_pago` | VARCHAR(100) | Ref. de transferencia o comprobante |
| `notas` | VARCHAR(255) | Observaciones |
| `created_at` | TIMESTAMP | Fecha de creación del registro |
| `updated_at` | TIMESTAMP | Última modificación |

**Ejemplo de registro:**
```
ID: 1
Cliente: Juan Pérez
Cancha: Cancha 1
Turno: Turno 3 (11:00-12:30)
Fecha: 2024-01-15
Precio Total: $520.00
Método Pago: Transferencia
Estado: Pendiente
Referencia: TRF-001-15012024
```

---

### 5️⃣ **VENTAS** ⭐ TABLA PRINCIPAL
Registro de cada transacción de venta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_venta` | INT | Identificador único (PK) |
| `nombre_cliente` | VARCHAR(100) | Nombre del cliente (texto simple) |
| `nombre_turno` | VARCHAR(50) | Nombre del turno (texto simple) |
| `id_cancha` | INT | Cancha donde se realiza (FK) |
| `fecha_venta` | DATE | Fecha de la venta |
| `hora_venta` | TIME | Hora exacta de la venta |
| `precio_total` | DECIMAL(10,2) | Monto total |
| `metodo_pago` | ENUM | 'efectivo' o 'transferencia' |
| `estado_pago` | ENUM | 'pagado' o 'pendiente' |
| `fecha_pago` | DATE | Cuándo se pagó |
| `referencia_pago` | VARCHAR(100) | Ref. de transferencia o comprobante |
| `notas` | VARCHAR(255) | Observaciones |
| `created_at` | TIMESTAMP | Fecha de creación del registro |
| `updated_at` | TIMESTAMP | Última modificación |

**Ejemplo de registro:**
```
ID: 1
Cliente: Juan Pérez
Cancha: Cancha 1
Turno: Turno 3 (11:00-12:30)
Fecha: 2024-01-15
Precio Total: $520.00
Método Pago: Transferencia
Estado: Pendiente
Referencia: TRF-001-15012024
```

---

### 6️⃣ **MOVIMIENTOS_STOCK**
Historial de todos los cambios en el stock.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_movimiento` | INT | Identificador único (PK) |
| `id_producto` | INT | Producto (FK) |
| `tipo_movimiento` | ENUM | 'entrada', 'salida', 'ajuste' |
| `cantidad` | INT | Cantidad movida |
| `cantidad_anterior` | INT | Stock antes del movimiento |
| `cantidad_nueva` | INT | Stock después del movimiento |
| `motivo` | VARCHAR(255) | Razón del movimiento |
| `usuario_responsable` | VARCHAR(100) | Quién realizó el movimiento |
| `created_at` | TIMESTAMP | Fecha del movimiento |

**Tipos de movimientos:**
- **Entrada**: Compra de stock nuevo
- **Salida**: Venta (automático desde ventas)
- **Ajuste**: Correcciones por faltantes, roturas, etc.

---

### 7️⃣ **DETALLES_VENTA**
Desglose de productos por cada venta (relación muchos a muchos).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_detalle` | INT | Identificador único (PK) |
| `id_venta` | INT | Venta asociada (FK) |
| `id_producto` | INT | Producto vendido (FK) |
| `cantidad` | INT | Cantidad vendida |
| `precio_unitario` | DECIMAL(10,2) | Precio en ese momento |
| `subtotal` | DECIMAL(10,2) | cantidad × precio_unitario |
| `created_at` | TIMESTAMP | Fecha del movimiento |

**Ejemplo:**
```
Venta ID 1 contiene:
- Agua 500ml: 2 × $50.00 = $100.00
- Bebida isotónica: 1 × $120.00 = $120.00
- Grip raqueta: 2 × $150.00 = $300.00
Total: $520.00
```

---

### 8️⃣ **HISTORIAL_PAGOS**
Registro detallado de cada pago realizado.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_pago` | INT | Identificador único (PK) |
| `id_venta` | INT | Venta asociada (FK) |
| `monto_pagado` | DECIMAL(10,2) | Monto pagado |
| `metodo_pago` | ENUM | 'efectivo' o 'transferencia' |
| `fecha_pago` | TIMESTAMP | Cuándo se pagó |
| `referencia_pago` | VARCHAR(100) | Comprobante o referencia |
| `usuario_responsable` | VARCHAR(100) | Quién procesó el pago |
| `notas` | VARCHAR(255) | Observaciones |

**Utilidad:**
- Dejar constancia de cuándo se pagó realmente
- Seguimiento de transferencias pendientes
- Reportes de flujo de caja

---

### 9️⃣ **AUDITORIA**
Log de todos los cambios en el sistema (para control y seguridad).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_auditoria` | INT | Identificador único (PK) |
| `tabla_afectada` | VARCHAR(50) | Tabla modificada |
| `id_registro` | INT | ID del registro modificado |
| `accion` | VARCHAR(50) | INSERT, UPDATE, DELETE |
| `usuario` | VARCHAR(100) | Quién hizo el cambio |
| `valores_anteriores` | JSON | Valores antes (UPDATE) |
| `valores_nuevos` | JSON | Valores después |
| `ip_address` | VARCHAR(45) | IP de origen |
| `created_at` | TIMESTAMP | Cuándo ocurrió |

---

## 🔗 Relaciones entre Tablas

```
CANCHAS (1) ──────→ (N) VENTAS
VENTAS (1) ──────→ (N) DETALLES_VENTA
PRODUCTOS (1) ──────→ (N) DETALLES_VENTA
PRODUCTOS (1) ──────→ (N) MOVIMIENTOS_STOCK
CATEGORIAS (1) ──────→ (N) PRODUCTOS
VENTAS (1) ──────→ (N) HISTORIAL_PAGOS
```

---

## 👁️ Vistas Útiles

### Vista 1: VENTAS_PENDIENTES
Muestra todas las ventas que aún no han sido pagadas.

```sql
SELECT 
    id_venta,
    nombre_cliente,
    cancha,
    nombre_turno,
    fecha_venta,
    precio_total,
    metodo_pago,
    dias_pendiente
FROM ventas_pendientes
ORDER BY fecha_venta ASC;
```

**Resultado esperado:**
```
ID  | Cliente        | Cancha    | Turno       | Fecha      | Monto  | Método        | Días Pendiente
--- | -------------- | --------- | ----------- | ---------- | ------ | ------------- | ---------------
1   | Juan Pérez     | Cancha 1  | Turno 3     | 2024-01-15 | $520.0 | Transferencia | 5
2   | María García   | Cancha 2  | Turno 5     | 2024-01-16 | $350.0 | Transferencia | 4
```

---

### Vista 2: VENTAS_POR_CANCHA_TURNO
Resumen de ventas agrupadas por cancha y turno.

```sql
SELECT * FROM ventas_por_cancha_turno;
```

**Utilidad:**
- Ver qué cancha y turno vende más
- Analizar patrones de demanda
- Identificar momentos pico

---

### Vista 3: STOCK_DISPONIBLE
Estado actual de todos los productos con nivel de alerta.

```sql
SELECT * FROM stock_disponible
WHERE nivel_stock != 'BUENO'
ORDER BY stock_actual ASC;
```

**Resultado:**
```
Producto          | Categoría | Precio | Stock | Mínimo | Nivel
--- | --- | --- | --- | --- | ---
Muñequera deportiva | Accesorios | $100.0 | 5 | 2 | MEDIO
Pelota de pádel | Equipamiento | $450.0 | 8 | 2 | BUENO
```

---

### Vista 4: RESUMEN_VENTAS_DIARIAS
Resumen financiero diario.

```sql
SELECT * FROM resumen_ventas_diarias
WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

**Resultado:**
```
Fecha      | Transacciones | Clientes | Monto Total | Efectivo | Transferencia | Pagado | Pendiente
--- | --- | --- | --- | --- | --- | --- | ---
2024-01-16 | 5 | 4 | $1,820.0 | $520.0 | $1,300.0 | $470.0 | $1,350.0
2024-01-15 | 3 | 3 | $890.0 | $340.0 | $550.0 | $890.0 | $0.0
```

---

## 🔍 Querys Comunes

### 📈 1. Ventas de un día específico
```sql
SELECT 
    v.id_venta,
    CONCAT(c.nombre, ' ', c.apellido) as cliente,
    ca.nombre as cancha,
    t.nombre as turno,
    v.precio_total,
    v.metodo_pago,
    v.estado_pago
FROM ventas v
JOIN clientes c ON v.id_cliente = c.id_cliente
JOIN canchas ca ON v.id_cancha = ca.id_cancha
JOIN turnos t ON v.id_turno = t.id_turno
WHERE v.fecha_venta = '2024-01-15'
ORDER BY v.hora_venta;
```

---

### 💰 2. Total de ventas por método de pago (hoy)
```sql
SELECT 
    metodo_pago,
    COUNT(*) as cantidad,
    SUM(precio_total) as total
FROM ventas
WHERE fecha_venta = CURDATE()
GROUP BY metodo_pago;
```

---

### ⏳ 3. Ventas pendientes de pago (con cliente)
```sql
SELECT 
    v.id_venta,
    CONCAT(c.nombre, ' ', c.apellido) as cliente,
    c.email,
    c.telefono,
    v.precio_total,
    v.metodo_pago,
    v.fecha_venta,
    DATEDIFF(NOW(), v.fecha_venta) as dias_sin_pagar
FROM ventas v
JOIN clientes c ON v.id_cliente = c.id_cliente
WHERE v.estado_pago = 'pendiente'
ORDER BY v.fecha_venta ASC;
```

---

### 📦 4. Productos con stock bajo
```sql
SELECT 
    p.nombre,
    cp.nombre as categoria,
    p.stock_actual,
    p.stock_minimo,
    p.precio_unitario
FROM productos p
JOIN categorias_productos cp ON p.id_categoria = cp.id_categoria
WHERE p.stock_actual <= p.stock_minimo
ORDER BY p.stock_actual ASC;
```

---

### 🏆 5. Clientes que más han comprado
```sql
SELECT 
    CONCAT(c.nombre, ' ', c.apellido) as cliente,
    COUNT(v.id_venta) as cantidad_compras,
    SUM(v.precio_total) as monto_total,
    MAX(v.fecha_venta) as ultima_compra
FROM clientes c
JOIN ventas v ON c.id_cliente = v.id_cliente
GROUP BY c.id_cliente
ORDER BY monto_total DESC
LIMIT 10;
```

---

### 📊 6. Ventas por cancha (últimos 30 días)
```sql
SELECT 
    ca.nombre as cancha,
    COUNT(v.id_venta) as cantidad_ventas,
    SUM(v.precio_total) as monto_total,
    AVG(v.precio_total) as promedio_venta
FROM ventas v
JOIN canchas ca ON v.id_cancha = ca.id_cancha
WHERE v.fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY ca.id_cancha
ORDER BY monto_total DESC;
```

---

### 💵 7. Historial de movimientos de un producto específico
```sql
SELECT 
    m.id_movimiento,
    m.tipo_movimiento,
    m.cantidad,
    m.cantidad_anterior,
    m.cantidad_nueva,
    m.motivo,
    m.usuario_responsable,
    m.created_at
FROM movimientos_stock m
WHERE m.id_producto = 1
ORDER BY m.created_at DESC;
```

---

## 🔄 Flujo de Datos

### Flujo de una Venta Completa:

```
1. CREAR CLIENTE (si no existe)
   └─ INSERT en tabla CLIENTES

2. CREAR VENTA
   └─ INSERT en tabla VENTAS
      - id_cliente: seleccionar cliente
      - id_cancha: seleccionar cancha
      - id_turno: seleccionar turno
      - fecha_venta: HOY
      - hora_venta: AHORA
      - metodo_pago: EFECTIVO o TRANSFERENCIA
      - estado_pago: PENDIENTE (por defecto)
      - precio_total: $0 (se calcula después)

3. AGREGAR PRODUCTOS A LA VENTA
   ├─ INSERT en tabla DETALLES_VENTA (por cada producto)
   │  - id_venta: ID de la venta creada
   │  - id_producto: producto seleccionado
   │  - cantidad: cantidad comprada
   │  - precio_unitario: precio actual del producto
   │  - subtotal: cantidad × precio_unitario
   │
   └─ UPDATE tabla PRODUCTOS (restar stock)
      - stock_actual = stock_actual - cantidad
      - INSERT en MOVIMIENTOS_STOCK (tipo: salida)

4. CALCULAR TOTAL
   └─ UPDATE tabla VENTAS
      - precio_total = SUM(detalles_venta.subtotal)

5. PROCESAR PAGO
   ├─ UPDATE tabla VENTAS
   │  - estado_pago: PAGADO
   │  - fecha_pago: HOY
   │
   └─ INSERT en tabla HISTORIAL_PAGOS
      - monto_pagado: precio_total
      - metodo_pago: confirmado
      - fecha_pago: HOY
      - usuario_responsable: usuario actual
```

---

## 🎯 Campos Clave para Funcionalidades

### ✅ Para saber si una venta está pagada:
- `ventas.estado_pago` = 'pagado'
- `ventas.estado_pago` = 'pendiente'

### ✅ Para filtrar por método de pago:
- `ventas.metodo_pago` = 'efectivo'
- `ventas.metodo_pago` = 'transferencia'

### ✅ Para ubicar una venta en el torneo:
- `ventas.id_cancha` → nombre de cancha
- `ventas.id_turno` → horario de turno
- `ventas.fecha_venta` → día del evento

### ✅ Para rastrear el inventario:
- `productos.stock_actual` → cantidad disponible
- `movimientos_stock.*` → historial completo

---

## 🛡️ Integridad de Datos

Todas las relaciones tienen `FOREIGN KEY` configuradas para garantizar:
- ✅ No se puede vender un producto inexistente
- ✅ No se puede asignar una venta a una cancha que no existe
- ✅ No se puede borrar una categoría que tiene productos
- ✅ Los datos se mantienen consistentes

---

## 📝 Notas Finales

1. **Stock Automático**: Cuando se crea un DETALLE_VENTA, el stock del producto se resta automáticamente (mediante un TRIGGER en la BD)

2. **Auditoría**: Todos los cambios importantes quedan registrados en AUDITORIA para trazabilidad

3. **Búsquedas Rápidas**: Las columnas importantes tienen INDEX para consultas eficientes

4. **Escalabilidad**: La estructura permite crecer fácilmente (agregar canchas, productos, etc.)

5. **Reportes**: Las VISTAS hacen fácil generar reportes sin escribir querys complejas

---

**Documento generado**: Enero 2024
**Versión**: 1.0