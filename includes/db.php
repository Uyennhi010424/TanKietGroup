<?php
// PDO database helper - returns a PDO instance
function get_db_connection(): PDO
{
    $cfg = require __DIR__ . '/../config/database.php';
    $host = $cfg['host'] ?? '127.0.0.1';
    $db   = $cfg['dbname'] ?? '';
    $user = $cfg['user'] ?? '';
    $pass = $cfg['pass'] ?? '';

    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Re-throw with a clearer message for the developer
        $hint = ' Configure credentials in config/database.local.php or env DB_HOST, DB_NAME, DB_USER, DB_PASS.';
        throw new RuntimeException('Database connection failed: ' . $e->getMessage() . $hint);
    }
}
