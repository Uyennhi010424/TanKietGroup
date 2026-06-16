<?php
/**
 * Dynamic Sitemap Generator
 * Generates sitemap.xml from database content
 */

require_once __DIR__ . '/../includes/site.php';

$baseUrl = rtrim((string)($site['site_url'] ?? 'https://tankiet.group'), '/');
if ($baseUrl === '' || $baseUrl === 'https://') {
    $baseUrl = 'https://tankiet.group';
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => '/?page=about', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/?page=services', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => '/?page=courses', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => '/?page=projects', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => '/?page=blog', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => '/?page=contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/?page=recruitments', 'priority' => '0.6', 'changefreq' => 'monthly'],
];

foreach ($staticPages as $page) {
    echo "<url>\n";
    echo "  <loc>{$baseUrl}{$page['loc']}</loc>\n";
    echo "  <priority>{$page['priority']}</priority>\n";
    echo "  <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "</url>\n";
}

// Services
try {
    $services = site_fetch_all('SELECT slug, updated_at FROM services WHERE status = 1');
    foreach ($services as $s) {
        $lastmod = date('Y-m-d', strtotime((string)($s['updated_at'] ?? 'now')));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/dich-vu/" . htmlspecialchars($s['slug'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "  <lastmod>{$lastmod}</lastmod>\n";
        echo "  <priority>0.8</priority>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "</url>\n";
    }
} catch (Throwable $e) {
    // ignore
}

// Projects
try {
    $projects = site_fetch_all('SELECT slug, updated_at FROM projects WHERE status = 1');
    foreach ($projects as $p) {
        $lastmod = date('Y-m-d', strtotime((string)($p['updated_at'] ?? 'now')));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/du-an/" . htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "  <lastmod>{$lastmod}</lastmod>\n";
        echo "  <priority>0.7</priority>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "</url>\n";
    }
} catch (Throwable $e) {
    // ignore
}

// Blog posts
try {
    $posts = site_fetch_all('SELECT slug, updated_at, published_at FROM blog_posts WHERE status = "published"');
    foreach ($posts as $p) {
        $lastmod = date('Y-m-d', strtotime((string)($p['updated_at'] ?? $p['published_at'] ?? 'now')));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/blog/" . htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "  <lastmod>{$lastmod}</lastmod>\n";
        echo "  <priority>0.7</priority>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "</url>\n";
    }
} catch (Throwable $e) {
    // ignore
}

// Courses
try {
    $courses = site_fetch_all('SELECT slug, updated_at FROM courses WHERE status = 1');
    foreach ($courses as $c) {
        $lastmod = date('Y-m-d', strtotime((string)($c['updated_at'] ?? 'now')));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/khoa-hoc/" . htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "  <lastmod>{$lastmod}</lastmod>\n";
        echo "  <priority>0.7</priority>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "</url>\n";
    }
} catch (Throwable $e) {
    // ignore
}

echo '</urlset>';
