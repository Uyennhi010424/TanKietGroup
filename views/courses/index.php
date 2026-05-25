<?php
require_once __DIR__ . '/../../includes/site.php';

$courses = site_fetch_all(
    'SELECT id, title, slug, short_desc, thumbnail, price, discount_price, duration, form_type
     FROM courses
     WHERE status = 1
     ORDER BY sort_order ASC, created_at DESC'
);
?>
<section class="hero">
	<div class="container reveal">
		<span class="tag">Khóa học</span>
		<h1>Khóa học đang được quản lý trong admin</h1>
		<p class="lead">Mỗi khóa học hiển thị ở đây đều lấy từ bảng `courses`, nên admin thêm/sửa là frontend cập nhật ngay.</p>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="grid grid-3">
			<?php if (!$courses): ?>
				<article class="card reveal"><h3>Chưa có khóa học</h3><p class="muted">Thêm dữ liệu trong admin để hiển thị tại đây.</p></article>
			<?php else: ?>
				<?php foreach ($courses as $course): ?>
					<article class="card reveal">
						<img src="<?php echo htmlspecialchars(site_image_url($course['thumbnail'] ?? '', '/img/du_an.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;">
						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
						<p class="muted"><?php echo htmlspecialchars($course['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="muted"><?php echo htmlspecialchars(($course['duration'] ?? '') . ' · ' . ($course['form_type'] ?? 'online'), ENT_QUOTES, 'UTF-8'); ?></p>
						<p><a href="/?page=course_detail&amp;slug=<?php echo rawurlencode($course['slug']); ?>">Xem chi tiết</a></p>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
