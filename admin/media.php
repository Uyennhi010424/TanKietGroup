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

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
readfile($filePath);