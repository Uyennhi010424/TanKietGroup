<?php
require_once __DIR__ . '/../../includes/site.php';

$projects = site_fetch_all(
    'SELECT id, title, slug, short_desc, thumbnail, client_name
     FROM projects
     WHERE status = 1
     ORDER BY created_at DESC'
);
?>
<section class="hero">
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
					<article class="card reveal">
						<img src="<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/du_an3.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;">
						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
						<p class="muted"><?php echo htmlspecialchars($project['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="muted"><?php echo htmlspecialchars($project['client_name'] ?: 'Khách hàng', ENT_QUOTES, 'UTF-8'); ?></p>
						<p><a href="<?php echo htmlspecialchars(site_page_url('project_detail', ['slug' => $project['slug']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Xem chi tiết</a></p>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
