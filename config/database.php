<?php
// Database connection settings
// Priority: defaults < database.local.php < .env < environment variables

// Load .env helper (reads .env file into environment)
require_once __DIR__ . '/../includes/env.php';

$config = [
    'host' => 'localhost',
    'dbname' => 'tankietgroup',
    'user' => 'root',
    'pass' => '',
];

// Legacy: database.local.php override (for backward compatibility)
$localConfigPath = __DIR__ . '/database.local.php';
if (is_file($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

// Environment variables override (from .env or system env)
$envMap = [
    'host' => ['DB_HOST', 'MYSQL_HOST'],
    'dbname' => ['DB_NAME', 'MYSQL_DATABASE'],
    'user' => ['DB_USER', 'MYSQL_USER'],
    'pass' => ['DB_PASS', 'MYSQL_PASSWORD'],
];

foreach ($envMap as $key => $names) {
    foreach ($names as $envName) {
        $value = getenv($envName);
        if ($value !== false && $value !== '') {
            $config[$key] = $value;
            break;
        }
    }
}

return $config;
