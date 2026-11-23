<?php
/**
 * Operaciones para restablecer los datos y ejecutar el seeder independiente.
 */

require_once __DIR__ . '/../conexion.php';

/**
 * Lee las sentencias SQL ejecutables de docs/seeder.sql ignorando comentarios.
 */
function loadSeederStatements(string $seederPath): array
{
    if (!is_file($seederPath)) {
        throw new RuntimeException('No se encontró el archivo del seeder en docs/seeder.sql.');
    }

    $content = file_get_contents($seederPath);
    if ($content === false) {
        throw new RuntimeException('No se pudo leer el archivo docs/seeder.sql.');
    }

    // Eliminar comentarios de línea SQL (-- ...) antes de dividir por punto y coma
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $cleanLines = array_filter($lines, static function (string $line): bool {
        $trimmed = ltrim($line);
        return $trimmed !== '' && strpos($trimmed, '--') !== 0;
    });

    $sql = implode("\n", $cleanLines);
    $rawStatements = explode(';', $sql);

    $statements = [];
    foreach ($rawStatements as $stmt) {
        $trimmed = trim($stmt);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
    }

    if (empty($statements)) {
        throw new RuntimeException('El archivo docs/seeder.sql no contiene sentencias ejecutables.');
    }

    return $statements;
}

/**
 * Borra el contenido de la base de datos (conservando al admin actual) y carga el seeder.
 */
function resetDatabaseAndRunSeeder(int $preserveUserId): void
{
    $statements = loadSeederStatements(__DIR__ . '/../../docs/seeder.sql');
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    try {
        // Eliminar en orden dependiente para respetar todas las llaves foráneas
        $tablesToClear = [
            'detalle_pedido',
            'pedidos',
            'recetas',
            'platillos',
            'ingredientes',
            'mesas',
        ];

        foreach ($tablesToClear as $table) {
            $pdo->exec("DELETE FROM `$table`");
        }

        // Conservar al administrador en sesión para mantener integridad referencial y acceso
        $stmtUsers = $pdo->prepare('DELETE FROM `usuarios` WHERE `id_usuario` != ?');
        $stmtUsers->execute([$preserveUserId]);

        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
