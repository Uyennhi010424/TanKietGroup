<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function site_app_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    // Ensure env helper is loaded (for env() function in config)
    $envHelper = __DIR__ . '/env.php';
    if (is_file($envHelper)) {
        require_once $envHelper;
    }

    $configFile = __DIR__ . '/../config/config.php';
    if (is_file($configFile)) {
        $loaded = require $configFile;
        $config = is_array($loaded) ? $loaded : [];
    } else {
        $config = [];
    }

    return $config;
}

function site_db(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }

    $db = get_db_connection();
    return $db;
}

function site_settings(): array
{
    static $settings = null;
    if (is_array($settings)) {
        return $settings;
    }

    $settings = [
        'site_name' => APP_NAME,
        'logo' => '',
        'banner' => '',
        'favicon' => '',
        'meta_title' => APP_NAME,
        'meta_description' => APP_NAME,
        'meta_keywords' => '',
        'hotline' => '',
        'email' => '',
        'address' => '',
        'company_info' => '',
        'facebook' => '',
        'tiktok' => '',
        'youtube' => '',
    ];

    try {
        $row = site_db()->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1')->fetch();
        if (is_array($row)) {
            $settings = array_merge($settings, $row);
        }
    } catch (Throwable $e) {
        // keep defaults when settings table is unavailable
    }

    return $settings;
}

function site_public_media_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    // Use simple media endpoint for uploads (avoids realpath() issues on Windows with Unicode paths)
    if (str_starts_with($path, 'uploads/')) {
        return '/media.php?path=' . rawurlencode($path);
    }

    // Serve other files directly
    return site_base_path() . '/' . ltrim($path, '/');
}

function site_image_url(?string $path, string $fallback = ''): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return $fallback;
    }

    if (str_starts_with($path, 'uploads/')) {
        return site_public_media_url($path);
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    // If path starts with '/', treat it as site-root-relative and prefix base path
    if (str_starts_with($path, '/')) {
        return site_base_path() . $path;
    }

    return site_base_path() . '/' . ltrim($path, '/');
}

/**
 * Get image URL, preferring WebP version if it exists on disk.
 * Falls back to original if WebP not found.
 */
function site_image_url_webp(?string $path, string $fallback = ''): string
{
    $url = site_image_url($path, $fallback);

    // Only check for WebP for local files (not external URLs)
    if (preg_match('#^(https?:)?//#i', $url)) {
        return $url;
    }

    // Check if a WebP version exists on disk
    $relPath = trim((string)$path);
    if ($relPath !== '' && !preg_match('#^(https?:)?//#i', $relPath)) {
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $relPath);
        $fullPath = __DIR__ . '/../' . ltrim($webpPath, '/');
        if (is_file($fullPath)) {
            return site_image_url($webpPath, $fallback);
        }
    }

    return $url;
}

function site_logo_url(string $fallback = '/img/logo.jpg'): string
{
    $site = site_settings();
    $logoPath = trim((string)($site['logo'] ?? ''));

    if ($logoPath === '') {
        return site_image_url($fallback, $fallback);
    }

    return site_image_url($logoPath, $fallback);
}

function site_base_path(): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    // Normalize common values: when running under CLI or some servers dirname() may return '.' or '/'.
    if ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/') {
        return '';
    }

    return $scriptDir;
}

function site_page_url(string $page, array $params = []): string
{
    $url = site_base_path() . '/?page=' . rawurlencode($page);
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }

    return $url;
}

function site_admin_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(dirname($scriptName), '/');

    if ($scriptDir !== '' && preg_match('#/admin$#', $scriptDir)) {
        return $scriptDir;
    }

    $basePath = site_base_path();
    return ($basePath !== '' ? $basePath : '') . '/admin';
}

function site_admin_url(string $path = ''): string
{
    $basePath = rtrim(site_admin_base_path(), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $basePath : $basePath . '/' . $path;
}

function site_fetch_all(string $sql, array $params = []): array
{
    $stmt = site_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function site_fetch_one(string $sql, array $params = []): ?array
{
    $stmt = site_db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function site_parse_json(?string $value, array $default = []): array
{
    $value = trim((string)$value);
    if ($value === '') {
        return $default;
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $default;
}

function site_slugify(string $text): string
{
    // Delegate to make_slug() if available (defined in admin_helpers.php)
    if (function_exists('make_slug')) {
        return make_slug($text);
    }

    // Fallback when admin_helpers.php is not loaded
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ă' => 'a', 'đ' => 'd',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ơ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'item';
}

function site_favicon_url(string $fallback = '/img/favicon.ico'): string
{
    $site = site_settings();
    $fav = trim((string)($site['favicon'] ?? ''));
    if ($fav !== '') {
        return site_image_url($fav, $fallback);
    }

    // If no explicit favicon, fall back to logo when available
    $logo = trim((string)($site['logo'] ?? ''));
    if ($logo !== '') {
        return site_image_url($logo, $fallback);
    }

    return site_image_url($fallback, $fallback);
}

function site_admin_asset_url(string $path): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(dirname($scriptName), '/');

    // Nếu đang chạy với -t admin thì scriptDir không có /admin
    // Nếu chạy từ gốc project thì scriptDir có /admin
    if (preg_match('#/admin$#', $scriptDir)) {
        // Chạy từ gốc: /admin/assets/css/admin.css
        return $scriptDir . '/assets/' . ltrim($path, '/');
    }

    // Chạy với -t admin: /assets/css/admin.css
    return '/assets/' . ltrim($path, '/');
}
