<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/security.php';

$site = site_settings();
?>
<section class="hero">
	<div class="container reveal">
		<span class="tag">Liên hệ</span>
		<h1>Kết nối với <?php echo htmlspecialchars($site['site_name'] ?: APP_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="lead">Gửi thông tin nhu cầu, đội ngũ của chúng tôi sẽ liên hệ và tư vấn lộ trình phù hợp trong vòng 24 giờ.</p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Thông tin kết nối</h2>
			<ul class="contact-list">
				<li><strong>Hotline:</strong> <?php echo htmlspecialchars($site['hotline'] ?: '0901 234 567', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Email:</strong> <?php echo htmlspecialchars($site['email'] ?: 'contact@tankiet.group', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($site['address'] ?: 'TP. Hồ Chí Minh', ENT_QUOTES, 'UTF-8'); ?></li>
			</ul>
		</article>

		<article class="card reveal">
			<h2>Gửi yêu cầu tư vấn</h2>
			<form id="consultationForm" method="POST" action="/api/save_consultation.php">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
				<div class="form-grid">
					<div>
						<label for="contact-name" class="sr-only">Họ và tên</label>
						<input class="input" id="contact-name" type="text" name="name" placeholder="Họ và tên" required>
					</div>
					<div>
						<label for="contact-phone" class="sr-only">Số điện thoại</label>
						<input class="input" id="contact-phone" type="tel" name="phone" placeholder="Số điện thoại" required>
					</div>
				</div>
				<div style="margin-top:12px;">
					<label for="contact-email" class="sr-only">Email</label>
					<input class="input" id="contact-email" type="email" name="email" placeholder="Email">
				</div>
				<div style="margin-top:12px;">
					<label for="contact-goal" class="sr-only">Mục tiêu</label>
					<select class="select" id="contact-goal" name="goal">
						<option value="" disabled selected>Mục tiêu của bạn</option>
						<option>Tăng lead</option>
						<option>Tăng doanh thu</option>
						<option>Mở rộng nhận diện thương hiệu</option>
					</select>
				</div>
				<div style="margin-top:12px;">
					<label for="contact-message" class="sr-only">Nội dung</label>
					<textarea class="textarea" id="contact-message" name="message" placeholder="Chia sẻ ngắn gọn về doanh nghiệp và nhu cầu"></textarea>
				</div>
				<button class="btn btn-primary" type="submit" style="margin-top:16px;">Đăng ký tư vấn miễn phí</button>
				<div id="formMessage" style="margin-top:12px;display:none;padding:12px;border-radius:8px;"></div>
			</form>
		</article>
	</div>
</section>
