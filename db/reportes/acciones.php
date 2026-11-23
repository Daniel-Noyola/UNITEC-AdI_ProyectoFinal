<?php
/**
 * Consultas SQL sencillas para los KPIs del Dashboard y los 5 Reportes Administrativos.
 */

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../pedidos/acciones.php';

/**
 * Construye condiciones de fecha simples para pedidos pagados.
 */
function buildPaidDateFilter(?string $from, ?string $to, string $alias = 'pedidos'): array
{
    $conditions = ["$alias.estado = 'pagado'"];
    $params = [];

    if ($from !== null && $from !== '') {
        $conditions[] = "DATE($alias.fecha_cierre) >= ?";
        $params[] = $from;
    }
    if ($to !== null && $to !== '') {
        $conditions[] = "DATE($alias.fecha_cierre) <= ?";
        $params[] = $to;
    }

    return [implode(' AND ', $conditions), $params];
}

function getDashboardKpis(?string $dateFrom = null, ?string $dateTo = null): array
{
    $pdo = getDbConnection();
    [$where, $params] = buildPaidDateFilter($dateFrom, $dateTo, 'p');

    // KPI 1 y 5: Ventas totales, cantidad de pedidos y ticket promedio
    $stmtStats = $pdo->prepare(
        "SELECT SUM(p.total) AS total_ventas, COUNT(p.id_pedido) AS cant_pedidos, AVG(p.total) AS ticket_promedio
         FROM pedidos p
         WHERE $where"
    );
    $stmtStats->execute($params);
    $stats = $stmtStats->fetch();

    // KPI 2: Platillo más vendido
    $stmtDish = $pdo->prepare(
        "SELECT pl.nombre, SUM(d.cantidad) AS total_unidades
         FROM detalle_pedido d
         JOIN pedidos p ON d.id_pedido = p.id_pedido
         JOIN platillos pl ON d.id_platillo = pl.id_platillo
         WHERE $where
         GROUP BY d.id_platillo, pl.nombre
         ORDER BY total_unidades DESC
         LIMIT 1"
    );
    $stmtDish->execute($params);
    $topDish = $stmtDish->fetch() ?: ['nombre' => 'Sin ventas registradas', 'total_unidades' => 0];

    // KPI 3: Cliente con más compras
    $stmtClient = $pdo->prepare(
        "SELECT u.nombre, COUNT(p.id_pedido) AS total_pedidos, SUM(p.total) AS total_gastado
         FROM pedidos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE $where
         GROUP BY p.id_usuario, u.nombre
         ORDER BY total_pedidos DESC, total_gastado DESC
         LIMIT 1"
    );
    $stmtClient->execute($params);
    $topClient = $stmtClient->fetch() ?: ['nombre' => 'Sin clientes registrados', 'total_pedidos' => 0, 'total_gastado' => 0];

    // KPI 4: Día con mayores ventas
    $stmtDay = $pdo->prepare(
        "SELECT DATE(p.fecha_cierre) AS fecha_dia, SUM(p.total) AS total_dia
         FROM pedidos p
         WHERE $where
         GROUP BY DATE(p.fecha_cierre)
         ORDER BY total_dia DESC
         LIMIT 1"
    );
    $stmtDay->execute($params);
    $topPeriod = $stmtDay->fetch() ?: ['fecha_dia' => 'N/D', 'total_dia' => 0];

    return [
        'total_ventas'    => (float)($stats['total_ventas'] ?? 0),
        'pedidos_totales' => (int)($stats['cant_pedidos'] ?? 0),
        'ticket_promedio' => (float)($stats['ticket_promedio'] ?? 0),
        'platillo_top'    => $topDish,
        'cliente_top'     => $topClient,
        'periodo_top'     => $topPeriod,
    ];
}

// Reporte 1: Ventas por día
function getReportSalesByPeriod(?string $from = null, ?string $to = null): array
{
    $pdo = getDbConnection();
    [$where, $params] = buildPaidDateFilter($from, $to, 'pedidos');

    $stmt = $pdo->prepare(
        "SELECT DATE(fecha_cierre) AS periodo,
                COUNT(id_pedido) AS total_pedidos,
                SUM(total) AS total_ventas,
                AVG(total) AS ticket_promedio
         FROM pedidos
         WHERE $where AND fecha_cierre IS NOT NULL
         GROUP BY DATE(fecha_cierre)
         ORDER BY periodo DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Reporte 2: Platillos más vendidos
function getReportTopDishes(?string $from = null, ?string $to = null): array
{
    $pdo = getDbConnection();
    [$where, $params] = buildPaidDateFilter($from, $to, 'p');

    $stmt = $pdo->prepare(
        "SELECT pl.id_platillo, pl.nombre, pl.categoria, pl.precio,
                SUM(d.cantidad) AS unidades_vendidas,
                SUM(d.subtotal) AS importe_total
         FROM detalle_pedido d
         JOIN pedidos p ON d.id_pedido = p.id_pedido
         JOIN platillos pl ON d.id_platillo = pl.id_platillo
         WHERE $where
         GROUP BY pl.id_platillo, pl.nombre, pl.categoria, pl.precio
         ORDER BY unidades_vendidas DESC, importe_total DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Reporte 3: Clientes con más compras
function getReportTopClients(?string $from = null, ?string $to = null): array
{
    $pdo = getDbConnection();
    [$where, $params] = buildPaidDateFilter($from, $to, 'p');

    $stmt = $pdo->prepare(
        "SELECT u.id_usuario, u.nombre, u.correo, u.telefono,
                COUNT(p.id_pedido) AS total_pedidos,
                SUM(p.total) AS total_gastado,
                MAX(p.fecha_cierre) AS ultima_compra
         FROM pedidos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE $where
         GROUP BY u.id_usuario, u.nombre, u.correo, u.telefono
         ORDER BY total_pedidos DESC, total_gastado DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Reporte 4: Inventario actual y alertas de stock mínimo
function getReportInventoryStatus(): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT * FROM ingredientes ORDER BY cantidad_stock ASC, nombre ASC');
    $rows = $stmt->fetchAll();

    // Calcular alerta y diferencia en PHP para mantener la consulta SQL simple
    foreach ($rows as &$row) {
        $stock = (float)$row['cantidad_stock'];
        $min   = (float)$row['stock_minimo'];
        $row['bajo_stock'] = ($stock <= $min) ? 1 : 0;
        $row['diferencia'] = $stock - $min;
    }

    return $rows;
}

// Reporte 5: Historial de pedidos
function getReportOrdersHistory(?string $status = null, ?string $from = null, ?string $to = null): array
{
    return getAllOrders($status, $from, $to);
}
