<?php
/**
 * Operaciones de base de datos para la entidad Pedidos y Detalle de Pedido.
 */

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../mesas/acciones.php';

const VALID_ORDER_TRANSITIONS = [
    'pendiente'  => ['preparando', 'cancelado'],
    'preparando' => ['entregado', 'cancelado'],
    'entregado'  => ['pagado', 'cancelado'],
    'pagado'     => [],
    'cancelado'  => [],
];

/**
 * Crea un pedido verificando existencias y descontando el inventario de forma segura.
 */
function createOrder(int $userId, ?int $tableId, array $items): int
{
    if (empty($items)) {
        throw new InvalidArgumentException('El pedido debe incluir al menos un platillo.');
    }

    // Agrupar cantidades por platillo
    $dishQuantities = [];
    foreach ($items as $item) {
        $dishId = (int)($item['id_platillo'] ?? 0);
        $qty    = (int)($item['cantidad'] ?? 0);
        if ($dishId > 0 && $qty > 0) {
            $dishQuantities[$dishId] = ($dishQuantities[$dishId] ?? 0) + $qty;
        }
    }

    if (empty($dishQuantities)) {
        throw new InvalidArgumentException('No se indicaron cantidades válidas para los platillos.');
    }

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    try {
        // 1. Validar mesa si se seleccionó una
        if ($tableId !== null && $tableId > 0) {
            $tableStmt = $pdo->prepare('SELECT estado FROM mesas WHERE id_mesa = ? FOR UPDATE');
            $tableStmt->execute([$tableId]);
            $tableStatus = $tableStmt->fetchColumn();

            if (!$tableStatus) {
                throw new RuntimeException('La mesa seleccionada no existe.');
            }
            if ($tableStatus === 'inactiva') {
                throw new RuntimeException('No se pueden abrir pedidos en una mesa inactiva.');
            }
            if ($tableStatus === 'ocupada') {
                throw new RuntimeException('La mesa seleccionada ya se encuentra ocupada.');
            }
        }

        // 2. Obtener precios y disponibilidad reales desde la base de datos
        $dishStmt = $pdo->prepare('SELECT id_platillo, nombre, precio, disponible FROM platillos WHERE id_platillo = ?');
        $dishesMap = [];
        foreach ($dishQuantities as $dishId => $qty) {
            $dishStmt->execute([$dishId]);
            $dish = $dishStmt->fetch();
            if (!$dish) {
                throw new RuntimeException('Uno o más platillos seleccionados no existen.');
            }
            if ((int)$dish['disponible'] !== 1) {
                throw new RuntimeException('El platillo "' . $dish['nombre'] . '" no está disponible actualmente.');
            }
            $dishesMap[$dishId] = $dish;
        }

        // 3. Calcular ingredientes requeridos según la receta de cada platillo
        $requiredIngredients = [];
        $recipeStmt = $pdo->prepare(
            'SELECT r.id_ingrediente, r.cantidad_requerida, i.nombre, i.unidad_medida
             FROM recetas r
             JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
             WHERE r.id_platillo = ?'
        );

        foreach ($dishQuantities as $dishId => $qty) {
            $recipeStmt->execute([$dishId]);
            $recipes = $recipeStmt->fetchAll();

            foreach ($recipes as $rec) {
                $ingId = (int)$rec['id_ingrediente'];
                $needed = (float)$rec['cantidad_requerida'] * $qty;

                if (!isset($requiredIngredients[$ingId])) {
                    $requiredIngredients[$ingId] = [
                        'nombre'        => $rec['nombre'],
                        'unidad_medida' => $rec['unidad_medida'],
                        'cantidad'      => 0.0,
                    ];
                }
                $requiredIngredients[$ingId]['cantidad'] += $needed;
            }
        }

        // 4. Bloquear ingredientes, validar stock y descontar
        $lockStmt   = $pdo->prepare('SELECT cantidad_stock FROM ingredientes WHERE id_ingrediente = ? FOR UPDATE');
        $deductStmt = $pdo->prepare('UPDATE ingredientes SET cantidad_stock = cantidad_stock - ? WHERE id_ingrediente = ?');

        foreach ($requiredIngredients as $ingId => $req) {
            $lockStmt->execute([$ingId]);
            $currentStock = (float)$lockStmt->fetchColumn();

            if ($currentStock < $req['cantidad']) {
                throw new RuntimeException(sprintf(
                    'Inventario insuficiente para "%s". Requerido: %.2f %s, en stock: %.2f %s.',
                    $req['nombre'],
                    $req['cantidad'],
                    $req['unidad_medida'],
                    $currentStock,
                    $req['unidad_medida']
                ));
            }

            $deductStmt->execute([$req['cantidad'], $ingId]);
        }

        // 5. Calcular total en el servidor e insertar pedido
        $totalOrder = 0.0;
        foreach ($dishQuantities as $dishId => $qty) {
            $totalOrder += (float)$dishesMap[$dishId]['precio'] * $qty;
        }

        $orderStmt = $pdo->prepare(
            "INSERT INTO pedidos (id_usuario, id_mesa, fecha_pedido, estado, total, fecha_cierre)
             VALUES (?, ?, NOW(), 'pendiente', ?, NULL)"
        );
        $orderStmt->execute([
            $userId,
            ($tableId && $tableId > 0) ? $tableId : null,
            $totalOrder,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // 6. Insertar detalles del pedido
        $detailStmt = $pdo->prepare(
            'INSERT INTO detalle_pedido (id_pedido, id_platillo, cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($dishQuantities as $dishId => $qty) {
            $unitPrice = (float)$dishesMap[$dishId]['precio'];
            $detailStmt->execute([$orderId, $dishId, $qty, $unitPrice, $unitPrice * $qty]);
        }

        // 7. Marcar mesa como ocupada si aplica
        if ($tableId && $tableId > 0) {
            $updTable = $pdo->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = ?");
            $updTable->execute([$tableId]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getOrderById(int $orderId): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT p.*, u.nombre AS usuario_nombre, u.correo AS usuario_correo, m.numero_mesa
         FROM pedidos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
         WHERE p.id_pedido = ? LIMIT 1'
    );
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

function getOrderDetails(int $orderId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT d.*, pl.nombre AS platillo_nombre, pl.categoria
         FROM detalle_pedido d
         JOIN platillos pl ON d.id_platillo = pl.id_platillo
         WHERE d.id_pedido = ?'
    );
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function getUserOrders(int $userId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT p.*, m.numero_mesa
         FROM pedidos p
         LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
         WHERE p.id_usuario = ?
         ORDER BY p.fecha_pedido DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllOrders(?string $statusFilter = null, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $pdo = getDbConnection();
    $conditions = [];
    $params = [];

    if ($statusFilter !== null && $statusFilter !== '') {
        $conditions[] = 'p.estado = ?';
        $params[] = $statusFilter;
    }
    if ($dateFrom !== null && $dateFrom !== '') {
        $conditions[] = 'DATE(p.fecha_pedido) >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo !== null && $dateTo !== '') {
        $conditions[] = 'DATE(p.fecha_pedido) <= ?';
        $params[] = $dateTo;
    }

    $sql = 'SELECT p.*, u.nombre AS usuario_nombre, m.numero_mesa
            FROM pedidos p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa';

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY p.fecha_pedido DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Cambia el estado de un pedido, repone inventario si se cancela y libera la mesa.
 */
function updateOrderStatus(int $orderId, string $newStatus): void
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE id_pedido = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new RuntimeException('El pedido especificado no existe.');
        }

        $currentStatus = $order['estado'];
        if ($currentStatus === $newStatus) {
            $pdo->commit();
            return;
        }

        $allowed = VALID_ORDER_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            throw new RuntimeException("Transición no permitida: de \"$currentStatus\" a \"$newStatus\".");
        }

        // Reponer inventario exactamente una vez si se cancela
        if ($newStatus === 'cancelado') {
            $detailsStmt = $pdo->prepare('SELECT id_platillo, cantidad FROM detalle_pedido WHERE id_pedido = ?');
            $detailsStmt->execute([$orderId]);
            $details = $detailsStmt->fetchAll();

            $recStmt     = $pdo->prepare('SELECT id_ingrediente, cantidad_requerida FROM recetas WHERE id_platillo = ?');
            $restoreStmt = $pdo->prepare('UPDATE ingredientes SET cantidad_stock = cantidad_stock + ? WHERE id_ingrediente = ?');

            foreach ($details as $d) {
                $recStmt->execute([$d['id_platillo']]);
                foreach ($recStmt->fetchAll() as $r) {
                    $restoreStmt->execute([(float)$r['cantidad_requerida'] * (int)$d['cantidad'], $r['id_ingrediente']]);
                }
            }
        }

        if ($newStatus === 'pagado') {
            $upd = $pdo->prepare('UPDATE pedidos SET estado = ?, fecha_cierre = NOW() WHERE id_pedido = ?');
            $upd->execute([$newStatus, $orderId]);
        } else {
            $upd = $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
            $upd->execute([$newStatus, $orderId]);
        }

        if (!empty($order['id_mesa'])) {
            syncTableOccupancy($pdo, (int)$order['id_mesa']);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
