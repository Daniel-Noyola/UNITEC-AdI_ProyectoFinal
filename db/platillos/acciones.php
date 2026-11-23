<?php
/**
 * Operaciones de base de datos para la entidad Platillos.
 */

require_once __DIR__ . '/../conexion.php';

const DISH_CATEGORIES = [
    'Entradas',
    'Platos Fuertes',
    'Ensaladas',
    'Bebidas',
    'Postres',
    'Desayunos',
];

function getDishes(bool $onlyAvailable = false, ?string $category = null): array
{
    $pdo = getDbConnection();
    $conditions = [];
    $params = [];

    if ($onlyAvailable) {
        $conditions[] = 'disponible = 1';
    }

    if ($category !== null && $category !== '') {
        $conditions[] = 'categoria = ?';
        $params[] = $category;
    }

    $sql = 'SELECT * FROM platillos';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY categoria ASC, nombre ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDishById(int $dishId): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM platillos WHERE id_platillo = ? LIMIT 1');
    $stmt->execute([$dishId]);
    return $stmt->fetch() ?: null;
}

function getCategories(): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM platillos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function saveDish(?int $id, string $name, ?string $description, float $price, string $category, int $available, ?string $image = null): int
{
    if ($price < 0) {
        throw new InvalidArgumentException('El precio del platillo no puede ser negativo.');
    }

    $cleanCategory = trim($category);
    if (!in_array($cleanCategory, DISH_CATEGORIES, true)) {
        throw new InvalidArgumentException('La categoría seleccionada no es válida.');
    }

    $pdo = getDbConnection();
    $desc = $description ? trim($description) : null;
    $disp = $available ? 1 : 0;

    if ($id && $id > 0) {
        if ($image !== null) {
            $stmt = $pdo->prepare(
                'UPDATE platillos SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, disponible = ?, imagen = ? WHERE id_platillo = ?'
            );
            $stmt->execute([trim($name), $desc, $price, $cleanCategory, $disp, $image, $id]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE platillos SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, disponible = ? WHERE id_platillo = ?'
            );
            $stmt->execute([trim($name), $desc, $price, $cleanCategory, $disp, $id]);
        }
        return $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO platillos (nombre, descripcion, precio, categoria, disponible, imagen) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([trim($name), $desc, $price, $cleanCategory, $disp, $image]);

    return (int)$pdo->lastInsertId();
}

function toggleDishAvailability(int $dishId): bool
{
    $dish = getDishById($dishId);
    if (!$dish) {
        return false;
    }

    $newStatus = ((int)$dish['disponible'] === 1) ? 0 : 1;
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE platillos SET disponible = ? WHERE id_platillo = ?');
    return $stmt->execute([$newStatus, $dishId]);
}
