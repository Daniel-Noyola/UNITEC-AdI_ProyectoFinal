<?php

require_once __DIR__ . '/../conexion.php';

const INGREDIENT_UNITS = [
    'kg'  => 'Kilogramos (kg)',
    'g'   => 'Gramos (g)',
    'L'   => 'Litros (L)',
    'ml'  => 'Mililitros (ml)',
    'pza' => 'Piezas (pza)',
    'paq' => 'Paquetes (paq)',
];

function getIngredients(): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT * FROM ingredientes ORDER BY nombre ASC');
    return $stmt->fetchAll();
}

function saveIngredient(?int $id, string $name, float $stock, string $unit, float $minStock): bool
{
    if ($stock < 0 || $minStock < 0) {
        throw new InvalidArgumentException('El stock y el stock mínimo no pueden ser valores negativos.');
    }

    $cleanUnit = trim($unit);
    if (!array_key_exists($cleanUnit, INGREDIENT_UNITS)) {
        throw new InvalidArgumentException('La unidad de medida seleccionada no es válida.');
    }

    $pdo = getDbConnection();

    if ($id && $id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE ingredientes SET nombre = ?, cantidad_stock = ?, unidad_medida = ?, stock_minimo = ? WHERE id_ingrediente = ?'
        );
        return $stmt->execute([trim($name), $stock, $cleanUnit, $minStock, $id]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO ingredientes (nombre, cantidad_stock, unidad_medida, stock_minimo) VALUES (?, ?, ?, ?)'
    );
    return $stmt->execute([trim($name), $stock, $cleanUnit, $minStock]);
}
