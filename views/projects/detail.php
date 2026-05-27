<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ($_GET['id'] ?? '')));
$project = null;

if ($slug !== '') {
    $project = site_fetch_one('SELECT * FROM projects WHERE slug = :slug AND status = 1 LIMIT 1', ['slug' => $slug]);
}

if (!$project) {
    $project = site_fetch_one('SELECT * FROM projects WHERE status = 1 ORDER BY created_at DESC LIMIT 1');
}

if (!$project) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có dự án</h3><p class="muted">Thêm dự án trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}

$images = site_parse_json($project['images'] ?? '', []);
$resultMetrics = site_parse_json($project['result_metrics'] ?? '', []);
?>
<section class="section">
	<div class="container project-detail">
		<div class="project-back">
			<a class="btn btn-outline" href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>">← Quay lại</a>
		</div>
		<div class="grid grid-2">
			<div>
				<div class="project-gallery card">
					<?php if ($images): ?>
						<?php foreach ($images as $img): ?>
							<img src="<?php echo htmlspecialchars(site_image_url((string)$img, '/img/du_an3.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;border-radius:12px;margin-bottom:12px;">
						<?php endforeach; ?>
					<?php else: ?>
						<img src="<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/du_an3.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;border-radius:12px;">
					<?php endif; ?>
				</div>
			</div>
			<div>
				<h2><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
				<p class="lead" style="margin-top:8px;"><?php echo htmlspecialchars($project['short_desc'] ?: 'Dự án được quản lý từ admin.', ENT_QUOTES, 'UTF-8'); ?></p>

				<h4 style="margin-top:18px;">Nội dung dự án</h4>
				<p class="muted"><?php echo nl2br(htmlspecialchars((string)($project['content'] ?: 'Nội dung chi tiết chưa được cập nhật.'), ENT_QUOTES, 'UTF-8')); ?></p>

				<h4 style="margin-top:12px;">Kết quả đạt được</h4>
				<?php if ($resultMetrics): ?>
					<ul>
						<?php foreach ($resultMetrics as $metric): ?>
							<li class="muted"><?php echo htmlspecialchars(is_array($metric) ? json_encode($metric, JSON_UNESCAPED_UNICODE) : (string)$metric, ENT_QUOTES, 'UTF-8'); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p class="muted">Chưa có dữ liệu kết quả.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
