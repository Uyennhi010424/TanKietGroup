<?php
/**
 * Simple media endpoint for serving uploads.
 * Works on all ports without admin framework overhead.
 */
$relativePath = trim((string)($_GET['path'] ?? ''));

if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid path';
    exit;
}

// Only allow uploads directory
if (!str_starts_with($relativePath, 'uploads/') && !str_starts_with($relativePath, '/uploads/')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

$relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
$baseDir = __DIR__ . '/uploads';
$filePath = $baseDir . '/' . substr($relativePath, 8); // Remove 'uploads/' prefix

// Try realpath first, fallback to direct path
$resolved = realpath($filePath);
if ($resolved === false) {
    $resolved = $filePath;
}

// Verify file exists
if (!is_file($resolved)) {
    // Serve placeholder for missing images
    header('Content-Type: image/gif');
    header('Cache-Control: public, max-age=3600');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// Security: verify path is within uploads
$normalizedBase = str_replace('\\', '/', realpath($baseDir) ?: $baseDir);
$normalizedFile = str_replace('\\', '/', $resolved);
if (!str_starts_with($normalizedFile, $normalizedBase . '/')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

// Detect MIME type
$ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
$mimes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png', 'webp' => 'image/webp',
    'gif' => 'image/gif', 'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
];

$mime = $mimes[$ext] ?? 'application/octet-stream';

// Serve file
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($resolved));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($resolved);
