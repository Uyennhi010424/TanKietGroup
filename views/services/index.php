<?php
require_once __DIR__ . '/../../includes/site.php';

$site = site_settings();
$heroBanner = site_image_url($site['banner'] ?? '', '/img/hero.jpg');

$services = site_fetch_all(
	'SELECT s.id, s.title, s.slug, s.short_desc, s.content, s.image, s.is_featured, i.name AS industry_name
	 FROM services s
	 LEFT JOIN industries i ON i.id = s.industry_id
	 WHERE s.status = 1
	 ORDER BY s.is_featured DESC, s.sort_order ASC, s.id DESC'
);
$industries = site_fetch_all('SELECT id, name, slug, description FROM industries ORDER BY sort_order ASC, id ASC');
?>
<section class="hero">
	<div class="hero-bg" style="background-image:url('<?php echo htmlspecialchars($heroBanner, ENT_QUOTES, 'UTF-8'); ?>');"></div>
	<div class="container reveal">
		<span class="tag">Dịch vụ</span>
		<h1>Dịch vụ của chúng tôi</h1>
		<p class="lead">Khám phá các dịch vụ marketing chuyên nghiệp mà TanKiet Group cung cấp để giúp doanh nghiệp bạn phát triển.</p>
	</div>
</section>

<section class="section">
	<div class="container">
		<h2 class="reveal">Danh sách dịch vụ</h2>

		<div class="services-layout" style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:18px;align-items:start;">
			<div>
				<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
					<div><strong><?php echo count($services); ?></strong> dịch vụ đang hiển thị</div>
					<div>
						<a href="?page=services&amp;view=grid" class="btn" style="margin-right:8px;">Xem dạng lưới</a>
						<a href="?page=services&amp;view=table" class="btn">Xem dạng bảng</a>
					</div>
				</div>

				<?php $viewMode = ($_GET['view'] ?? 'grid'); ?>

				<?php if ($viewMode === 'table'): ?>
					<div style="overflow:auto;margin-top:12px;">
						<table class="data-table" style="width:100%;border-collapse:collapse;">
							<thead>
								<tr>
									<th>ID</th>
									<th>Tiêu đề</th>
									<th>Ngành</th>
									<th>Mô tả</th>
									<th>Ảnh</th>
									<th style="text-align:right">Hành động</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$services): ?>
									<tr><td colspan="6" style="padding:18px;text-align:center;color:var(--muted);">Chưa có dịch vụ</td></tr>
								<?php else: foreach ($services as $s): ?>
									<tr>
										<td>#SVC-<?php echo str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT); ?></td>
										<td><a href="<?php echo htmlspecialchars(site_page_url('service_detail', ['slug' => $s['slug']]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8'); ?></a></td>
										<td><?php echo htmlspecialchars($s['industry_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
										<td style="max-width:360px"><?php echo htmlspecialchars($s['short_desc'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php if (!empty($s['image'])): ?><img src="<?php echo htmlspecialchars(site_image_url($s['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="" style="height:48px;object-fit:cover;border-radius:6px;"><?php else: ?>-<?php endif; ?></td>
										<td style="text-align:right"><a class="btn" href="<?php echo htmlspecialchars(site_page_url('service_detail', ['slug' => $s['slug']]), ENT_QUOTES, 'UTF-8'); ?>">Xem</a></td>
									</tr>
								<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="grid grid-3" style="margin-top:24px;">
						<?php if (!$services): ?>
							<article class="card reveal"><h3>Chưa có dịch vụ</h3><p class="muted">Thêm dịch vụ trong trang quản trị để hiển thị tại đây.</p></article>
						<?php else: foreach ($services as $service): ?>
							<article class="card reveal" style="position:relative;">
								<?php if ((int)($service['is_featured'] ?? 0) === 1): ?>
									<span style="position:absolute;top:12px;right:12px;background:rgba(255,200,0,0.9);color:#1a1a1a;font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:999px;z-index:1;">Tiêu biểu</span>
								<?php endif; ?>
								<img src="<?php echo htmlspecialchars(site_image_url($service['image'] ?? '', '/img/du_an.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;" loading="lazy">
								<h3 style="margin-top:14px;"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
								<p class="muted"><?php echo htmlspecialchars($service['short_desc'] ?: ($service['industry_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
								<p><a href="<?php echo htmlspecialchars(site_page_url('service_detail', ['slug' => $service['slug']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline btn-sm">Xem chi tiết →</a></p>
							</article>
						<?php endforeach; endif; ?></div>
				<?php endif; ?>
			</div>

			<aside class="services-sidebar">
				<div class="card">
					<h3>Các ngành Marketing</h3>
					<ul style="margin:0;padding:0;list-style:none;">
						<?php if (!$industries): ?>
							<li class="muted">Chưa có ngành nào</li>
						<?php else: foreach ($industries as $ind): ?>
							<li style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.03);">
								<a href="<?php echo htmlspecialchars(site_page_url('services', ['industry' => $ind['slug']]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ind['name'], ENT_QUOTES, 'UTF-8'); ?></a>
								<div class="muted" style="font-size:0.92rem;margin-top:6px"><?php echo htmlspecialchars($ind['description'] ?: '', ENT_QUOTES, 'UTF-8'); ?></div>
							</li>
						<?php endforeach; endif; ?></ul>
				</div>
			</aside>
		</div>
	</div>
</section>
