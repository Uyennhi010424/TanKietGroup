<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

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
        'favicon' => '',
        'meta_title' => APP_NAME,
        'meta_description' => APP_NAME,
        'hotline' => '',
        'email' => '',
        'address' => '',
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

    return '/?page=admin_media&path=' . rawurlencode($path);
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

    if (str_starts_with($path, '/')) {
        return $path;
    }

    return '/' . ltrim($path, '/');
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