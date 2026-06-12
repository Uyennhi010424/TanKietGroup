<?php
// Router for USER-FACING pages only (port 8000)
define('APP_MODE', 'user');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve static files (CSS, JS, images)
$file = realpath(__DIR__ . $uri);
if ($file && is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css', 'js' => 'application/javascript',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'gif' => 'image/gif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ico' => 'image/x-icon',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}

// Serve user pages through index.php
require __DIR__ . '/index.php';
