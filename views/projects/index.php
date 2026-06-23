<?php
require_once __DIR__ . '/../../includes/site.php';

$site = site_settings();
$heroBanner = site_image_url($site['banner'] ?? '', '/img/hero.jpg');

$projects = site_fetch_all(
    'SELECT id, title, slug, short_desc, thumbnail, client_name, is_featured
     FROM projects
     WHERE status = 1
     ORDER BY is_featured DESC, created_at DESC'
);
?>
<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo htmlspecialchars($heroBanner, ENT_QUOTES, 'UTF-8'); ?>');"></div>
	<div class="container reveal">
		<span class="tag">Dự án</span>
		<h1>Dự án tiêu biểu</h1>
		<p class="lead">Những dự án đã thực hiện thành công, giúp khách hàng đạt được mục tiêu kinh doanh.</p>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="grid grid-3">
			<?php if (!$projects): ?>
				<article class="card reveal"><h3>Chưa có dự án</h3><p class="muted">Thêm dự án trong admin để hiển thị tại đây.</p></article>
			<?php else: ?>
				<?php foreach ($projects as $project): ?>
					<article class="card reveal" style="position:relative;">
						<?php if ((int)($project['is_featured'] ?? 0) === 1): ?>
							<span style="position:absolute;top:12px;right:12px;background:rgba(255,200,0,0.9);color:#1a1a1a;font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:999px;z-index:1;">Tiêu biểu</span>
						<?php endif; ?>
						<img src="<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/du_an3.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;" loading="lazy">
						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
						<p class="muted"><?php echo htmlspecialchars($project['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="muted"><?php echo htmlspecialchars($project['client_name'] ?: 'Khách hàng', ENT_QUOTES, 'UTF-8'); ?></p>
						<p><a href="<?php echo htmlspecialchars(site_page_url('project_detail', ['slug' => $project['slug']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline btn-sm">Xem chi tiết →</a></p>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
