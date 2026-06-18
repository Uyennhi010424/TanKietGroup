<?php
// Router for USER-FACING pages only (port 8000)
define('APP_MODE', 'user');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve media.php directly (for uploads with Unicode paths)
if ($uri === '/media.php' || str_starts_with($uri, '/media.php?')) {
    require __DIR__ . '/media.php';
    exit;
}

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
