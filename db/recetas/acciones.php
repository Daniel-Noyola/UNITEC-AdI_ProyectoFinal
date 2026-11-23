<?php

require_once __DIR__ . '/../conexion.php';

function getRecipeForDish(int $dishId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT r.id_ingrediente, r.cantidad_requerida, i.nombre, i.unidad_medida, i.cantidad_stock
         FROM recetas r
         JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
         WHERE r.id_platillo = ?
         ORDER BY i.nombre ASC'
    );
    $stmt->execute([$dishId]);
    return $stmt->fetchAll();
}

function saveRecipe(int $dishId, array $items): void
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    try {
        $del = $pdo->prepare('DELETE FROM recetas WHERE id_platillo = ?');
        $del->execute([$dishId]);

        $ins = $pdo->prepare(
            'INSERT INTO recetas (id_platillo, id_ingrediente, cantidad_requerida) VALUES (?, ?, ?)'
        );

        foreach ($items as $item) {
            $ingId = (int)($item['id_ingrediente'] ?? 0);
            $qty   = (float)($item['cantidad_requerida'] ?? 0);
            if ($ingId > 0 && $qty > 0) {
                $ins->execute([$dishId, $ingId, $qty]);
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
