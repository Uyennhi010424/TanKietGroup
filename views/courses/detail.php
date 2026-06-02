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
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
    <h2>Nội dung khóa học</h2>
    <?php 
    $content = trim((string)($course['short_desc'] ?? ''));
    
    if (!empty($content)): 
    ?>
        <div class="course-content prose" style="line-height: 1.7; font-size: 1.05rem;">
            <?= nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) ?>
        </div>
    <?php else: ?>
        <p class="muted" style="font-style: italic;">
            Nội dung khóa học đang được cập nhật. <br>
            Vui lòng quay lại sau hoặc liên hệ tư vấn để biết thêm thông tin.
        </p>
    <?php endif; ?>
</article>
</article>
		<article class="card reveal">
			<h2>Thông tin</h2>
			<ul class="contact-list">
				<li><strong>Giá:</strong> <?php echo htmlspecialchars(format_vnd($course['price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Thời lượng:</strong> <?php echo htmlspecialchars($course['duration'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Hình thức:</strong> <?php echo htmlspecialchars($course['form_type'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></li>
			</ul>
			<p style="margin-top:16px;"><a class="btn btn-primary" href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>">Đăng ký tư vấn</a></p>
		</article>
	</div>
</section>
