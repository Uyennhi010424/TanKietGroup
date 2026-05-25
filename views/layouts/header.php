<!doctype html>
<html lang="vi">
<head>
<?php
require_once __DIR__ . '/../../includes/site.php';
$site = site_settings();
$logoUrl = site_image_url($site['logo'] ?? '', '/img/logo.jpg');
$metaTitle = trim((string)($site['meta_title'] ?? ''));
$metaDescription = trim((string)($site['meta_description'] ?? ''));
$resolvedTitle = $pageTitle ?? ($metaTitle !== '' ? $metaTitle : APP_NAME);
?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($resolvedTitle . ' | ' . ($site['site_name'] ?? APP_NAME), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription !== '' ? $metaDescription : 'TanKiet Group - Giải pháp Marketing tăng trưởng toàn diện cho doanh nghiệp hiện đại.', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <button class="menu-toggle" data-menu-toggle aria-expanded="false" aria-controls="main-nav">
            <span class="hamburger" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
            <span class="sr-only">Mở menu</span>
        </button>

        <a class="brand" href="/?page=home">
            <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($site['site_name'] ?? APP_NAME, ENT_QUOTES, 'UTF-8'); ?>" class="site-logo">
        </a>

        <nav id="main-nav" class="site-nav" data-main-nav>
            <ul>
                <li><a class="<?php echo ($currentPage ?? 'home') === 'home' ? 'active' : ''; ?>" href="/?page=home">Trang chủ</a></li>
                <li><a class="<?php echo ($currentPage ?? '') === 'about' ? 'active' : ''; ?>" href="/?page=about">Giới thiệu</a></li>

                <li class="has-dropdown">
                    <button class="dropdown-toggle" data-dropdown-toggle aria-expanded="false">Dịch vụ ▾</button>
                    <div class="mega-dropdown" data-dropdown>
                        <div class="dropdown-grid container">
                            <div class="dropdown-column">
                                <h4>Dịch vụ chính</h4>
                                <ul>
                                    <li><a href="/?page=services">Tất cả dịch vụ</a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>Marketing theo ngành</h4>
                                <ul>
                                    <li><a class="highlight" href="/?page=services">Giải pháp theo ngành</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>

                <li><a class="<?php echo ($currentPage ?? '') === 'courses' ? 'active' : ''; ?>" href="/?page=courses">Khóa học</a></li>
                <li><a class="<?php echo in_array($currentPage ?? '', ['projects', 'project_detail'], true) ? 'active' : ''; ?>" href="/?page=projects">Dự án</a></li>
                <li><a class="<?php echo in_array($currentPage ?? '', ['blog', 'blog_detail'], true) ? 'active' : ''; ?>" href="/?page=blog">Blog</a></li>
                <li><a class="<?php echo ($currentPage ?? '') === 'recruitments' ? 'active' : ''; ?>" href="/?page=recruitments">Tuyển dụng</a></li>
                <li><a class="<?php echo ($currentPage ?? '') === 'contact' ? 'active' : ''; ?>" href="/?page=contact">Liên hệ</a></li>
            </ul>
        </nav>

        <div class="header-cta">
            <a class="btn btn-primary" href="/?page=contact"><?php echo htmlspecialchars($site['hotline'] ? ('Gọi ' . $site['hotline']) : 'Liên hệ', ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>
</header>
