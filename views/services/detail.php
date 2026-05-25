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
?>
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
			<p style="margin-top:16px;"><a class="btn btn-primary" href="/?page=consultations">Nhận tư vấn ngay</a></p>
		</article>
	</div>
</section>
