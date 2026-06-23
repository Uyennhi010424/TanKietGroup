<?php
// Router for admin panel — run: cd admin && php -S localhost:8001 router.php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve static files from admin/ directory (CSS, JS, images)
$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
if ($ext && $ext !== 'php' && is_file(__DIR__ . $uri)) {
    $mimes = [
        'css' => 'text/css', 'js' => 'application/javascript',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize(__DIR__ . $uri));
    readfile(__DIR__ . $uri);
    return true;
}

// Serve static files from project root (assets/, img/)
if (preg_match('#^/(assets|img)/(.+)$#', $uri, $m)) {
    $file = realpath(__DIR__ . '/../' . $m[1] . '/' . $m[2]);
    if ($file && is_file($file)) {
        $ext2 = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        ];
        header('Content-Type: ' . ($mimes[$ext2] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}

// Serve media.php from project root
if ($uri === '/media.php' || str_starts_with($uri, '/media.php')) {
    require __DIR__ . '/../media.php';
    return true;
}

// Serve uploads from project root
if (preg_match('#^/uploads/(.+)$#', $uri, $m)) {
    $file = realpath(__DIR__ . '/../uploads/' . $m[1]);
    if (!$file && is_file(__DIR__ . '/../uploads/' . $m[1])) $file = __DIR__ . '/../uploads/' . $m[1];
    if ($file && is_file($file)) {
        $ext3 = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp',
            'gif' => 'image/gif', 'pdf' => 'application/pdf',
        ];
        header('Content-Type: ' . ($mimes[$ext3] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}

// Login page
if (($_GET['page'] ?? '') === 'admin_login' || $uri === '/login.php') {
    require __DIR__ . '/login.php';
    return true;
}

// API routes from project root
if (preg_match('#^/api/([a-z_]+)\.php$#', $uri, $m)) {
    $apiFile = __DIR__ . '/../api/' . $m[1] . '.php';
    if (is_file($apiFile)) { require $apiFile; return true; }
}

// Default: admin index
require __DIR__ . '/index.php';
