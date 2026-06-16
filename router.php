<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath(__DIR__ . $uri);

// Only serve non-PHP static files directly
if ($file && is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css', 'js' => 'application/javascript',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'gif' => 'image/gif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'txt' => 'text/plain',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    // Force download SVG files to prevent embedded script execution
    if ($ext === 'svg') {
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('X-Content-Type-Options: nosniff');
    }
    readfile($file);
    return true;
}

// Fallback to application front controller
require __DIR__ . '/index.php';
