<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$course = null;

if ($slug !== '') {
    $course = site_fetch_one('SELECT * FROM courses WHERE slug = :slug AND status = 1 LIMIT 1', ['slug' => $slug]);
}

if (!$course && $slug !== '') {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy khóa học</h3><p class="muted">Khóa học bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Xem tất cả khóa học</a></div></div></section>';
    return;
}

if (!$course) {
    $course = site_fetch_one('SELECT * FROM courses WHERE status = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 1');
}

if (!$course) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có khóa học</h3><p class="muted">Thêm khóa học trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}

// Dynamic SEO
$pageTitle = $course['title'] . ' - Khóa học';
$metaDescOverride = $course['short_desc'] ?: '';
$ogImageOverride = site_image_url($course['thumbnail'] ?? '', '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Khóa học', 'item' => site_page_url('courses')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $course['title']],
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>">Khóa học</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>

<!-- Hero Banner + Title -->
<section class="vintage-hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($course['thumbnail'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
    <div class="container reveal">
        <span class="vintage-hero__category">Khóa học</span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($course['short_desc'])): ?>
        <p class="vintage-hero__lead"><?php echo htmlspecialchars(mb_strimwidth(strip_tags($course['short_desc']), 0, 150, '…'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Article Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <div class="vintage-prose">
                    <h2>Nội dung khóa học</h2>
                    <?php
                    $content = trim((string)($course['content'] ?? $course['short_desc'] ?? ''));
                    if (!empty($content)):
                    ?>
                        <?php echo sanitize_html($content); ?>
                    <?php else: ?>
                        <p>Nội dung khóa học đang được cập nhật. Vui lòng quay lại sau hoặc liên hệ tư vấn để biết thêm thông tin.</p>
                    <?php endif; ?>
                </div>
            </article>

            <aside class="vintage-article__sidebar reveal">
                <div class="vintage-sidebar-card">
                    <div class="vintage-sidebar-card__header">
                        <span>✦</span> Thông tin khóa học
                    </div>
                    <ul class="vintage-sidebar-card__list">
                        <?php if (!empty($course['price'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Học phí</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars(format_vnd($course['price']), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['duration'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Thời lượng</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($course['duration'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['form_type'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Hình thức</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($course['form_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="vintage-sidebar-card__footer">
                        <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="width:100%;text-align:center;">Đăng ký tư vấn</a>
                        <a href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Quay lại Khóa học
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
