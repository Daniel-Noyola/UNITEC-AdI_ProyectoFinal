# Catálogo de Consultas SQL Puras — Food Manager

Este documento reúne, organiza y agrupa todas las consultas SQL ejecutadas en los archivos del directorio [`db/`](../db/), presentadas en **SQL puro** (listas para ejecutarse directamente en MySQL / MariaDB sobre la base de datos `food_manager`).

---

## Índice de Organización

1. [Parte 1 — Consultas Simples por Tabla](#parte-1--consultas-simples-por-tabla)
   - [1.1 Tabla `usuarios`](#11-tabla-usuarios)
   - [1.2 Tabla `mesas`](#12-tabla-mesas)
   - [1.3 Tabla `platillos`](#13-tabla-platillos)
   - [1.4 Tabla `ingredientes`](#14-tabla-ingredientes)
   - [1.5 Tabla `recetas`](#15-tabla-recetas)
   - [1.6 Tabla `pedidos`](#16-tabla-pedidos)
   - [1.7 Tabla `detalle_pedido`](#17-tabla-detalle_pedido)
2. [Parte 2 — Consultas Compuestas](#parte-2--consultas-compuestas-por-complejidad)
   - [2.1 Básico: `JOIN` de 2 tablas](#21-básico-join-de-2-tablas)
   - [2.2 Intermedio: `JOIN` múltiples (3 tablas) y filtros combinados](#22-intermedio-join-múltiples-3-tablas-y-filtros-combinados)
   - [2.3 Avanzado: Agregaciones, `GROUP BY`, Rankings y Reportes Analíticos](#23-avanzado-agregaciones-group-by-rankings-y-reportes-analíticos)
3. [Parte 3 — Agrupación por Resultado Obtenido y Transacciones](#parte-3--agrupación-por-resultado-obtenido-y-transacciones)
   - [3.1 Resultado: KPIs del Dashboard (`getDashboardKpis`)](#31-resultado-kpis-del-dashboard-getdashboardkpis)
   - [3.2 Resultado: Reportes Administrativos (`db/reportes/acciones.php`)](#32-resultado-reportes-administrativos-dbreportesaccionesphp)
   - [3.3 Transacción: Guardar Receta de un Platillo (`saveRecipe`)](#33-transacción-guardar-receta-de-un-platillo-saverecipe)
   - [3.4 Transacción: Crear Pedido con Validación y Descuento de Inventario (`createOrder`)](#34-transacción-crear-pedido-con-validación-y-descuento-de-inventario-createorder)
   - [3.5 Transacción: Actualizar Estado de Pedido, Reponer Stock y Sincronizar Mesa (`updateOrderStatus`)](#35-transacción-actualizar-estado-de-pedido-reponer-stock-y-sincronizar-mesa-updateorderstatus)
   - [3.6 Transacción: Reiniciar Base de Datos y Cargar Seeder (`resetDatabaseAndRunSeeder`)](#36-transacción-reiniciar-base-de-datos-y-cargar-seeder-resetdatabaseandrunseeder)

---

## Parte 1 — Consultas Simples por Tabla

### 1.1 Tabla `usuarios`

* `Baja` **Verificar existencia de correo** (`db/usuarios/acciones.php` → `emailExists`, `db/create_admin.php`)
```sql
SELECT id_usuario
FROM usuarios
WHERE correo = 'cliente@ejemplo.com'
LIMIT 1;
```

* `Baja` **Obtener usuario completo para autenticación** (`db/usuarios/acciones.php` → `authenticateUser`)
```sql
SELECT *
FROM usuarios
WHERE correo = 'cliente@ejemplo.com'
LIMIT 1;
```

* `Media` **Registrar nuevo usuario** (`db/usuarios/acciones.php` → `registerUser`)
```sql
INSERT INTO usuarios (nombre, correo, contrasena_hash, telefono, rol, fecha_registro)
VALUES ('Carlos López', 'carlos@ejemplo.com', '$2y$10$hash_bcrypt_ejemplo', '5551234567', 'cliente', NOW());
```

* `Media` **Crear administrador inicial desde CLI** (`db/create_admin.php`)
```sql
INSERT INTO usuarios (nombre, correo, contrasena_hash, telefono, rol, fecha_registro)
VALUES ('Administrador', 'admin@foodmanager.local', '$2y$10$hash_bcrypt_ejemplo', NULL, 'admin', NOW());
```

* `Media` **Promover usuario existente a administrador** (`db/create_admin.php`)
```sql
UPDATE usuarios
SET nombre = 'Administrador',
    contrasena_hash = '$2y$10$hash_bcrypt_ejemplo',
    telefono = '5550000000',
    rol = 'admin'
WHERE id_usuario = 1;
```

* `Media` **Eliminar usuarios conservando al administrador activo** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `usuarios`
WHERE `id_usuario` != 1;
```

---

### 1.2 Tabla `mesas`

* `Baja` **Listar todas las mesas ordenadas** (`db/mesas/acciones.php` → `getTables`)
```sql
SELECT *
FROM mesas
ORDER BY numero_mesa ASC;
```

* `Baja` **Obtener mesa por ID** (`db/mesas/acciones.php` → `getTableById`)
```sql
SELECT *
FROM mesas
WHERE id_mesa = 1
LIMIT 1;
```

* `Baja` **Consultar estado actual de una mesa** (`db/mesas/acciones.php` → `syncTableOccupancy`)
```sql
SELECT estado
FROM mesas
WHERE id_mesa = 1;
```

* `Baja` **(con bloqueo) Bloquear fila de mesa antes de abrir pedido** (`db/pedidos/acciones.php` → `createOrder`)
```sql
SELECT estado
FROM mesas
WHERE id_mesa = 1
FOR UPDATE;
```

* `Media` **Insertar nueva mesa** (`db/mesas/acciones.php` → `saveTable`)
```sql
INSERT INTO mesas (numero_mesa, capacidad, estado)
VALUES (10, 4, 'disponible');
```

* `Media` **Actualizar datos completos de una mesa** (`db/mesas/acciones.php` → `saveTable`)
```sql
UPDATE mesas
SET numero_mesa = 10,
    capacidad = 6,
    estado = 'disponible'
WHERE id_mesa = 1;
```

* `Baja` **Cambiar estado de mesa (activar/desactivar/ocupar/liberar)** (`db/mesas/acciones.php` → `toggleTableStatus`, `syncTableOccupancy`; `db/pedidos/acciones.php` → `createOrder`)
```sql
UPDATE mesas
SET estado = 'ocupada'
WHERE id_mesa = 1;
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `mesas`;
```

---

### 1.3 Tabla `platillos`

* `Baja` **Listar todos los platillos** (`db/platillos/acciones.php` → `getDishes`)
```sql
SELECT *
FROM platillos
ORDER BY categoria ASC, nombre ASC;
```

* `Media` **Listar platillos disponibles filtrados por categoría** (`db/platillos/acciones.php` → `getDishes`)
```sql
SELECT *
FROM platillos
WHERE disponible = 1
  AND categoria = 'Platos Fuertes'
ORDER BY categoria ASC, nombre ASC;
```

* `Baja` **Obtener platillo por ID** (`db/platillos/acciones.php` → `getDishById`)
```sql
SELECT *
FROM platillos
WHERE id_platillo = 1
LIMIT 1;
```

* `Baja` **Validar precio y disponibilidad de un platillo al ordenar** (`db/pedidos/acciones.php` → `createOrder`)
```sql
SELECT id_platillo, nombre, precio, disponible
FROM platillos
WHERE id_platillo = 1;
```

* `Media` **Obtener categorías distintas activas** (`db/platillos/acciones.php` → `getCategories`)
```sql
SELECT DISTINCT categoria
FROM platillos
WHERE categoria IS NOT NULL
  AND categoria != ''
ORDER BY categoria ASC;
```

* `Media` **Insertar nuevo platillo** (`db/platillos/acciones.php` → `saveDish`)
```sql
INSERT INTO platillos (nombre, descripcion, precio, categoria, disponible, imagen)
VALUES ('Tacos al Pastor', 'Orden de 4 tacos con piña y cilantro', 125.00, 'Platos Fuertes', 1, 'uploads/dishes/tacos_pastor.jpg');
```

* `Media` **Actualizar platillo con nueva imagen** (`db/platillos/acciones.php` → `saveDish`)
```sql
UPDATE platillos
SET nombre = 'Tacos al Pastor Especial',
    descripcion = 'Orden de 5 tacos con piña, cilantro y cebolla',
    precio = 140.00,
    categoria = 'Platos Fuertes',
    disponible = 1,
    imagen = 'uploads/dishes/tacos_pastor_esp.jpg'
WHERE id_platillo = 1;
```

* `Media` **Actualizar platillo conservando imagen actual** (`db/platillos/acciones.php` → `saveDish`)
```sql
UPDATE platillos
SET nombre = 'Tacos al Pastor Especial',
    descripcion = 'Orden de 5 tacos con piña, cilantro y cebolla',
    precio = 140.00,
    categoria = 'Platos Fuertes',
    disponible = 1
WHERE id_platillo = 1;
```

* `Baja` **Activar o desactivar disponibilidad de platillo** (`db/platillos/acciones.php` → `toggleDishAvailability`)
```sql
UPDATE platillos
SET disponible = 0
WHERE id_platillo = 1;
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `platillos`;
```

---

### 1.4 Tabla `ingredientes`

* `Baja` **Listar ingredientes ordenados alfabéticamente** (`db/ingredientes/acciones.php` → `getIngredients`)
```sql
SELECT *
FROM ingredientes
ORDER BY nombre ASC;
```

* `Baja` **Listar inventario priorizando menor stock** (`db/reportes/acciones.php` → `getReportInventoryStatus`)
```sql
SELECT *
FROM ingredientes
ORDER BY cantidad_stock ASC, nombre ASC;
```

* `Baja` **(con bloqueo) Bloquear y consultar stock antes de descontar** (`db/pedidos/acciones.php` → `createOrder`)
```sql
SELECT cantidad_stock
FROM ingredientes
WHERE id_ingrediente = 1
FOR UPDATE;
```

* `Media` **Insertar nuevo ingrediente** (`db/ingredientes/acciones.php` → `saveIngredient`)
```sql
INSERT INTO ingredientes (nombre, cantidad_stock, unidad_medida, stock_minimo)
VALUES ('Carne de cerdo', 25.00, 'kg', 5.00);
```

* `Media` **Actualizar datos de un ingrediente** (`db/ingredientes/acciones.php` → `saveIngredient`)
```sql
UPDATE ingredientes
SET nombre = 'Carne de cerdo marinada',
    cantidad_stock = 30.00,
    unidad_medida = 'kg',
    stock_minimo = 6.00
WHERE id_ingrediente = 1;
```

* `Media` **Descontar inventario al confirmar pedido** (`db/pedidos/acciones.php` → `createOrder`)
```sql
UPDATE ingredientes
SET cantidad_stock = cantidad_stock - 0.50
WHERE id_ingrediente = 1;
```

* `Media` **Reponer inventario al cancelar pedido** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
UPDATE ingredientes
SET cantidad_stock = cantidad_stock + 0.50
WHERE id_ingrediente = 1;
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `ingredientes`;
```

---

### 1.5 Tabla `recetas`

* `Baja` **Consultar ingredientes requeridos de un platillo** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
SELECT id_ingrediente, cantidad_requerida
FROM recetas
WHERE id_platillo = 1;
```

* `Baja` **Insertar renglón de receta** (`db/recetas/acciones.php` → `saveRecipe`)
```sql
INSERT INTO recetas (id_platillo, id_ingrediente, cantidad_requerida)
VALUES (1, 2, 0.25);
```

* `Baja` **Eliminar receta completa de un platillo antes de reescribirla** (`db/recetas/acciones.php` → `saveRecipe`)
```sql
DELETE FROM recetas
WHERE id_platillo = 1;
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `recetas`;
```

---

### 1.6 Tabla `pedidos`

* `Baja` **(con bloqueo) Bloquear pedido para transición de estado** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
SELECT *
FROM pedidos
WHERE id_pedido = 1
FOR UPDATE;
```

* `Media` **Contar pedidos activos en una mesa** (`db/mesas/acciones.php` → `syncTableOccupancy`)
```sql
SELECT COUNT(*)
FROM pedidos
WHERE id_mesa = 1
  AND estado IN ('pendiente', 'preparando', 'entregado');
```

* `Media` **Insertar encabezado de pedido** (`db/pedidos/acciones.php` → `createOrder`)
```sql
INSERT INTO pedidos (id_usuario, id_mesa, fecha_pedido, estado, total, fecha_cierre)
VALUES (2, 1, NOW(), 'pendiente', 280.00, NULL);
```

* `Baja` **Actualizar estado de pedido (transición intermedia o cancelación)** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
UPDATE pedidos
SET estado = 'preparando'
WHERE id_pedido = 1;
```

* `Media` **Cerrar pedido como pagado registrando fecha de cierre** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
UPDATE pedidos
SET estado = 'pagado',
    fecha_cierre = NOW()
WHERE id_pedido = 1;
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `pedidos`;
```

---

### 1.7 Tabla `detalle_pedido`

* `Baja` **Consultar platillos y cantidades de un pedido** (`db/pedidos/acciones.php` → `updateOrderStatus`)
```sql
SELECT id_platillo, cantidad
FROM detalle_pedido
WHERE id_pedido = 1;
```

* `Media` **Insertar línea de detalle de pedido** (`db/pedidos/acciones.php` → `createOrder`)
```sql
INSERT INTO detalle_pedido (id_pedido, id_platillo, cantidad, precio_unitario, subtotal)
VALUES (1, 1, 2, 125.00, 250.00);
```

* `Baja` **Limpiar tabla en reinicio de seeder** (`db/seeder/acciones.php` → `resetDatabaseAndRunSeeder`)
```sql
DELETE FROM `detalle_pedido`;
```

---

## Parte 2 — Consultas Compuestas por Complejidad

### 2.1 Básico: `JOIN` de 2 tablas

* **Receta de un platillo con detalle de ingrediente y stock actual** (`db/recetas/acciones.php` → `getRecipeForDish`)
```sql
SELECT r.id_ingrediente,
       r.cantidad_requerida,
       i.nombre,
       i.unidad_medida,
       i.cantidad_stock
FROM recetas r
JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
WHERE r.id_platillo = 1
ORDER BY i.nombre ASC;
```

* **Ingredientes requeridos por platillo durante la creación de pedido** (`db/pedidos/acciones.php` → `createOrder`)
```sql
SELECT r.id_ingrediente,
       r.cantidad_requerida,
       i.nombre,
       i.unidad_medida
FROM recetas r
JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
WHERE r.id_platillo = 1;
```

* **Detalle de un pedido con nombre y categoría de cada platillo** (`db/pedidos/acciones.php` → `getOrderDetails`)
```sql
SELECT d.*,
       pl.nombre AS platillo_nombre,
       pl.categoria
FROM detalle_pedido d
JOIN platillos pl ON d.id_platillo = pl.id_platillo
WHERE d.id_pedido = 1;
```

* **Historial de pedidos de un cliente con número de mesa opcional** (`db/pedidos/acciones.php` → `getUserOrders`)
```sql
SELECT p.*,
       m.numero_mesa
FROM pedidos p
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
WHERE p.id_usuario = 2
ORDER BY p.fecha_pedido DESC;
```

---

### 2.2 Intermedio: `JOIN` múltiples (3 tablas) y filtros combinados

* **Ficha completa de un pedido específico (cliente + mesa)** (`db/pedidos/acciones.php` → `getOrderById`)
```sql
SELECT p.*,
       u.nombre AS usuario_nombre,
       u.correo AS usuario_correo,
       m.numero_mesa
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
WHERE p.id_pedido = 1
LIMIT 1;
```

* **Listado general de pedidos sin filtros** (`db/pedidos/acciones.php` → `getAllOrders`, `db/reportes/acciones.php` → `getReportOrdersHistory`)
```sql
SELECT p.*,
       u.nombre AS usuario_nombre,
       m.numero_mesa
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
ORDER BY p.fecha_pedido DESC;
```

* **Listado general de pedidos filtrado por estado y rango de fechas** (`db/pedidos/acciones.php` → `getAllOrders`, `db/reportes/acciones.php` → `getReportOrdersHistory`)
```sql
SELECT p.*,
       u.nombre AS usuario_nombre,
       m.numero_mesa
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_pedido) >= '2026-01-01'
  AND DATE(p.fecha_pedido) <= '2026-12-31'
ORDER BY p.fecha_pedido DESC;
```

---

### 2.3 Avanzado: Agregaciones, `GROUP BY`, Rankings y Reportes Analíticos

* **Resumen global de ventas, pedidos y ticket promedio** (`db/reportes/acciones.php` → `getDashboardKpis`)
```sql
SELECT SUM(p.total) AS total_ventas,
       COUNT(p.id_pedido) AS cant_pedidos,
       AVG(p.total) AS ticket_promedio
FROM pedidos p
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31';
```

* **Ventas agrupadas por día** (`db/reportes/acciones.php` → `getReportSalesByPeriod`)
```sql
SELECT DATE(fecha_cierre) AS periodo,
       COUNT(id_pedido) AS total_pedidos,
       SUM(total) AS total_ventas,
       AVG(total) AS ticket_promedio
FROM pedidos
WHERE estado = 'pagado'
  AND DATE(fecha_cierre) >= '2026-01-01'
  AND DATE(fecha_cierre) <= '2026-12-31'
  AND fecha_cierre IS NOT NULL
GROUP BY DATE(fecha_cierre)
ORDER BY periodo DESC;
```

* **Ranking de clientes con más compras** (`db/reportes/acciones.php` → `getReportTopClients`)
```sql
SELECT u.id_usuario,
       u.nombre,
       u.correo,
       u.telefono,
       COUNT(p.id_pedido) AS total_pedidos,
       SUM(p.total) AS total_gastado,
       MAX(p.fecha_cierre) AS ultima_compra
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31'
GROUP BY u.id_usuario, u.nombre, u.correo, u.telefono
ORDER BY total_pedidos DESC, total_gastado DESC;
```

* **Ranking de platillos más vendidos (3 tablas + agregación)** (`db/reportes/acciones.php` → `getReportTopDishes`)
```sql
SELECT pl.id_platillo,
       pl.nombre,
       pl.categoria,
       pl.precio,
       SUM(d.cantidad) AS unidades_vendidas,
       SUM(d.subtotal) AS importe_total
FROM detalle_pedido d
JOIN pedidos p ON d.id_pedido = p.id_pedido
JOIN platillos pl ON d.id_platillo = pl.id_platillo
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31'
GROUP BY pl.id_platillo, pl.nombre, pl.categoria, pl.precio
ORDER BY unidades_vendidas DESC, importe_total DESC;
```

---

## Parte 3 — Agrupación por Resultado Obtenido y Transacciones

### 3.1 Resultado: KPIs del Dashboard (`getDashboardKpis`)

Para construir la vista de KPIs del Dashboard (`db/reportes/acciones.php` → `getDashboardKpis`), el sistema ejecuta **4 consultas consecutivas** sobre pedidos con `estado = 'pagado'` (opcionalmente acotadas por rango de `fecha_cierre`):

```sql
-- =====================================================================
-- KPI 1 y 5: Ventas totales, cantidad de pedidos y ticket promedio
-- =====================================================================
SELECT SUM(p.total) AS total_ventas,
       COUNT(p.id_pedido) AS cant_pedidos,
       AVG(p.total) AS ticket_promedio
FROM pedidos p
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31';

-- =====================================================================
-- KPI 2: Platillo más vendido (por unidades totales)
-- =====================================================================
SELECT pl.nombre,
       SUM(d.cantidad) AS total_unidades
FROM detalle_pedido d
JOIN pedidos p ON d.id_pedido = p.id_pedido
JOIN platillos pl ON d.id_platillo = pl.id_platillo
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31'
GROUP BY d.id_platillo, pl.nombre
ORDER BY total_unidades DESC
LIMIT 1;

-- =====================================================================
-- KPI 3: Cliente con más compras (desempate por mayor gasto)
-- =====================================================================
SELECT u.nombre,
       COUNT(p.id_pedido) AS total_pedidos,
       SUM(p.total) AS total_gastado
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31'
GROUP BY p.id_usuario, u.nombre
ORDER BY total_pedidos DESC, total_gastado DESC
LIMIT 1;

-- =====================================================================
-- KPI 4: Día con mayores ventas
-- =====================================================================
SELECT DATE(p.fecha_cierre) AS fecha_dia,
       SUM(p.total) AS total_dia
FROM pedidos p
WHERE p.estado = 'pagado'
  AND DATE(p.fecha_cierre) >= '2026-01-01'
  AND DATE(p.fecha_cierre) <= '2026-12-31'
GROUP BY DATE(p.fecha_cierre)
ORDER BY total_dia DESC
LIMIT 1;
```

> **Nota:** Si no se filtra por fechas en el dashboard, se omiten las condiciones `AND DATE(p.fecha_cierre) >= ...` y `AND DATE(p.fecha_cierre) <= ...`, conservando únicamente `WHERE p.estado = 'pagado'`.

---

### 3.2 Resultado: Reportes Administrativos (`db/reportes/acciones.php`)

Cada reporte administrativo corresponde a una consulta independiente:

```sql
-- Reporte 1: Ventas por día (getReportSalesByPeriod)
SELECT DATE(fecha_cierre) AS periodo,
       COUNT(id_pedido) AS total_pedidos,
       SUM(total) AS total_ventas,
       AVG(total) AS ticket_promedio
FROM pedidos
WHERE estado = 'pagado'
  AND fecha_cierre IS NOT NULL
GROUP BY DATE(fecha_cierre)
ORDER BY periodo DESC;

-- Reporte 2: Platillos más vendidos (getReportTopDishes)
SELECT pl.id_platillo,
       pl.nombre,
       pl.categoria,
       pl.precio,
       SUM(d.cantidad) AS unidades_vendidas,
       SUM(d.subtotal) AS importe_total
FROM detalle_pedido d
JOIN pedidos p ON d.id_pedido = p.id_pedido
JOIN platillos pl ON d.id_platillo = pl.id_platillo
WHERE p.estado = 'pagado'
GROUP BY pl.id_platillo, pl.nombre, pl.categoria, pl.precio
ORDER BY unidades_vendidas DESC, importe_total DESC;

-- Reporte 3: Clientes con más compras (getReportTopClients)
SELECT u.id_usuario,
       u.nombre,
       u.correo,
       u.telefono,
       COUNT(p.id_pedido) AS total_pedidos,
       SUM(p.total) AS total_gastado,
       MAX(p.fecha_cierre) AS ultima_compra
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
WHERE p.estado = 'pagado'
GROUP BY u.id_usuario, u.nombre, u.correo, u.telefono
ORDER BY total_pedidos DESC, total_gastado DESC;

-- Reporte 4: Inventario actual (getReportInventoryStatus; alertas calculadas en capa de aplicación)
SELECT *
FROM ingredientes
ORDER BY cantidad_stock ASC, nombre ASC;

-- Reporte 5: Historial completo de pedidos (getReportOrdersHistory -> getAllOrders)
SELECT p.*,
       u.nombre AS usuario_nombre,
       m.numero_mesa
FROM pedidos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
ORDER BY p.fecha_pedido DESC;
```

---

### 3.3 Transacción: Guardar Receta de un Platillo (`saveRecipe`)

Archivo origen: `db/recetas/acciones.php`. Reemplaza atómicamente los ingredientes asociados a un platillo.

```sql
START TRANSACTION;

DELETE FROM recetas
WHERE id_platillo = 1;

INSERT INTO recetas (id_platillo, id_ingrediente, cantidad_requerida)
VALUES (1, 1, 0.25),
       (1, 2, 0.10),
       (1, 3, 2.00);

COMMIT;
```

---

### 3.4 Transacción: Crear Pedido con Validación y Descuento de Inventario (`createOrder`)

Archivo origen: `db/pedidos/acciones.php`. Garantiza consistencia entre mesa, disponibilidad de platillos, stock de ingredientes, encabezado del pedido y su detalle.

```sql
START TRANSACTION;

-- 1. Bloquear y validar la mesa seleccionada (si aplica)
SELECT estado
FROM mesas
WHERE id_mesa = 1
FOR UPDATE;

-- 2. Validar existencia, disponibilidad y precio real de cada platillo
SELECT id_platillo, nombre, precio, disponible
FROM platillos
WHERE id_platillo = 1;

-- 3. Consultar la receta de cada platillo para calcular insumos requeridos
SELECT r.id_ingrediente,
       r.cantidad_requerida,
       i.nombre,
       i.unidad_medida
FROM recetas r
JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
WHERE r.id_platillo = 1;

-- 4. Bloquear ingredientes requeridos, verificar stock suficiente y descontar
SELECT cantidad_stock
FROM ingredientes
WHERE id_ingrediente = 1
FOR UPDATE;

UPDATE ingredientes
SET cantidad_stock = cantidad_stock - 0.50
WHERE id_ingrediente = 1;

-- 5. Insertar encabezado del pedido con el total calculado
INSERT INTO pedidos (id_usuario, id_mesa, fecha_pedido, estado, total, fecha_cierre)
VALUES (2, 1, NOW(), 'pendiente', 250.00, NULL);

SET @nuevo_id_pedido = LAST_INSERT_ID();

-- 6. Insertar líneas de detalle del pedido
INSERT INTO detalle_pedido (id_pedido, id_platillo, cantidad, precio_unitario, subtotal)
VALUES (@nuevo_id_pedido, 1, 2, 125.00, 250.00);

-- 7. Marcar la mesa como ocupada (si el pedido tiene mesa asignada)
UPDATE mesas
SET estado = 'ocupada'
WHERE id_mesa = 1;

COMMIT;
```

---

### 3.5 Transacción: Actualizar Estado de Pedido, Reponer Stock y Sincronizar Mesa (`updateOrderStatus`)

Archivo origen: `db/pedidos/acciones.php` + `db/mesas/acciones.php` (`syncTableOccupancy`).

#### Caso A: Transición a `'pagado'` (cierre de pedido y liberación de mesa)

```sql
START TRANSACTION;

-- 1. Bloquear pedido para validar transición permitida
SELECT *
FROM pedidos
WHERE id_pedido = 1
FOR UPDATE;

-- 2. Registrar estado pagado y fecha de cierre
UPDATE pedidos
SET estado = 'pagado',
    fecha_cierre = NOW()
WHERE id_pedido = 1;

-- 3. Sincronizar ocupación de la mesa asociada (syncTableOccupancy)
SELECT COUNT(*)
FROM pedidos
WHERE id_mesa = 1
  AND estado IN ('pendiente', 'preparando', 'entregado');

SELECT estado
FROM mesas
WHERE id_mesa = 1;

UPDATE mesas
SET estado = 'disponible'
WHERE id_mesa = 1;

COMMIT;
```

#### Caso B: Transición a `'cancelado'` (reposición de inventario y liberación de mesa)

```sql
START TRANSACTION;

-- 1. Bloquear pedido
SELECT *
FROM pedidos
WHERE id_pedido = 1
FOR UPDATE;

-- 2. Obtener platillos y cantidades del pedido a cancelar
SELECT id_platillo, cantidad
FROM detalle_pedido
WHERE id_pedido = 1;

-- 3. Consultar receta de cada platillo y reponer stock de ingredientes
SELECT id_ingrediente, cantidad_requerida
FROM recetas
WHERE id_platillo = 1;

UPDATE ingredientes
SET cantidad_stock = cantidad_stock + 0.50
WHERE id_ingrediente = 1;

-- 4. Marcar pedido como cancelado
UPDATE pedidos
SET estado = 'cancelado'
WHERE id_pedido = 1;

-- 5. Sincronizar ocupación de la mesa asociada (syncTableOccupancy)
SELECT COUNT(*)
FROM pedidos
WHERE id_mesa = 1
  AND estado IN ('pendiente', 'preparando', 'entregado');

SELECT estado
FROM mesas
WHERE id_mesa = 1;

UPDATE mesas
SET estado = 'disponible'
WHERE id_mesa = 1;

COMMIT;
```

---

### 3.6 Transacción: Reiniciar Base de Datos y Cargar Seeder (`resetDatabaseAndRunSeeder`)

Archivo origen: `db/seeder/acciones.php`. Elimina los datos respetando el orden inverso de las llaves foráneas, conserva al administrador en sesión y ejecuta las sentencias de [`seeder.sql`](seeder.sql).

```sql
START TRANSACTION;

-- 1. Vaciar tablas dependientes en orden referencial inverso
DELETE FROM `detalle_pedido`;
DELETE FROM `pedidos`;
DELETE FROM `recetas`;
DELETE FROM `platillos`;
DELETE FROM `ingredientes`;
DELETE FROM `mesas`;

-- 2. Conservar al administrador en sesión
DELETE FROM `usuarios`
WHERE `id_usuario` != 1;

-- 3. Ejecutar las sentencias INSERT definidas en docs/seeder.sql
-- (ver contenido completo en docs/seeder.sql)

COMMIT;
```
