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
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>
<section class="hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($service['image'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
	<div class="container reveal">
		<span class="tag"><?php echo htmlspecialchars($service['industry_name'] ?? 'Dịch vụ', ENT_QUOTES, 'UTF-8'); ?></span>
		<h1><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="lead"><?php echo htmlspecialchars($service['short_desc'] ?: 'Dịch vụ được quản lý từ admin và hiển thị trực tiếp cho người dùng.', ENT_QUOTES, 'UTF-8'); ?></p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Về dịch vụ</h2>
			<?php echo nl2br(htmlspecialchars((string)($service['content'] ?: 'Nội dung chi tiết chưa được cập nhật.'), ENT_QUOTES, 'UTF-8')); ?>
		</article>
		<article class="card reveal">
			<h2>Thông tin nhanh</h2>
			<ul class="contact-list">
				<li><strong>Ngành:</strong> <?php echo htmlspecialchars($service['industry_name'] ?? 'Chưa phân loại', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Trạng thái:</strong> Đang hiển thị</li>
				<li><strong>Liên hệ:</strong> <?php echo htmlspecialchars((site_settings()['hotline'] ?? '') ?: 'Liên hệ qua trang tư vấn', ENT_QUOTES, 'UTF-8'); ?></li>
			</ul>
			<p style="margin-top:16px;"><a class="btn btn-primary" href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>">Nhận tư vấn ngay</a></p>
		</article>
	</div>
</section>
