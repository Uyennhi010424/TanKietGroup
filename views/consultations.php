<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/security.php';

$site = site_settings();
?>
<section class="hero">
	<div class="container reveal">
		<span class="tag">Tư vấn</span>
		<h1>Tư vấn chiến lược theo dữ liệu thực</h1>
		<p class="lead">Gửi thông tin nhu cầu, đội ngũ của chúng tôi sẽ liên hệ và tư vấn lộ trình phù hợp trong vòng 24 giờ.</p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Thông tin tư vấn</h2>
			<ul class="contact-list">
				<li><strong>Hotline:</strong> <?php echo htmlspecialchars($site['hotline'] ?: '0901 234 567', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Email:</strong> <?php echo htmlspecialchars($site['email'] ?: 'consultation@tankiet.group', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($site['address'] ?: 'TP. Hồ Chí Minh', ENT_QUOTES, 'UTF-8'); ?></li>
			</ul>
		</article>

		<article class="card reveal">
			<h2>Gửi yêu cầu tư vấn</h2>
			<form id="consultationForm" method="POST" action="/api/save_consultation.php">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
				<div class="form-grid">
					<input class="input" type="text" name="name" placeholder="Tên công ty hoặc cá nhân" required>
					<input class="input" type="tel" name="phone" placeholder="Số điện thoại" required>
				</div>
				<p></p>
				<input class="input" type="email" name="email" placeholder="Email">
				<p></p>
				<select class="select" name="goal">
					<option>-- Chọn lĩnh vực tư vấn --</option>
					<option>Chiến lược kinh doanh</option>
					<option>Chuyển đổi số</option>
					<option>Marketing & Bán hàng</option>
					<option>Thiết kế & Phát triển</option>
					<option>Quản lý chuỗi cung ứng</option>
					<option>Phát triển nhân sự</option>
				</select>
				<p></p>
				<textarea class="textarea" name="message" placeholder="Mô tả ngắn gọn nhu cầu tư vấn của bạn" required></textarea>
				<p></p>
				<button class="btn btn-primary" type="submit">Gửi yêu cầu tư vấn</button>
				<div id="formMessage" style="margin-top:12px;display:none;padding:12px;border-radius:8px;"></div>
			</form>
		</article>
	</div>
</section>
