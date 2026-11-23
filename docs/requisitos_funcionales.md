# Requisitos Funcionales — Food Manager

Lista de requisitos funcionales del sistema
## 1. Autenticación y Control de Acceso
- **RF-01**: El sistema debe permitir a nuevos usuarios registrarse como clientes proporcionando nombre, correo electrónico, contraseña y teléfono opcional.
- **RF-02**: El sistema debe permitir a los usuarios iniciar sesión mediante correo electrónico y contraseña verificada, redirigiéndolos automáticamente según su rol (`admin` o `cliente`).
- **RF-03**: El sistema debe permitir a cualquier usuario autenticado cerrar su sesión activa de forma segura.
- **RF-04**: El sistema debe restringir las pantallas y acciones administrativas exclusivamente a usuarios con rol de administrador.

## 2. Menú Digital y Creación de Pedidos
- **RF-05**: El sistema debe mostrar únicamente los platillos marcados como disponibles, permitiendo filtrarlos por categoría.
- **RF-06**: El sistema debe permitir al cliente o administrador crear un pedido seleccionando platillos, cantidades y una mesa disponible o la modalidad para llevar.
- **RF-07**: El sistema debe validar antes de confirmar un pedido que la mesa esté disponible y que exista stock suficiente de todos los ingredientes requeridos por las recetas.
- **RF-08**: El sistema debe descontar automáticamente del inventario los ingredientes consumidos al confirmar un pedido y marcar la mesa seleccionada como ocupada.
- **RF-09**: El sistema debe calcular el total del pedido y congelar los precios unitarios desde el servidor al momento de registrar la compra.

## 3. Seguimiento y Gestión de Pedidos
- **RF-10**: El sistema debe mostrar al cliente únicamente su historial de pedidos y al administrador la lista completa filtrable por estado.
- **RF-11**: El sistema debe permitir abrir el detalle de un pedido mostrando de forma destacada su estado actual, línea de progreso, platillos consumidos y monto final.
- **RF-12**: El sistema debe permitir al administrador avanzar el estado de los pedidos siguiendo el flujo válido (`pendiente` → `preparando` → `entregado`) o cancelarlos.
- **RF-13**: El sistema debe permitir al cliente cancelar su pedido únicamente mientras se encuentre en estado `pendiente` y confirmarlo como `pagado` cuando esté en estado `entregado`.
- **RF-14**: El sistema debe reponer automáticamente al inventario los ingredientes de un pedido cuando este sea cancelado y liberar la mesa si no tiene otros pedidos activos.

## 4. Administración de Mesas
- **RF-15**: El sistema debe permitir al administrador registrar y editar mesas indicando número de mesa, capacidad y estado.
- **RF-16**: El sistema debe permitir al administrador activar o desactivar mesas, impidiendo desactivar aquellas que se encuentren ocupadas con pedidos activos.

## 5. Catálogo de Platillos y Recetas
- **RF-17**: El sistema debe permitir al administrador crear platillos asignando nombre, descripción, precio, una categoría válida predefinida, disponibilidad e imagen opcional (`JPG`, `PNG` o `WEBP`).
- **RF-18**: El sistema debe permitir al administrador activar o desactivar la disponibilidad de un platillo sin eliminarlo físicamente para preservar el historial de pedidos.
- **RF-19**: El sistema debe permitir al administrador configurar la receta de cada platillo asociando ingredientes y cantidades requeridas.

## 6. Control de Inventario
- **RF-20**: El sistema debe permitir al administrador registrar ingredientes y actualizar sus existencias, seleccionando una unidad de medida válida predefinida y un stock mínimo de alerta.
- **RF-21**: El sistema debe mostrar alertas visuales cuando la existencia de un ingrediente sea igual o inferior a su stock mínimo o esté próxima a alcanzarlo.

## 7. Dashboard KPI, Reportes y Datos de Prueba
- **RF-22**: El sistema debe mostrar al administrador un tablero con indicadores clave filtrables por fecha (ventas totales, pedidos pagados, ticket promedio, platillo más vendido, cliente frecuente y día con mayores ventas).
- **RF-23**: El sistema debe generar cinco reportes administrativos: ventas por día, platillos más vendidos, clientes con más compras, estado de inventario con alertas de bajo stock e historial completo de pedidos.
