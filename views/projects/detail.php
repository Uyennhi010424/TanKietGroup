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

function cs_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$thumbUrl = site_image_url($project['thumbnail'] ?? '', '/img/hero.jpg');
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>">Dự án</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo cs_h($project['title']); ?></span>
	</div>
</nav>

<!-- Hero — Full-bleed cinematic -->
<section class="cs-hero" style="--cs-bg: url('<?php echo htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8'); ?>');">
	<div class="cs-hero__overlay"></div>
	<div class="container cs-hero__inner reveal">
		<div class="cs-hero__content">
			<?php if (!empty($project['industry_name'])): ?>
				<span class="cs-hero__tag"><?php echo cs_h($project['industry_name']); ?></span>
			<?php endif; ?>
			<h1 class="cs-hero__title"><?php echo cs_h($project['title']); ?></h1>
			<?php if (!empty($project['short_desc'])): ?>
				<p class="cs-hero__desc"><?php echo cs_h($project['short_desc']); ?></p>
			<?php endif; ?>
			<?php if (!empty($project['client_name'])): ?>
				<p class="cs-hero__client">Khách hàng: <strong><?php echo cs_h($project['client_name']); ?></strong></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- Overview Bar -->
<section class="cs-overview reveal">
	<div class="container">
		<div class="cs-overview__grid">
			<?php if (!empty($project['client_name'])): ?>
			<div class="cs-overview__item">
				<span class="cs-overview__label">Khách hàng</span>
				<span class="cs-overview__value"><?php echo cs_h($project['client_name']); ?></span>
			</div>
			<?php endif; ?>
			<?php if (!empty($project['industry_name'])): ?>
			<div class="cs-overview__item">
				<span class="cs-overview__label">Ngành</span>
				<span class="cs-overview__value"><?php echo cs_h($project['industry_name']); ?></span>
			</div>
			<?php endif; ?>
			<?php if (!empty($project['start_date'])): ?>
			<div class="cs-overview__item">
				<span class="cs-overview__label">Thời gian hợp tác</span>
				<span class="cs-overview__value">Từ <?php echo cs_h($project['start_date']); ?><?php echo !empty($project['end_date']) ? ' đến ' . cs_h($project['end_date']) : ' đến nay'; ?></span>
			</div>
			<?php endif; ?>
			<div class="cs-overview__item">
				<span class="cs-overview__label">Trạng thái</span>
				<span class="cs-overview__value cs-overview__value--active">Đang hợp tác</span>
			</div>
		</div>
	</div>
</section>

<!-- Main Content -->
<section class="cs-body">
	<div class="container">

		<!-- Content from admin -->
		<?php if (!empty($project['content'])): ?>
		<div class="cs-section reveal">
			<div class="cs-prose">
				<?php
					$raw = trim($project['content']);
					$lines = preg_split('/\r\n|\r|\n/', $raw);
					$out = [];
					foreach ($lines as $line) {
						$line = trim($line);
						if ($line === '') continue; // bỏ dòng trống
						// Dòng tiêu đề: chứa phần lớn chữ IN HOA (cho phép thêm chữ thường, số, dấu)
						if (preg_match('/^[A-ZÀ-ẠẢÃÁÂẦẤẬẨẪĂẰẮẶẲẴÈẸẺẼÉÊỀẾỆỂỄÌÍỊỈĨÒỌỎÕÓÔỒỐỘỔỖƠỜỚỢỞỠÙỤỦŨÚỪỨỰỬỮỲỴỶỸĐa-zà-ạảãáâầấậẩẫăằắặẳẵèẹẻẽéêềếệểễìíịỉĩòọỏõóôồốộổỗơờớợởỡùụủũúừứựửữỳỵỷỹđ0-9\s\.\&\(\)\-:\/,]+$/u', $line) && mb_strlen($line) > 3) {
							// Đếm số chữ IN HOA, nếu >= 50% tổng chữ cái → là tiêu đề
							preg_match_all('/[A-ZÀ-ẠẢÃÁÂẦẤẬẨẪĂẰẮẶẲẴÈẸẺẼÉÊỀẾỆỂỄÌÍỊỈĨÒỌỎÕÓÔỒỐỘỔỖƠỜỚỢỞỠÙỤỦŨÚỪỨỰỬỮỲỴỶỸĐ]/u', $line, $upper);
							preg_match_all('/[a-zà-ạảãáâầấậẩẫăằắặẳẵèẹẻẽéêềếệểễìíịỉĩòọỏõóôồốộổỗơờớợởỡùụủũúừứựửữỳỵỷỹđ]/u', $line, $lower);
							$isTitle = (count($lower[0]) === 0) || (count($upper[0]) / max(1, count($upper[0]) + count($lower[0])) >= 0.5);
							if ($isTitle) {
								$out[] = '<span class="cs-cap">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</span>';
							} else {
								$out[] = '<span class="cs-line"><span class="cs-dot"></span>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</span>';
							}
						} else {
							$out[] = '<span class="cs-line"><span class="cs-dot"></span>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</span>';
						}
					}
					echo implode("\n", $out);
				?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Results -->
		<?php if (!empty($resultMetrics)): ?>
		<div class="cs-results">
			<div class="cs-results__header reveal">
				<h2 class="cs-results__title">Kết quả đạt được</h2>
				<p class="cs-results__subtitle">Những con số ấn tượng sau quá trình hợp tác</p>
			</div>
			<div class="cs-results__grid">
				<?php foreach ($resultMetrics as $metric): ?>
				<div class="cs-results__card reveal">
					<span class="cs-results__metric"><?php echo cs_h($metric); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Gallery -->
		<?php if (!empty($images)): ?>
		<div class="cs-gallery reveal">
			<h2 class="cs-gallery__title">Hình ảnh dự án</h2>
			<div class="cs-gallery__grid">
				<?php foreach ($images as $img): ?>
				<div class="cs-gallery__item">
					<img src="<?php echo htmlspecialchars(site_image_url($img, '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo cs_h($project['title']); ?>" loading="lazy">
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

	</div>
</section>

<!-- CTA -->
<section class="cs-cta reveal">
	<div class="container">
		<div class="cs-cta__card">
			<h2>Bạn muốn đạt kết quả tương tự?</h2>
			<p>Liên hệ ngay để được tư vấn chiến lược marketing phù hợp với doanh nghiệp của bạn.</p>
			<div class="cs-cta__actions">
				<a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Đăng ký tư vấn miễn phí</a>
				<a href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline">← Xem tất cả dự án</a>
			</div>
		</div>
	</div>
</section>
