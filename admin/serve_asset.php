<?php
// Serve admin CSS/JS files via PHP — avoids static file routing issues
$file = $_GET['file'] ?? '';

$allowed = [
    'css' => 'assets/css/admin.css',
    'js'  => 'assets/js/admin.js',
];

if (!isset($allowed[$file])) {
    http_response_code(404);
    exit('Not found');
}

$filePath = __DIR__ . '/' . $allowed[$file];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('Not found');
}

$ext = pathinfo($filePath, PATHINFO_EXTENSION);
$mimes = ['css' => 'text/css', 'js' => 'application/javascript'];
header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=86400');
readfile($filePath);
