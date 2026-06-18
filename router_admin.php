<?php
// Router for ADMIN pages only (port 8001)
define('APP_MODE', 'admin');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = $_SERVER['QUERY_STRING'] ?? '';

// Redirect root to admin dashboard
if ($uri === '/' && $query === '') {
    header('Location: /?page=admin_index');
    exit;
}

// Serve admin static files (CSS, JS)
if (preg_match('#^/admin/assets/(css|js)/(.+)$#', $uri, $m)) {
    $file = realpath(__DIR__ . '/admin/assets/' . $m[1] . '/' . $m[2]);
    if ($file && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = ['css' => 'text/css', 'js' => 'application/javascript'];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}

// Serve API routes
if (preg_match('#^/api/([a-z_]+)\.php$#', $uri, $m)) {
    $apiFile = __DIR__ . '/api/' . $m[1] . '.php';
    if (is_file($apiFile)) {
        require $apiFile;
        exit;
    }
}

// Serve media.php directly
if ($uri === '/media.php' || str_starts_with($uri, '/media.php?')) {
    require __DIR__ . '/media.php';
    exit;
}

// Serve upload files (for media endpoint)
if (preg_match('#^/uploads/(.+)$#', $uri, $m)) {
    $file = realpath(__DIR__ . '/uploads/' . $m[1]);
    // Fallback when realpath() fails (e.g., Windows with Unicode path characters)
    if (!$file && !str_contains($m[1], '..') && $m[1] !== '') {
        $candidate = __DIR__ . '/uploads/' . $m[1];
        if (is_file($candidate)) {
            $file = $candidate;
        }
    }
    if ($file && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx'];
        if (in_array($ext, $allowedExts, true)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($file));
            // Force download SVG files to prevent embedded script execution
            if ($ext === 'svg') {
                header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                header('X-Content-Type-Options: nosniff');
            }
            readfile($file);
            return true;
        }
    }
}

// Default: serve admin pages through index.php
require __DIR__ . '/index.php';
