<?php
/**
 * Dynamic sitemap.xml generator
 * URL: /sitemap.xml (via .htaccess rewrite)
 */

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/includes/site.php';

$config = site_app_config();
$base = rtrim($config['base_url'] ?? 'https://tankietgroup.com', '/');

$urls = [];

// Static pages
$urls[] = ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'daily'];
$urls[] = ['loc' => $base . '/?page=about', 'priority' => '0.8', 'changefreq' => 'monthly'];
$urls[] = ['loc' => $base . '/?page=contact', 'priority' => '0.8', 'changefreq' => 'monthly'];
$urls[] = ['loc' => $base . '/?page=services', 'priority' => '0.8', 'changefreq' => 'weekly'];
$urls[] = ['loc' => $base . '/?page=courses', 'priority' => '0.7', 'changefreq' => 'weekly'];
$urls[] = ['loc' => $base . '/?page=projects', 'priority' => '0.7', 'changefreq' => 'weekly'];
$urls[] = ['loc' => $base . '/?page=blog', 'priority' => '0.7', 'changefreq' => 'daily'];
$urls[] = ['loc' => $base . '/?page=recruitments', 'priority' => '0.5', 'changefreq' => 'monthly'];

try {
    // Services
    $services = site_fetch_all('SELECT slug, updated_at FROM services WHERE status = 1');
    foreach ($services as $s) {
        $urls[] = [
            'loc' => $base . '/dich-vu/' . rawurlencode($s['slug']),
            'lastmod' => $s['updated_at'] ?? null,
            'priority' => '0.7',
        ];
    }

    // Projects
    $projects = site_fetch_all('SELECT slug, updated_at FROM projects WHERE status = 1');
    foreach ($projects as $p) {
        $urls[] = [
            'loc' => $base . '/du-an/' . rawurlencode($p['slug']),
            'lastmod' => $p['updated_at'] ?? null,
            'priority' => '0.6',
        ];
    }

    // Blog posts
    $posts = site_fetch_all("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'");
    foreach ($posts as $p) {
        $urls[] = [
            'loc' => $base . '/blog/' . rawurlencode($p['slug']),
            'lastmod' => $p['updated_at'] ?? null,
            'priority' => '0.6',
        ];
    }

    // Courses
    $courses = site_fetch_all('SELECT slug, updated_at FROM courses WHERE status = 1');
    foreach ($courses as $c) {
        $urls[] = [
            'loc' => $base . '/khoa-hoc/' . rawurlencode($c['slug']),
            'lastmod' => $c['updated_at'] ?? null,
            'priority' => '0.6',
        ];
    }
} catch (Throwable $e) {
    // If DB fails, still output static pages
    error_log('Sitemap DB error: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . '</lastmod>' . "\n";
    }
    echo '    <changefreq>' . ($u['changefreq'] ?? 'monthly') . '</changefreq>' . "\n";
    echo '    <priority>' . ($u['priority'] ?? '0.5') . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
