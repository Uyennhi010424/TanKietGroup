<?php
// Database connection settings
// Priority: defaults < database.local.php < environment variables

$config = [
    'host' => 'localhost',
    'dbname' => 'tankietgroup',
    'user' => 'root',
    'pass' => '',
];

$localConfigPath = __DIR__ . '/database.local.php';
if (is_file($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

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
