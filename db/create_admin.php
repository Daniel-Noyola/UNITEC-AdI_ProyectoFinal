<?php
/**
 * Script de Terminal para crear o actualizar el Administrador Inicial.
 * Uso: php db/create_admin.php
 */

require_once __DIR__ . '/conexion.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso denegado. Este script solo puede ejecutarse desde la terminal.\n");
}

echo "========================================================\n";
echo "   FOOD MANAGER - Creación de Administrador Inicial     \n";
echo "========================================================\n\n";

$name     = $argv[1] ?? null;
$email    = $argv[2] ?? null;
$password = $argv[3] ?? null;
$phone    = $argv[4] ?? null;

if (empty($name)) {
    echo "Nombre del Administrador: ";
    $name = trim((string)fgets(STDIN));
}
if (empty($email)) {
    echo "Correo Electrónico: ";
    $email = trim((string)fgets(STDIN));
}
if (empty($password)) {
    echo "Contraseña (mínimo 6 caracteres): ";
    $password = trim((string)fgets(STDIN));
}
if (empty($phone)) {
    echo "Teléfono (opcional): ";
    $phone = trim((string)fgets(STDIN));
}

if (empty($name) || empty($email) || empty($password)) {
    echo "\n[ERROR] Nombre, correo y contraseña son obligatorios.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\n[ERROR] El correo '$email' no tiene un formato válido.\n";
    exit(1);
}

if (strlen($password) < 6) {
    echo "\n[ERROR] La contraseña debe contener al menos 6 caracteres.\n";
    exit(1);
}

try {
    $pdo = getDbConnection();
    $check = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1');
    $check->execute([strtolower($email)]);
    $existing = $check->fetch();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        $update = $pdo->prepare(
            "UPDATE usuarios SET nombre = ?, contrasena_hash = ?, telefono = ?, rol = 'admin' WHERE id_usuario = ?"
        );
        $update->execute([$name, $hash, $phone ?: null, $existing['id_usuario']]);
        echo "\n[ÉXITO] El usuario '$email' fue actualizado al rol de ADMINISTRADOR.\n";
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO usuarios (nombre, correo, contrasena_hash, telefono, rol, fecha_registro)
             VALUES (?, ?, ?, ?, 'admin', NOW())"
        );
        $insert->execute([$name, strtolower($email), $hash, $phone ?: null]);
        echo "\n[ÉXITO] Administrador '$name' ($email) creado satisfactoriamente.\n";
    }
    exit(0);
} catch (Exception $e) {
    echo "\n[ERROR] Ocurrió un problema con la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
