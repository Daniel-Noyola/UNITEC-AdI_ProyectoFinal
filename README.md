# Food Manager

Sistema web de gestión para restaurantes y cafeterías desarrollado en **PHP** y **MySQL / MariaDB** como proyecto final de la materia *Arquitectura de la Información*.

Permite administrar el menú digital, mesas, recetas, inventario de ingredientes con descuento automático por pedido, control de estados de órdenes y reportes administrativos con KPIs.

---

## Características principales

- **Autenticación y control de acceso por roles (`admin` y `cliente`)**:
  - Registro e inicio de sesión con contraseñas cifradas (`password_hash` / `password_verify`).
  - Redirección automática según rol desde `index.php`.
- **Menú digital y creación de pedidos (`views/menu/menu.php`)**:
  - Visualización de platillos disponibles con fotografía o marcador SVG de respaldo.
  - Validación transaccional de stock de ingredientes según la receta de cada platillo y descuento automático al crear el pedido.
- **Gestión de pedidos (`views/orders/orders.php`)**:
  - Flujo de estados controlado: `pendiente` → `preparando` → `entregado` → `pagado` (o `cancelado`).
  - Los clientes consultan únicamente sus propios pedidos; el administrador gestiona todos.
- **Administración de mesas (`views/tables/tables.php`)**:
  - Alta, edición y control de estado (`disponible`, `ocupada`, `inactiva`).
- **Platillos y recetas (`views/dishes/dishes.php`)**:
  - CRUD de platillos con carga de imagen (`JPG`, `PNG`, `WEBP` hasta 3 MB en `uploads/`).
  - Asociación de ingredientes y cantidades requeridas por platillo (`recetas`).
- **Inventario de ingredientes (`views/inventory/inventory.php`)**:
  - Control de stock actual, unidad de medida y alertas de stock mínimo.
- **Dashboard KPI y 5 Reportes (`views/dashboard/dashboard.php`, `views/reports/reports.php`)**:
  - KPIs filtrables por fecha (ventas totales, pedidos pagados, ticket promedio, platillo más vendido, mejor cliente y día con mayores ventas).
  - 5 reportes:
    1. Ventas por periodo.
    2. Platillos más vendidos.
    3. Clientes y gasto acumulado.
    4. Inventario y stock mínimo.
    5. Historial completo de pedidos.

---

## Estructura del proyecto

```text
ProyectoFinal/
├── AGENTS.md                        # Guía de contexto y convenciones del repositorio
├── README.md                        # Documentación principal del proyecto
├── index.php                        # Punto de entrada y enrutamiento por rol
├── db/
│   ├── conexion.php                 # Conexión PDO a MySQL / MariaDB (food_manager)
│   ├── create_admin.php             # Script CLI para crear o promover un administrador
│   ├── ingredientes/acciones.php    # CRUD de ingredientes y catálogo INGREDIENT_UNITS
│   ├── mesas/acciones.php           # Gestión de mesas y sincronización de ocupación
│   ├── pedidos/acciones.php         # Creación transaccional y estados VALID_ORDER_TRANSITIONS
│   ├── platillos/acciones.php       # CRUD de platillos y catálogo DISH_CATEGORIES
│   ├── recetas/acciones.php         # Asociación platillo-ingredientes
│   ├── reportes/acciones.php        # KPIs del dashboard y 5 reportes administrativos
│   ├── seeder/acciones.php          # Reinicio transaccional de datos y carga de seeder.sql
│   └── usuarios/acciones.php        # Registro y autenticación de usuarios
├── docs/
│   ├── diagrama_er.md               # Diagrama Entidad-Relación (Mermaid)
│   ├── flujo_datos_arquitectura.md  # Flujo de información y reglas de negocio
│   ├── schema.sql                   # Esquema de 7 tablas (food_manager)
│   └── seeder.sql                   # Seeder independiente con datos y pedidos de prueba
├── helpers/
│   ├── auth.php                     # Sesión, autenticación y guards (requireLogin, requireAdmin)
│   ├── funciones.php                # Escape HTML e(), mensajes flash, badges, iconos e imágenes
│   └── layout.php                   # Plantilla común (sidebar, topbar, modales, seeder y JS mínimo)
├── styles/
│   └── style.css                    # Hoja de estilos global
├── uploads/
│   └── dishes/                      # Imágenes del seeder (cafe_latte.jpg, hamburguesa.jpg,
│                                    # papas_francesas.jpg, refresco_lata.jpg, tacos_pollo.jpg)
└── views/
    ├── auth/                        # login.php, register.php, logout.php
    ├── dashboard/                   # dashboard.php
    ├── dishes/                      # dishes.php
    ├── inventory/                   # inventory.php
    ├── menu/                        # menu.php
    ├── orders/                      # orders.php
    ├── reports/                     # reports.php
    └── tables/                      # tables.php
```

---

## Esquema de base de datos

El archivo [`docs/schema.sql`](docs/schema.sql) define la base de datos `food_manager` con 7 tablas principales (puedes consultar su representación visual en [`docs/diagrama_er.md`](docs/diagrama_er.md) y el flujo de negocio en [`docs/flujo_datos_arquitectura.md`](docs/flujo_datos_arquitectura.md)), mientras que [`docs/seeder.sql`](docs/seeder.sql) contiene los datos iniciales independientes (clientes, mesas, ingredientes, platillos con imágenes, recetas y pedidos históricos):

1. `usuarios` — Cuentas de `admin` y `cliente`.
2. `mesas` — Mesas del establecimiento y su disponibilidad.
3. `platillos` — Catálogo de productos del menú.
4. `ingredientes` — Insumos con stock actual y stock mínimo.
5. `recetas` — Relación muchos a muchos entre `platillos` e `ingredientes` con cantidad requerida.
6. `pedidos` — Cabecera de órdenes, usuario, mesa, estado y total.
7. `detalle_pedido` — Partidas de cada pedido con precio unitario y subtotal.

---

## Requisitos e instalación

### Requisitos
- PHP 8.0 o superior con extensión `pdo_mysql`.
- MySQL 5.7+ o MariaDB 10.4+.
- Servidor web local (XAMPP, WAMP, MAMP, Laragon o servidor embebido de PHP).

### Pasos para instalar, preparar y ejecutar el proyecto

#### 1. Colocar el proyecto y verificar requisitos
- Copia la carpeta del proyecto dentro de la raíz de tu servidor web (por ejemplo `C:\wamp64\www\ProyectoFinal` en WAMP o `C:\xampp\htdocs\ProyectoFinal` en XAMPP), o déjala en cualquier carpeta si usarás el servidor embebido de PHP.
- Asegúrate de tener encendidos los servicios de **Apache** (o Nginx) y **MySQL / MariaDB**.

#### 2. Importar la base de datos (estructura)
La aplicación **no** crea la base de datos ni las tablas automáticamente.
1. Abre **phpMyAdmin** (o tu cliente MySQL favorito).
2. Importa o ejecuta el archivo [`docs/schema.sql`](docs/schema.sql).
   Esto creará la base de datos `food_manager` y sus 7 tablas.

#### 3. Configurar la conexión a MySQL
Abre [`db/conexion.php`](db/conexion.php) y ajusta las credenciales según tu entorno:
- `$host`: `'127.0.0.1'` (o `'localhost'`)
- `$port`: `'3306'` (en MAMP suele ser `'8889'`)
- `$dbName`: `'food_manager'`
- `$user`: `'root'`
- `$pass`: en WAMP/XAMPP por defecto suele ser `''` (vacía); en MAMP suele ser `'root'`.

#### 4. Cargar los datos de prueba (Seeder)
Importa [`docs/seeder.sql`](docs/seeder.sql) dentro de la base de datos `food_manager` en phpMyAdmin (o desde consola).
*(También puedes recargarlo en cualquier momento desde el Dashboard del Administrador con el botón **Cargar Seeder** ubicado junto a **Cerrar Sesión**).*

#### 5. Crear el usuario Administrador
Abre una terminal **dentro de la carpeta del proyecto** y ejecuta:
```bash
php db/create_admin.php
```
*(Si usas WAMP y `php` no está en el PATH de Windows, usa la ruta completa al ejecutable, por ejemplo `C:\wamp64\bin\php\php8.2.0\php.exe db/create_admin.php`).*

También puedes pasar los datos directamente en una sola línea:
```bash
php db/create_admin.php "Admin" "admin@ejemplo.com" "admin123" "5551234567"
```

> [!NOTE]
> **¿No pudiste crear el administrador con el script?**
> Créalo manualmente directamente en tu base de datos `food_manager` (tabla `usuarios`, con `rol = 'admin'` y un hash bcrypt en `contrasena_hash`; incluso puedes registrarte normalmente como cliente en la página y luego cambiarle `rol` a `'admin'` en phpMyAdmin). *Y si no puedes ni hacer eso por tu cuenta, no sé qué haces aquí xD.*

#### 6. Ejecutar y ver el proyecto en el navegador
- **Con WAMP / XAMPP**: Abre tu navegador y entra a `http://localhost/ProyectoFinal` (ajustando el nombre de la carpeta si la renombraste).
- **Con el servidor embebido de PHP** (desde la carpeta del proyecto):
  ```bash
  php -S localhost:8000
  ```
  Luego abre `http://localhost:8000` en tu navegador.

---

## Cómo probar la aplicación (Cliente de prueba del Seeder)

Una vez cargado [`docs/seeder.sql`](docs/seeder.sql), puedes iniciar sesión inmediatamente como **cliente** usando cualquiera de estas cuentas (por ejemplo, la primera):

- **Correo**: `ana.lopez@ejemplo.com`
- **Contraseña**: `pass123`

Con este usuario podrás explorar el **Menú Digital**, realizar nuevos pedidos y revisar el historial en **Mis Pedidos** (ya incluye pedidos previos pagados y cancelados para probar la vista de detalle).

### Todos los clientes del Seeder
En total se cargan **5 clientes de prueba**, **6 mesas**, **10 ingredientes**, **5 platillos con imagen**, sus **recetas** y **30 pedidos históricos**  para poblar el Dashboard KPI y los reportes. Todos comparten la contraseña **`pass123`**:

| Nombre | Correo electrónico | Contraseña | Pedidos en seeder |
| --- | --- | --- | --- |
| Ana López | `ana.lopez@ejemplo.com` | `pass123` | 7 |
| Carlos Méndez | `carlos.mendez@ejemplo.com` | `pass123` | 6 |
| Sofía Ramírez | `sofia.ramirez@ejemplo.com` | `pass123` | 6 |
| Javier Torres | `javier.torres@ejemplo.com` | `pass123` | 5 |
| Lucía Fernández | `lucia.fernandez@ejemplo.com` | `pass123` | 6 |
