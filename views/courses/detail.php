<?php
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

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

$thumbUrl = site_image_url($course['thumbnail'] ?? '', '/img/hero.jpg');
function cr_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Format form_type
$formLabels = ['online' => 'Online', 'offline' => 'Offline', 'hybrid' => 'Hybrid'];
$formLabel = $formLabels[$course['form_type'] ?? ''] ?? ($course['form_type'] ?? '');
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>">Khóa học</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo cr_h($course['title']); ?></span>
	</div>
</nav>

<!-- Hero -->
<section class="cr-hero">
	<div class="container cr-hero__inner reveal">
		<div class="cr-hero__content">
			<span class="cr-hero__tag">Khóa học</span>
			<h1 class="cr-hero__title"><?php echo cr_h($course['title']); ?></h1>
			<?php if (!empty($course['short_desc'])): ?>
				<p class="cr-hero__desc"><?php echo cr_h($course['short_desc']); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- Key Info Cards -->
<section class="cr-info reveal">
	<div class="container">
		<div class="cr-info__grid">
			<?php if (!empty($course['price'])): ?>
			<div class="cr-info__card cr-info__card--price">
				<span class="cr-info__label">Học phí</span>
				<span class="cr-info__value cr-info__value--price">
					<?php if (!empty($course['discount_price'])): ?>
						<span class="cr-price--old"><?php echo cr_h(format_vnd($course['price'])); ?></span>
						<span class="cr-price--new"><?php echo cr_h(format_vnd($course['discount_price'])); ?></span>
					<?php else: ?>
						<?php echo cr_h(format_vnd($course['price'])); ?>
					<?php endif; ?>
				</span>
			</div>
			<?php endif; ?>
			<?php if (!empty($course['duration'])): ?>
			<div class="cr-info__card">
				<span class="cr-info__label">Thời lượng</span>
				<span class="cr-info__value"><?php echo cr_h($course['duration']); ?></span>
			</div>
			<?php endif; ?>
			<?php if ($formLabel): ?>
			<div class="cr-info__card">
				<span class="cr-info__label">Hình thức</span>
				<span class="cr-info__value"><?php echo cr_h($formLabel); ?></span>
			</div>
			<?php endif; ?>
			<?php if (!empty($course['start_date'])): ?>
			<div class="cr-info__card">
				<span class="cr-info__label">Khai giảng</span>
				<span class="cr-info__value"><?php echo date('d/m/Y', strtotime($course['start_date'])); ?></span>
			</div>
			<?php endif; ?>
			<?php if (!empty($course['max_students'])): ?>
			<div class="cr-info__card">
				<span class="cr-info__label">Sĩ số tối đa</span>
				<span class="cr-info__value"><?php echo (int)$course['max_students']; ?> học viên</span>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- Thumbnail -->
<?php if (!empty($course['thumbnail'])): ?>
<section class="cr-thumb reveal">
	<div class="container">
		<div class="cr-thumb__wrap">
			<img src="<?php echo htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo cr_h($course['title']); ?>">
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Content -->
<section class="cr-body">
	<div class="container">
		<div class="cr-layout">
			<!-- Left: Content -->
			<article class="cr-content reveal">
				<h2>Nội dung khóa học</h2>
				<?php
				$content = trim((string)($course['content'] ?? $course['short_desc'] ?? ''));
				if (!empty($content)):
				?>
					<?php echo sanitize_html($content); ?>
				<?php else: ?>
					<p>Nội dung khóa học đang được cập nhật. Vui lòng quay lại sau hoặc liên hệ tư vấn để biết thêm thông tin.</p>
				<?php endif; ?>
			</article>

			<!-- Right: Enrollment Form -->
			<aside class="cr-sidebar reveal">
				<div class="cr-enroll">
					<h3 class="cr-enroll__title">Đăng ký nhận tư vấn</h3>
					<p class="cr-enroll__desc">Để lại thông tin, chúng tôi sẽ liên hệ trong vòng 24h.</p>
					<form id="course-enroll-form" onsubmit="submitEnroll(event)">
						<input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">
						<input type="hidden" name="csrf_token" value="<?php echo cr_h(csrf_token()); ?>">
						<div class="cr-enroll__field">
							<input type="text" name="full_name" placeholder="Họ và tên *" required>
						</div>
						<div class="cr-enroll__field">
							<input type="email" name="email" placeholder="Email *" required>
						</div>
						<div class="cr-enroll__field">
							<input type="tel" name="phone" placeholder="Số điện thoại *" required pattern="[0-9]{10,11}">
						</div>
						<button class="cr-enroll__btn" type="submit">Đăng ký ngay</button>
						<div class="cr-enroll__msg" id="enroll-msg" style="display:none;"></div>
					</form>
				</div>
			</aside>
		</div>
	</div>
</section>

<!-- Back -->
<section class="cr-footer reveal">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>" class="cr-back">
			← Quay lại tất cả khóa học
		</a>
	</div>
</section>

<script>
function submitEnroll(e) {
    e.preventDefault();
    var form = document.getElementById('course-enroll-form');
    var msgDiv = document.getElementById('enroll-msg');
    var btn = form.querySelector('button[type="submit"]');

    btn.disabled = true;
    btn.textContent = 'Đang gửi...';

    var formData = new FormData(form);

    fetch('/api/enroll_course.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        msgDiv.style.display = 'block';
        if (result.success) {
            msgDiv.className = 'cr-enroll__msg cr-enroll__msg--success';
            msgDiv.textContent = result.message;
            form.reset();
        } else {
            msgDiv.className = 'cr-enroll__msg cr-enroll__msg--error';
            msgDiv.textContent = result.message;
        }
        btn.disabled = false;
        btn.textContent = 'Đăng ký ngay';
        setTimeout(function() { msgDiv.style.display = 'none'; }, 5000);
    })
    .catch(function() {
        msgDiv.style.display = 'block';
        msgDiv.className = 'cr-enroll__msg cr-enroll__msg--error';
        msgDiv.textContent = 'Đã xảy ra lỗi, vui lòng thử lại sau';
        btn.disabled = false;
        btn.textContent = 'Đăng ký ngay';
    });
}
</script>
