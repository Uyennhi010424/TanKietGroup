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
// Fallback when realpath() fails (e.g., Windows with Unicode path characters)
if ($filePath === false && $baseDir === false) {
    $candidate = __DIR__ . '/../uploads/' . $relativePath;
    if (is_file($candidate)) {
        $baseDir = __DIR__ . '/../uploads';
        $filePath = $candidate;
    }
} elseif ($filePath === false && $baseDir !== false) {
    $candidate = __DIR__ . '/../' . $relativePath;
    if (is_file($candidate)) {
        $filePath = $candidate;
    }
}

if ($baseDir === false) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

$checkPath = $filePath ?: $baseDir . '/' . $relativePath;
if (!str_starts_with(str_replace('\\', '/', $checkPath), str_replace('\\', '/', $baseDir))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

if ($filePath === false || !is_file($filePath)) {
    // Serve a 1x1 transparent placeholder instead of 404
    header('Content-Type: image/gif');
    header('Cache-Control: public, max-age=86400');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
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
    }
}

// Allow images and document files — block PHP, HTML, and other executable types
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
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