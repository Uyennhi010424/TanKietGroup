<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo ($pageTitle ?? APP_NAME) . ' | ' . APP_NAME; ?></title>
    <meta name="description" content="TanKiet Group - Giải pháp Marketing tăng trưởng toàn diện cho doanh nghiệp hiện đại.">
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
            <img src="/img/logo.jpg" alt="TanKiet Group" class="site-logo">
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
                                    <li><a href="/?page=services#marketing-tron-goi">Marketing Trọn gói</a></li>
                                    <li><a href="/?page=services#cham-soc-fanpage">Chăm sóc Fanpage</a></li>
                                    <li><a href="/?page=services#san-xuat-video">Sản xuất Video</a></li>
                                    <li><a href="/?page=services#to-chuc-su-kien">Tổ chức Sự kiện</a></li>
                                    <li><a href="/?page=services#thiet-ke-website">Thiết kế Website chuẩn SEO</a></li>
                                </ul>
                            </div>
                            <div class="dropdown-column">
                                <h4>Marketing theo ngành</h4>
                                <ul>
                                    <li><a class="highlight" href="/?page=services#xd">Marketing cho Xây dựng</a></li>
                                    <li><a href="/?page=services#bds">Marketing cho Bất động sản</a></li>
                                    <li><a href="/?page=services#fb">Marketing cho F&B</a></li>
                                    <li><a href="/?page=services#beauty">Marketing cho Beauty</a></li>
                                    <li><a href="/?page=services#ban-le">Marketing cho Bán lẻ</a></li>
                                    <li><a href="/?page=services#nong-nghiep">Marketing cho Nông nghiệp</a></li>
                                    <li><a href="/?page=services#du-lich">Marketing cho Du lịch</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>

                <li><a href="/?page=services">Khóa học</a></li>
                <li><a href="/?page=services">Dự án</a></li>
                <li><a href="/?page=services">Tuyển dụng</a></li>
                <li><a href="/?page=services">Blog</a></li>
                <li><a class="<?php echo ($currentPage ?? '') === 'consultations' ? 'active' : ''; ?>" href="/?page=consultations">Tư vấn</a></li>
                <li><a class="<?php echo ($currentPage ?? '') === 'contact' ? 'active' : ''; ?>" href="/?page=contact">Liên hệ</a></li>
            </ul>
        </nav>

        <div class="header-cta">
            <a class="btn btn-primary" href="/?page=consultations">Tư vấn ngay</a>
        </div>
    </div>
</header>
