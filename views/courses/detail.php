<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$course = null;

if ($slug !== '') {
    $course = site_fetch_one('SELECT * FROM courses WHERE slug = :slug AND status = 1 LIMIT 1', ['slug' => $slug]);
}

if (!$course) {
    $course = site_fetch_one('SELECT * FROM courses WHERE status = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 1');
}

if (!$course) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có khóa học</h3><p class="muted">Thêm khóa học trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}
?>
<section class="hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($course['thumbnail'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
	<div class="container reveal">
		<span class="tag">Khóa học</span>
		<h1><?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="lead"><?php echo htmlspecialchars($course['short_desc'] ?: 'Khóa học được quản lý từ admin.', ENT_QUOTES, 'UTF-8'); ?></p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Nội dung khóa học</h2>
			<?php echo nl2br(htmlspecialchars((string)($course['content'] ?: 'Nội dung khóa học chưa được cập nhật.'), ENT_QUOTES, 'UTF-8')); ?>
		</article>
		<article class="card reveal">
			<h2>Thông tin nhanh</h2>
			<ul class="contact-list">
				<li><strong>Giá:</strong> <?php echo htmlspecialchars(format_vnd($course['price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Giá ưu đãi:</strong> <?php echo htmlspecialchars(format_vnd($course['discount_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Thời lượng:</strong> <?php echo htmlspecialchars($course['duration'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Hình thức:</strong> <?php echo htmlspecialchars($course['form_type'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></li>
			</ul>
			<p style="margin-top:16px;"><a class="btn btn-primary" href="/?page=consultations">Đăng ký tư vấn</a></p>
		</article>
	</div>
</section>
