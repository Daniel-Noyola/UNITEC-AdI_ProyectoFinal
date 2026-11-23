<?php
/**
 * Operaciones de base de datos para la entidad Usuarios.
 */

require_once __DIR__ . '/../conexion.php';

function emailExists(string $email): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    return (bool)$stmt->fetch();
}

function registerUser(string $name, string $email, string $password, ?string $phone, string $role = 'cliente'): int
{
    $pdo = getDbConnection();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $validRole = ($role === 'admin') ? 'admin' : 'cliente';

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, correo, contrasena_hash, telefono, rol, fecha_registro)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        trim($name),
        strtolower(trim($email)),
        $hash,
        $phone ? trim($phone) : null,
        $validRole,
    ]);

    return (int)$pdo->lastInsertId();
}

function authenticateUser(string $email, string $password): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contrasena_hash'])) {
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        unset($user['contrasena_hash']);
        $_SESSION['user'] = $user;
        return $user;
    }

    return null;
}
