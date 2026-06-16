<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$service = null;

if ($slug !== '') {
    $service = site_fetch_one(
        'SELECT s.*, i.name AS industry_name
         FROM services s
         LEFT JOIN industries i ON i.id = s.industry_id
         WHERE s.slug = :slug AND s.status = 1
         LIMIT 1',
        ['slug' => $slug]
    );
}

if (!$service && $slug !== '') {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy dịch vụ</h3><p class="muted">Dịch vụ bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Xem tất cả dịch vụ</a></div></div></section>';
    return;
}

if (!$service) {
    $service = site_fetch_one(
        'SELECT s.*, i.name AS industry_name
         FROM services s
         LEFT JOIN industries i ON i.id = s.industry_id
         WHERE s.status = 1
         ORDER BY s.sort_order ASC, s.id DESC
         LIMIT 1'
    );
}

if (!$service) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có dịch vụ</h3><p class="muted">Thêm dịch vụ trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}

// Dynamic SEO
$pageTitle = $service['title'] . ' - Dịch vụ';
$metaDescOverride = $service['short_desc'] ?: ($service['meta_description'] ?? '');
$ogImageOverride = site_image_url($service['image'] ?? '', '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dịch vụ', 'item' => site_page_url('services')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $service['title']],
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>

<!-- Hero Banner + Title -->
<section class="vintage-hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($service['image'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
    <div class="container reveal">
        <span class="vintage-hero__category"><?php echo htmlspecialchars($service['industry_name'] ?? 'Dịch vụ', ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($service['short_desc'])): ?>
        <p class="vintage-hero__lead"><?php echo htmlspecialchars($service['short_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Article Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <div class="vintage-prose">
                    <?php echo $service['content'] ? sanitize_html($service['content']) : '<p>Nội dung chi tiết chưa được cập nhật.</p>'; ?>
                </div>
            </article>

            <aside class="vintage-article__sidebar reveal">
                <div class="vintage-sidebar-card">
                    <div class="vintage-sidebar-card__header">
                        <span>✦</span> Thông tin dịch vụ
                    </div>
                    <ul class="vintage-sidebar-card__list">
                        <li>
                            <span class="vintage-sidebar-card__label">Ngành</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($service['industry_name'] ?? 'Chưa phân loại', ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li>
                            <span class="vintage-sidebar-card__label">Trạng thái</span>
                            <span class="vintage-sidebar-card__value">Đang hiển thị</span>
                        </li>
                        <?php if (!empty($service['service_type'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Loại dịch vụ</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($service['service_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php
                        $hotline = trim((string)(site_settings()['hotline'] ?? ''));
                        if ($hotline !== ''): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Hotline</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($hotline, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="vintage-sidebar-card__footer">
                        <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="width:100%;text-align:center;">Nhận tư vấn ngay</a>
                        <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Quay lại Dịch vụ
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
