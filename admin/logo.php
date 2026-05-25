<?php
declare(strict_types=1);

$logoPath = __DIR__ . '/../img/logo.jpg';

if (!is_file($logoPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Logo not found';
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($logoPath));
readfile($logoPath);
