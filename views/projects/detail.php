<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ($_GET['id'] ?? '')));
$project = null;

if ($slug !== '') {
	$project = site_fetch_one('SELECT p.*, i.name AS industry_name FROM projects p LEFT JOIN industries i ON i.id = p.industry_id WHERE p.slug = :slug AND p.status = 1 LIMIT 1', ['slug' => $slug]);
}

if (!$project && $slug !== '') {
	http_response_code(404);
	echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy dự án</h3><p class="muted">Dự án bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Xem tất cả dự án</a></div></div></section>';
	return;
}

if (!$project) {
	$project = site_fetch_one('SELECT p.*, i.name AS industry_name FROM projects p LEFT JOIN industries i ON i.id = p.industry_id WHERE p.status = 1 ORDER BY p.created_at DESC LIMIT 1');
}

if (!$project) {
	echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có dự án</h3><p class="muted">Thêm dự án trong admin để trang này hiển thị.</p></div></div></section>';
	return;
}

// Dynamic SEO
$pageTitle = $project['title'] . ' - Dự án';
$metaDescOverride = $project['short_desc'] ?: '';
$ogImageOverride = site_image_url($project['thumbnail'] ?? '', '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dự án', 'item' => site_page_url('projects')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $project['title']],
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$images = site_parse_json($project['images'] ?? '', []);
$resultRaw = trim((string)($project['result_metrics'] ?? ''));

$resultMetrics = [];
if ($resultRaw !== '') {
	$decoded = json_decode($resultRaw, true);
	if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
		$resultMetrics = $decoded;
	} else {
		$resultMetrics = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $resultRaw)));
	}
}
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>">Dự án</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>

<!-- Hero Banner + Title -->
<section class="vintage-hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
    <div class="container reveal">
        <span class="vintage-hero__category"><?php echo htmlspecialchars($project['industry_name'] ?? 'Dự án', ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($project['client_name'])): ?>
        <p class="vintage-hero__lead">Khách hàng: <?php echo htmlspecialchars($project['client_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Article Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <!-- Project Images Gallery -->
                <?php if (!empty($images)): ?>
                <div class="vintage-gallery">
                    <?php foreach ($images as $img): ?>
                    <div class="vintage-gallery__item">
                        <img src="<?php echo htmlspecialchars(site_image_url($img, '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="vintage-prose">
                    <h2>Nội dung dự án</h2>
                    <?php echo $project['content'] ? sanitize_html($project['content']) : '<p>Chưa cập nhật nội dung.</p>'; ?>
                </div>

                <?php if (!empty($resultMetrics)): ?>
                <div class="vintage-prose">
                    <h2>Kết quả đạt được</h2>
                    <ul>
                        <?php foreach ($resultMetrics as $metric): ?>
                        <li><?php echo htmlspecialchars($metric, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </article>

            <aside class="vintage-article__sidebar reveal">
                <div class="vintage-sidebar-card">
                    <div class="vintage-sidebar-card__header">
                        <span>✦</span> Thông tin dự án
                    </div>
                    <ul class="vintage-sidebar-card__list">
                        <?php if (!empty($project['client_name'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Khách hàng</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($project['client_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Ngành</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($project['industry_name'] ?? 'Chưa phân loại', ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php if (!empty($project['start_date'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Thời gian</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($project['start_date'], ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($project['end_date']) ? ' — ' . htmlspecialchars($project['end_date'], ENT_QUOTES, 'UTF-8') : ''; ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="vintage-sidebar-card__footer">
                        <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="width:100%;text-align:center;">Liên hệ tư vấn</a>
                        <a href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Quay lại Dự án
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
