<?php
// Shared admin helper functions — included by all admin pages to eliminate duplication.

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

/**
 * HTML-escape a value for safe output.
 */
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Convert Vietnamese text to a URL-safe slug.
 */
function make_slug(string $text): string
{
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

/**
 * Append query parameters to a route URL.
 */
function with_query(string $route, array $params): string
{
    $sep = strpos($route, '?') !== false ? '&' : '?';
    return $route . $sep . http_build_query($params);
}

/**
 * Return the standard admin routes array.
 */
function admin_routes(): array
{
    return [
        'dashboard'      => site_page_url('admin_index'),
        'courses'        => site_page_url('admin_courses'),
        'projects'       => site_page_url('admin_projects'),
        'services'       => site_page_url('admin_services'),
        'users'          => site_page_url('admin_users'),
        'blog'           => site_page_url('admin_blog'),
        'recruitments'   => site_page_url('admin_recruitments'),
        'applications'   => site_page_url('admin_applications'),
        'stats'          => site_page_url('admin_stats'),
        'settings'       => site_page_url('admin_settings'),
        'consultations'  => site_page_url('admin_consultations'),
        'clients'        => site_page_url('admin_clients'),
    ];
}

/**
 * Initialize admin page: require login, load user info, return common variables.
 * Returns: ['routes' => [...], 'loginRoute' => ..., 'user' => [...], 'role' => ..., 'isEditor' => bool, 'assetBase' => ..., 'logoUrl' => ...]
 *
 * Options:
 *   - 'require_admin' => true  — restrict to admin role only
 */
function admin_init(array $options = []): array
{
    $loginRoute = site_page_url('admin_login');
    admin_require_login($loginRoute);

    if (!empty($options['require_admin'])) {
        admin_require_roles(['admin'], site_page_url('admin_courses'));
    }

    $user = admin_current_user() ?? [];
    $role = (string)($user['role'] ?? 'editor');

    // Use simple media endpoint for uploads paths (avoids realpath() issues on Windows with Unicode paths)
    $site = site_settings();
    $logoSetting = trim((string)($site['logo'] ?? ''));
    if ($logoSetting !== '' && str_starts_with($logoSetting, 'uploads/')) {
        $logoUrl = '/media.php?path=' . rawurlencode($logoSetting);
    } else {
        $logoUrl = site_logo_url('/img/logo.jpg');
    }

    $favSetting = trim((string)($site['favicon'] ?? ''));
    if ($favSetting === '' && $logoSetting !== '') {
        $favSetting = $logoSetting;
    }
    if ($favSetting !== '' && str_starts_with($favSetting, 'uploads/')) {
        $faviconUrl = '/media.php?path=' . rawurlencode($favSetting);
    } else {
        $faviconUrl = site_favicon_url('/img/favicon.ico');
    }

    return [
        'routes'    => admin_routes(),
        'loginRoute'=> $loginRoute,
        'user'      => $user,
        'role'      => $role,
        'isEditor'  => $role === 'editor',
        'assetBase' => site_admin_base_path(),
        'logoUrl'   => $logoUrl,
        'faviconUrl'=> $faviconUrl,
    ];
}
