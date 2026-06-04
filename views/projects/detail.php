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
$resultRaw = trim((string)($project['result_metrics'] ?? ''));

$resultMetrics = [];

// thử JSON trước
if ($resultRaw !== '') {
	$decoded = json_decode($resultRaw, true);

	if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
		$resultMetrics = $decoded;
	} else {
		// fallback: tách dòng thường
		$resultMetrics = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $resultRaw)));
	}
}
?>
<section class="section project-detail-v2">
    <div class="container">

        <!-- BACK -->
        <div class="project-back">
            <a class="btn btn-outline" href="<?php echo htmlspecialchars(site_page_url('projects')); ?>">
                ← Quay lại
            </a>
        </div>

        <!-- TITLE -->
        <h1 class="project-title">
            <?php echo htmlspecialchars($project['title']); ?>
        </h1>

        <p class="project-desc">
            <?php echo htmlspecialchars($project['short_desc'] ?: 'Dự án được quản lý từ admin.'); ?>
        </p>

        <!-- MAIN GRID -->
        <div class="project-grid">

            <!-- LEFT IMAGE -->
            <div class="project-image card-glow">
                <?php if (!empty($images)): ?>
                    <img src="<?php echo htmlspecialchars(site_image_url($images[0], '/img/du_an3.jpg')); ?>" />
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/du_an3.jpg')); ?>" />
                <?php endif; ?>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="project-content">

                <div class="info-card">
                    <h3>📄 Nội dung dự án</h3>
                    <p>
                        <?php echo nl2br(htmlspecialchars($project['content'] ?: 'Chưa cập nhật nội dung')); ?>
                    </p>
                </div>

                <div class="info-card">
                    <h3>⭐ Kết quả đạt được</h3>

                    <?php if (!empty($resultMetrics)): ?>
                        <ul class="result-list">
                            <?php foreach ($resultMetrics as $metric): ?>
                                <li>✓ <?php echo htmlspecialchars($metric); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Chưa có dữ liệu kết quả</p>
                    <?php endif; ?>

                </div>

            </div>
        </div>

    </div>
</section>