<?php
declare(strict_types=1);

$relativePath = trim((string)($_GET['path'] ?? ''));
if ($relativePath === '' || preg_match('#^(https?:)?//#i', $relativePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

$relativePath = ltrim(str_replace(['..', '\\'], ['', '/'], $relativePath), '/');
$baseDir = realpath(__DIR__ . '/../uploads');
$filePath = $baseDir !== false ? realpath(__DIR__ . '/../' . $relativePath) : false;

if ($baseDir === false || $filePath === false || !str_starts_with(str_replace('\\', '/', $filePath), str_replace('\\', '/', $baseDir))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

if (!is_file($filePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $filePath);
        if (is_string($detected) && $detected !== '') {
            $mime = $detected;
        }
        finfo_close($finfo);
    }
}

// Only allow serving image files — block PHP, HTML, and other executable types
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=86400');
readfile($filePath);