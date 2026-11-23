<?php

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $port = '3306'; // En MAMP suele ser 8889; en WAMP/XAMPP/Docker suele ser 3306
        $dbName = 'food_manager';
        $user = 'root';
        $pass = 'root';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Error al conectar con la base de datos: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
