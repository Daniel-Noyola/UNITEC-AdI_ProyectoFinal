<?php

require_once __DIR__ . '/../conexion.php';

function getTables(): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT * FROM mesas ORDER BY numero_mesa ASC');
    return $stmt->fetchAll();
}

function getTableById(int $tableId): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM mesas WHERE id_mesa = ? LIMIT 1');
    $stmt->execute([$tableId]);
    return $stmt->fetch() ?: null;
}

function saveTable(?int $id, int $number, int $capacity, string $status): bool
{
    $pdo = getDbConnection();

    if ($id && $id > 0) {
        $stmt = $pdo->prepare('UPDATE mesas SET numero_mesa = ?, capacidad = ?, estado = ? WHERE id_mesa = ?');
        return $stmt->execute([$number, $capacity, $status, $id]);
    }

    $stmt = $pdo->prepare('INSERT INTO mesas (numero_mesa, capacidad, estado) VALUES (?, ?, ?)');
    return $stmt->execute([$number, $capacity, $status]);
}

function toggleTableStatus(int $tableId): bool
{
    $pdo = getDbConnection();
    $table = getTableById($tableId);
    if (!$table) {
        return false;
    }

    if ($table['estado'] === 'ocupada') {
        throw new RuntimeException('No se puede desactivar una mesa que se encuentra ocupada con pedidos activos.');
    }

    $newStatus = ($table['estado'] === 'inactiva') ? 'disponible' : 'inactiva';
    $stmt = $pdo->prepare('UPDATE mesas SET estado = ? WHERE id_mesa = ?');
    return $stmt->execute([$newStatus, $tableId]);
}

/**
 * Actualiza el estado de la mesa según si aún tiene pedidos activos.
 */
function syncTableOccupancy(PDO $pdo, int $tableId): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM pedidos
         WHERE id_mesa = ? AND estado IN ('pendiente', 'preparando', 'entregado')"
    );
    $stmt->execute([$tableId]);
    $activeOrders = (int)$stmt->fetchColumn();

    $tableStmt = $pdo->prepare('SELECT estado FROM mesas WHERE id_mesa = ?');
    $tableStmt->execute([$tableId]);
    $currentStatus = $tableStmt->fetchColumn();

    if ($currentStatus !== 'inactiva') {
        $newStatus = ($activeOrders > 0) ? 'ocupada' : 'disponible';
        $upd = $pdo->prepare('UPDATE mesas SET estado = ? WHERE id_mesa = ?');
        $upd->execute([$newStatus, $tableId]);
    }
}
