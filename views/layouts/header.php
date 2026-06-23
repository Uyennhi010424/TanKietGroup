<!doctype html>
<html lang="vi">

<head>
    <?php
    require_once __DIR__ . '/../../includes/site.php';
    $site = site_settings();
    $logoUrl = site_logo_url('/img/logo.jpg');
    $metaTitle = trim((string)($site['meta_title'] ?? ''));
    $metaDescription = trim((string)($site['meta_description'] ?? ''));
    $resolvedTitle = $pageTitle ?? ($metaTitle !== '' ? $metaTitle : APP_NAME);
    $siteName = $site['site_name'] ?? APP_NAME;
    $fullTitle = $resolvedTitle . ' | ' . $siteName;

    // Per-page meta description (set by detail views via $metaDescOverride)
    $pageDescription = $metaDescOverride ?? ($metaDescription !== '' ? $metaDescription : 'TanKiet Group - Giải pháp Marketing tăng trưởng toàn diện cho doanh nghiệp hiện đại.');

    // Per-page OG image (set by detail views via $ogImageOverride)
    $ogImage = $ogImageOverride ?? site_base_path() . '/img/hero.jpg';

    // Canonical URL
    $canonicalUrl = $canonicalUrlOverride ?? ((isset($_SERVER['REQUEST_URI']) ? strtok((string)$_SERVER['REQUEST_URI'], '?') : '/'));

    /**
     * Dịch vụ chính — slug phải khớp với cột service_type trong bảng services
     * URL: ?page=services_by_type&slug={service_type_slug}
     */
    $serviceTypes = [
        'marketing-tron-goi' => 'Marketing trọn gói (Chiến lược xây kênh)',
        'cham-soc-fanpage'   => 'Chăm sóc Fanpage',
        'san-xuat-video'     => 'Sản xuất Video',
        'to-chuc-su-kien'    => 'Tổ chức sự kiện',
        'thiet-ke-website'   => 'Thiết kế Website chuẩn SEO',
    ];

    /**
     * Ngành — slug phải khớp với cột slug trong bảng industries
     * URL: ?page=industry_detail&slug={industry_slug}
     */
    $industries = [
        'marketing-cho-xay-dung-noi-bat' => 'Xây dựng',
        'marketing-cho-bat-dong-san'      => 'Bất động sản',
        'marketing-cho-fb'                => 'F&B',
        'marketing-cho-beauty'            => 'Beauty',
        'marketing-cho-ban-le'            => 'Bán lẻ',
        'marketing-cho-nong-nghiep'       => 'Nông nghiệp',
        'marketing-cho-du-lich'           => 'Du lịch',
    ];
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Google Fonts (non-blocking) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo site_base_path() . '/assets/css/style.css'; ?>">
    <?php if (!empty($loadSwiper)): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <?php endif; ?>
    <?php require_once __DIR__ . '/../../includes/favicon_links.php'; ?>

    <!-- Structured Data: Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": <?php echo json_encode($siteName, JSON_UNESCAPED_UNICODE); ?>,
        "url": <?php echo json_encode($canonicalUrl, JSON_UNESCAPED_UNICODE); ?>,
        "logo": <?php echo json_encode($logoUrl, JSON_UNESCAPED_UNICODE); ?>,
        "description": <?php echo json_encode($pageDescription, JSON_UNESCAPED_UNICODE); ?>
        <?php if (!empty($site['hotline'])): ?>,
        "telephone": <?php echo json_encode($site['hotline'], JSON_UNESCAPED_UNICODE); ?>
        <?php endif; ?>
        <?php if (!empty($site['email'])): ?>,
        "email": <?php echo json_encode($site['email'], JSON_UNESCAPED_UNICODE); ?>
        <?php endif; ?>
        <?php if (!empty($site['address'])): ?>,
        "address": {
            "@type": "PostalAddress",
            "addressLocality": <?php echo json_encode($site['address'], JSON_UNESCAPED_UNICODE); ?>
        }
        <?php endif; ?>
    }
    </script>

    <?php if (!empty($breadcrumbJsonLd)): ?>
    <!-- Structured Data: BreadcrumbList -->
    <script type="application/ld+json">
    <?php echo $breadcrumbJsonLd; ?>
    </script>
    <?php endif; ?>
</head>

<body>
    <a class="skip-link" href="#main-content">Chuyển đến nội dung chính</a>
    <header class="site-header">
        <div class="container header-inner">

            <button class="menu-toggle" data-menu-toggle aria-expanded="false" aria-controls="main-nav">
                <span class="hamburger" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span class="sr-only">Mở menu</span>
            </button>

            <a class="brand" href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">
                <img
                    src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($site['site_name'] ?? APP_NAME, ENT_QUOTES, 'UTF-8'); ?>"
                    class="site-logo">
            </a>

            <nav id="main-nav" class="site-nav" data-main-nav>
                <ul>
                    <li>
                        <a
                            class="<?php echo ($currentPage ?? 'home') === 'home' ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">
                            Trang chủ
                        </a>
                    </li>
                    <li>
                        <a
                            class="<?php echo ($currentPage ?? '') === 'about' ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('about'), ENT_QUOTES, 'UTF-8'); ?>">
                            Giới thiệu
                        </a>
                    </li>

                    <!-- MEGA DROPDOWN: DỊCH VỤ -->
                    <li class="has-dropdown <?php echo in_array($currentPage ?? '', ['services', 'service_detail', 'services_by_type'], true) ? 'active' : ''; ?>">
                        <button class="dropdown-toggle" data-dropdown-toggle aria-expanded="false">
                            Dịch vụ <span class="dropdown-arrow" aria-hidden="true">▾</span>
                        </button>

                        <div class="mega-dropdown" data-dropdown>
                            <div class="dropdown-grid container">

                                <!-- Dịch vụ theo loại -->
                                <div class="dropdown-column">
                                    <h4>Dịch vụ chính</h4>
                                    <ul>
                                        <?php foreach ($serviceTypes as $typeSlug => $typeName): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars(
                                                    site_page_url('services_by_type') . '&slug=' . rawurlencode($typeSlug),
                                                    ENT_QUOTES, 'UTF-8'
                                                ); ?>"
                                                   class="<?php echo (($currentPage ?? '') === 'services_by_type' && ($_GET['slug'] ?? '') === $typeSlug) ? 'active' : ''; ?>">
                                                    <?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </li>
                    <!-- /MEGA DROPDOWN -->

                    <li>
                        <a
                            class="<?php echo ($currentPage ?? '') === 'courses' ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>">
                            Khóa học
                        </a>
                    </li>
                    <li>
                        <a
                            class="<?php echo in_array($currentPage ?? '', ['projects', 'project_detail'], true) ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>">
                            Dự án
                        </a>
                    </li>
                    <!-- MEGA DROPDOWN: BLOG -->
                    <li class="has-dropdown <?php echo in_array($currentPage ?? '', ['blog', 'blog_detail', 'industry_detail'], true) ? 'active' : ''; ?>">
                        <button class="dropdown-toggle" data-dropdown-toggle aria-expanded="false">
                            Blog <span class="dropdown-arrow" aria-hidden="true">▾</span>
                        </button>

                        <div class="mega-dropdown" data-dropdown>
                            <div class="dropdown-grid container">

                                <!-- CỘT 1: Blog chung -->
                                <div class="dropdown-column">
                                    <h4>Blog</h4>
                                    <ul>
                                        <li>
                                            <a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>"
                                               class="<?php echo (($currentPage ?? '') === 'blog') ? 'active' : ''; ?>">
                                                Tất cả bài viết
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- CỘT 2: Kiến thức Marketing theo ngành -->
                                <div class="dropdown-column">
                                    <h4>Kiến thức Marketing theo ngành</h4>
                                    <ul>
                                        <?php foreach ($industries as $industrySlug => $industryName): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars(
                                                    site_page_url('industry_detail') . '&slug=' . rawurlencode($industrySlug),
                                                    ENT_QUOTES, 'UTF-8'
                                                ); ?>"
                                                   class="<?php echo (($currentPage ?? '') === 'industry_detail' && ($_GET['slug'] ?? '') === $industrySlug) ? 'active' : ''; ?>">
                                                    Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </li>
                    <!-- /MEGA DROPDOWN -->
                    <li>
                        <a
                            class="<?php echo ($currentPage ?? '') === 'recruitments' ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('recruitments'), ENT_QUOTES, 'UTF-8'); ?>">
                            Tuyển dụng
                        </a>
                    </li>
                    <li>
                        <a
                            class="<?php echo ($currentPage ?? '') === 'contact' ? 'active' : ''; ?>"
                            href="<?php echo htmlspecialchars(site_page_url('contact'), ENT_QUOTES, 'UTF-8'); ?>">
                            Liên hệ
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="header-cta">
                <a
                    class="btn btn-primary"
                    href="<?php echo htmlspecialchars(site_page_url('contact'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(
                        !empty($site['hotline']) ? ('Gọi ' . $site['hotline']) : 'Liên hệ',
                        ENT_QUOTES, 'UTF-8'
                    ); ?>
                </a>
            </div>

        </div>
    </header>