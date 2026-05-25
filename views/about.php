<?php
require_once __DIR__ . '/../includes/site.php';

$site = site_settings();
?>
<section class="hero">
	<div class="container reveal">
		<span class="tag">Về TanKiet Group</span>
		<h1>Đồng hành doanh nghiệp trên hành trình tăng trưởng bền vững</h1>
		<p class="lead"><?php echo htmlspecialchars($site['meta_description'] ?: 'Chúng tôi là đối tác chiến lược giúp doanh nghiệp chuyển đổi và tăng trưởng bằng cách tiếp cận dữ liệu.', ENT_QUOTES, 'UTF-8'); ?></p>
	</div>
</section>

<section class="section section-muted">
	<div class="container">
		<h2 class="reveal">Thông tin công ty</h2>
		<div class="grid grid-2" style="margin-top:24px;">
			<article class="card reveal">
				<h3>Tầm nhìn</h3>
				<p class="muted"><?php echo htmlspecialchars($site['company_info'] ?: 'Xây dựng hệ sinh thái marketing và đào tạo thực chiến cho doanh nghiệp Việt.', ENT_QUOTES, 'UTF-8'); ?></p>
			</article>
			<article class="card reveal">
				<h3>Liên hệ</h3>
				<ul class="contact-list">
					<li><strong>Hotline:</strong> <?php echo htmlspecialchars($site['hotline'] ?: '0901 234 567', ENT_QUOTES, 'UTF-8'); ?></li>
					<li><strong>Email:</strong> <?php echo htmlspecialchars($site['email'] ?: 'contact@tankiet.group', ENT_QUOTES, 'UTF-8'); ?></li>
					<li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($site['address'] ?: 'TP. Hồ Chí Minh', ENT_QUOTES, 'UTF-8'); ?></li>
				</ul>
			</article>
		</div>
	</div>
</section>
