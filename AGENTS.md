# AGENTS.md

Guía de contexto y convenciones para agentes de código que trabajen en este repositorio (**Food Manager**).

## Arquitectura y stack

- **Stack**: PHP puro (sin frameworks ni Composer), MySQL / MariaDB mediante `PDO` (`db/conexion.php`), HTML5, CSS3 (`styles/style.css`) y JavaScript mínimo embebido en `helpers/layout.php`.
- **Separación de responsabilidades**:
  - `db/<entidad>/acciones.php`: Funciones de acceso a datos y lógica de negocio (transacciones, validaciones de estado, consultas SQL con sentencias preparadas). Incluye `db/seeder/acciones.php` para reiniciar y cargar `docs/seeder.sql`.
  - `helpers/`: Utilidades transversales (`auth.php` para sesión/roles, `funciones.php` para escape HTML, flash messages e imágenes con soporte para subrutas relativas en `uploads/`, `layout.php` para `renderHeader()` y `renderFooter()`).
  - `views/<modulo>/<modulo>.php`: Controladores de vista que procesan `POST`/`GET`, invocan funciones de `db/` y renderizan HTML.
  - `docs/schema.sql`: Fuente de verdad del esquema de base de datos (`food_manager`, 7 tablas).
  - `docs/seeder.sql`: Datos semilla independientes (clientes de prueba, mesas, ingredientes, platillos con imágenes en `uploads/dishes/`, recetas y pedidos históricos).

## Reglas de desarrollo

1. **Seguridad y SQL**:
   - Usa siempre `getDbConnection()` y sentencias preparadas (`PDO::prepare` + `execute`). Nunca interpoles entrada del usuario en consultas SQL.
   - Escapa toda salida dinámica en HTML con `e()` (definida en `helpers/funciones.php`).
   - Protege cada vista autenticada con `requireLogin()` o `requireAdmin()` (definidas en `helpers/auth.php`).
2. **Transacciones, integridad y catálogos en código**:
   - Operaciones críticas como creación de pedidos, cambios de estado con impacto en inventario/mesas o el reinicio con `resetDatabaseAndRunSeeder()` deben ejecutarse dentro de transacciones (`beginTransaction()` / `commit()` / `rollBack()`) y bloquear filas cuando corresponda (`FOR UPDATE`), siguiendo el patrón de `db/pedidos/acciones.php`.
   - Respeta las transiciones válidas definidas en `VALID_ORDER_TRANSITIONS`.
   - Las categorías de platillos (`DISH_CATEGORIES` en `db/platillos/acciones.php`) y las unidades de medida de ingredientes (`INGREDIENT_UNITS` en `db/ingredientes/acciones.php`) se definen y validan en código, sin modificar el esquema de base de datos.
   - Los platillos no se eliminan físicamente (por `fk_detalle_platillo` `ON DELETE RESTRICT`); se desactivan con `toggleDishAvailability()`.
3. **Interfaz y estilos**:
   - Todas las vistas autenticadas deben usar `renderHeader($pageTitle, $activePage)` y `renderFooter()`. El layout principal (`.app-layout`) fija su altura a `100vh`/`100dvh` con desplazamiento interno en `.app-layout__main`.
   - Usa las clases CSS existentes en `styles/style.css` (`.card`, `.btn`, `.badge`, `.data-table`, `.modal-overlay`, `.alert`, etc.) y los atributos `data-modal-target`, `data-modal-close` y `data-confirm` soportados por `helpers/layout.php` (usado también en el botón **Cargar Seeder** del dashboard).
   - Evita agregar dependencias externas (JS/CSS de terceros o paquetes) salvo que se solicite explícitamente.
4. **Idioma y estilo de código**:
   - Mantén la interfaz de usuario, mensajes flash y comentarios explicativos en español.
   - Usa tipado estricto en firmas de funciones PHP (tipos de parámetro y retorno) consistente con el resto del proyecto.
   - Preserva las 7 tablas definidas en `docs/schema.sql`; si modificas el esquema, actualiza también `docs/schema.sql`.

## Verificación rápida

- Sintaxis PHP tras editar archivos:
  ```bash
  php -l ruta/al/archivo.php
  ```
- Si se requiere probar en local:
  ```bash
  php -S localhost:8000
  ```
- Nunca expongas ni modifiques `db/create_admin.php` para permitir ejecución vía web (`php_sapi_name() !== 'cli'`).
