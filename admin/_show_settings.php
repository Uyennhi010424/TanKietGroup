<?php
require_once __DIR__ . '/../includes/db.php';
try {
    $db = get_db_connection();
    $stmt = $db->query('SHOW CREATE TABLE settings');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
