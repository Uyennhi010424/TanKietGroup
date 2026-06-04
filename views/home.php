<?php
require_once __DIR__ . '/../includes/site.php';

$site = site_settings();
$heroBanner = site_image_url($site['banner'] ?? '', '/img/hero.jpg');
$services = site_fetch_all(
	'SELECT s.id, s.title, s.slug, s.short_desc, s.image, i.name AS industry_name
     FROM services s
     LEFT JOIN industries i ON i.id = s.industry_id
     WHERE s.status = 1
     ORDER BY s.sort_order ASC, s.id DESC
     LIMIT 6'
);
$projects = site_fetch_all(
	'SELECT id, title, slug, short_desc, thumbnail
     FROM projects
     WHERE status = 1
     ORDER BY created_at DESC
     LIMIT 4'
);
$courses = site_fetch_all(
	'SELECT id, title, slug, short_desc, thumbnail, price, discount_price
     FROM courses
     WHERE status = 1
     ORDER BY sort_order ASC, created_at DESC
     LIMIT 4'
);
$posts = site_fetch_all(
	'SELECT id, title, slug, thumbnail, meta_title, created_at
     FROM blog_posts
     WHERE status = "published"
     ORDER BY is_featured DESC, published_at DESC, created_at DESC
     LIMIT 4'
);
?>
<section class="hero" style="--hero-banner: url('<?php echo htmlspecialchars($heroBanner, ENT_QUOTES, 'UTF-8'); ?>');">
	<div class="container reveal">
		<h1><?php echo htmlspecialchars($site['meta_title'] ?: 'Chiến lược Marketing toàn diện cho doanh nghiệp hiện đại', ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="lead"><?php echo htmlspecialchars($site['meta_description'] ?: 'TanKiet Group kết hợp dữ liệu, sáng tạo và công nghệ để giúp doanh nghiệp gia tăng doanh thu bền vững trên đa kênh.', ENT_QUOTES, 'UTF-8'); ?></p>
		<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
			<a class="btn btn-primary" href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Khám phá dịch vụ</a>
			<a class="btn btn-outline" href="<?php echo htmlspecialchars(site_page_url('about'), ENT_QUOTES, 'UTF-8'); ?>">Tìm hiểu chúng tôi</a>
		</div>
	</div>
</section>

<section class="section section-muted">
	<div class="container grid grid-4">
		<article class="card stats reveal">
			<div class="stat-number">
				<strong class="count" data-target="150">0</strong>
				<span class="suffix">+</span>
			</div>
			<span class="muted">Dự án thực hiện</span>
		</article>

		<article class="card stats reveal">
			<div class="stat-number">
				<strong class="count" data-target="50">0</strong>
				<span class="suffix">+</span>
			</div>
			<span class="muted">Doanh nghiệp đối tác</span>
		</article>

		<article class="card stats reveal">
			<div class="stat-number">
				<strong class="count" data-target="99">0</strong>
				<span class="suffix">+</span>
			</div>
			<span class="muted">Học viên đào tạo</span>
		</article>

		<article class="card stats reveal">
			<div class="stat-number">
				<strong class="count" data-target="98">0</strong>
				<span class="suffix">%</span>
			</div>
			<span class="muted">Mức độ hài lòng</span>
		</article>
	</div>
</section>

<section class="section">
	<div class="container">
		<h2 class="reveal">Dịch vụ nổi bật</h2>
		<div class="swiper services-swiper" style="margin-top:24px;">
			<div class="swiper-wrapper">
				<?php if (!$services): ?>
					<div class="swiper-slide">
						<article class="card reveal">
							<h3>Chưa có dữ liệu dịch vụ</h3>
							<p class="muted">Hãy thêm dịch vụ trong trang quản trị.</p>
						</article>
					</div>
				<?php else: ?>
					<?php foreach ($services as $service): ?>
						<div class="swiper-slide">
							<article class="card service-card reveal">
								<div class="card-media service-overlay-wrap">
									<img
										src="<?php echo htmlspecialchars(site_image_url($service['image'] ?? '', '/img/du_an.jpg'), ENT_QUOTES, 'UTF-8'); ?>"
										alt="<?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>"
										loading="lazy">

									<a href="/dich-vu/<?php echo htmlspecialchars($service['slug'], ENT_QUOTES, 'UTF-8'); ?>"
										class="service-overlay">
										Xem chi tiết
									</a>
								</div>
								<div class="card-content">
									<h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
									<p class="muted">
										<?php
										$desc = trim($service['short_desc'] ?? '');
										if ($desc !== '' && strtolower($desc) !== 'hello') {
											echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
										}
										?>
									</p>
								</div>
							</article>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="swiper-button-prev"></div>
			<div class="swiper-button-next"></div>
			<div class="swiper-pagination"></div>
		</div>
	</div>
</section>

<section class="section section-muted">
	<div class="container">
		<h2 class="reveal">Dự án tiêu biểu</h2>
		<div class="swiper projects-swiper" style="margin-top:24px;">
			<div class="swiper-wrapper">
				<?php if (!$projects): ?>
					<div class="swiper-slide">
						<article class="card reveal">
							<h3>Chưa có dữ liệu dự án</h3>
							<p class="muted">Hãy thêm dự án trong trang quản trị.</p>
						</article>
					</div>
				<?php else: ?>
					<?php foreach ($projects as $project): ?>
						<div class="swiper-slide">
							<article class="card project-card reveal">
								<div class="card-media">
									<img src="<?php echo htmlspecialchars(site_image_url($project['thumbnail'] ?? '', '/img/du_an3.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?>">
									<a href="/du-an/<?php echo htmlspecialchars($project['slug'], ENT_QUOTES, 'UTF-8'); ?>"
										class="project-overlay swiper-no-swiping">
										Xem chi tiết
									</a>
								</div>
								<div class="card-content">
									<h3><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
									<p class="muted"><?php echo htmlspecialchars($project['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
								</div>
							</article>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="swiper-button-prev" aria-label="Previous slide"></div>
			<div class="swiper-button-next" aria-label="Next slide"></div>
			<div class="swiper-pagination"></div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<h2 class="reveal">Khóa học mới</h2>
		<div class="grid grid-4" style="margin-top:24px;">
			<?php if (!$courses): ?>
				<article class="card reveal">
					<h3>Chưa có khóa học</h3>
					<p class="muted">Thêm khóa học trong admin để hiển thị tại đây.</p>
				</article>
			<?php else: ?>
				<?php foreach ($courses as $course): ?>
					<article class="card reveal course-card">
						<img src="<?php echo htmlspecialchars(site_image_url($course['thumbnail'] ?? '', '/img/du_an.jpg'), ENT_QUOTES, 'UTF-8'); ?>"
							alt="<?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?>"
							style="width:100%;height:200px;object-fit:cover;border-radius:12px;">

						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

						<!-- <?php echo htmlspecialchars($course['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>  -->

						<p><a href="/khoa-hoc/<?php echo htmlspecialchars($course['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Xem chi tiết</a></p>


					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section section-muted">
	<div class="container">
		<h2 class="reveal">Tin mới từ blog</h2>
		<div class="grid grid-4" style="margin-top:24px;">
			<?php if (!$posts): ?>
				<article class="card reveal">
					<h3>Chưa có bài viết</h3>
					<p class="muted">Thêm blog post trong admin để hiển thị tại đây.</p>
				</article>
			<?php else: ?>
				<?php foreach ($posts as $post): ?>
					<article class="card reveal">
						<img src="<?php echo htmlspecialchars(site_image_url($post['thumbnail'] ?? '', '/img/du_an4.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:180px;object-fit:cover;border-radius:12px;">
						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
						<p><a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">Đọc bài viết</a></p>

						<a href="/du-an/<?php echo htmlspecialchars($project['slug'], ENT_QUOTES, 'UTF-8'); ?>"
										class="project-overlay swiper-no-swiping">
										Xem chi tiết
									</a>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section">
	<div class="container card reveal" style="text-align:center;">
		<h2>Sẵn sàng bứt phá cùng <?php echo htmlspecialchars($site['site_name'] ?: APP_NAME, ENT_QUOTES, 'UTF-8'); ?>?</h2>
		<p class="lead" style="margin-inline:auto;">Nhận tư vấn 1:1 và lộ trình tăng trưởng phù hợp với mục tiêu doanh nghiệp của bạn.</p>
		<a class="btn btn-primary" href="/lien-he">Đăng ký tư vấn miễn phí</a>
	</div>
</section>