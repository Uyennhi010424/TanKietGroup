<?php
declare(strict_types=1);

$relativePath = trim((string)($_GET['path'] ?? ''));
if ($relativePath === '' || preg_match('#^(https?:)?//#i', $relativePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found';
    exit;
}

// Reject path traversal attempts BEFORE any normalization
if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid path';
    exit;
}

// Normalize slashes and remove 'uploads/' prefix since baseDir already points to uploads/
$relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
if (str_starts_with($relativePath, 'uploads/')) {
    $relativePath = substr($relativePath, 8);
}

$baseDir = realpath(__DIR__ . '/../uploads');
if ($baseDir === false) {
    $baseDir = __DIR__ . '/../uploads';
}

$filePath = realpath($baseDir . '/' . $relativePath);

// Fallback for Windows Unicode paths
if ($filePath === false) {
    $candidate = $baseDir . '/' . $relativePath;
    if (is_file($candidate)) {
        $filePath = $candidate;
    }
}

// Verify path is within uploads directory
if ($filePath === false || !is_file($filePath)) {
    // Serve a 1x1 transparent placeholder instead of 404
    header('Content-Type: image/gif');
    header('Cache-Control: public, max-age=86400');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

$normalizedBase = str_replace('\\', '/', $baseDir);
$normalizedFile = str_replace('\\', '/', $filePath);
if (!str_starts_with($normalizedFile, $normalizedBase . '/')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

// Detect MIME type from file content
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

// Allow images and documents — block executables, HTML, and SVG (XSS risk)
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
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
header('X-Content-Type-Options: nosniff');
readfile($filePath);
