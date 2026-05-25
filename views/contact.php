<section class="hero">
	<div class="container reveal">
		<span class="tag">Liên hệ</span>
		<h1>Kết nối với TanKiet Group</h1>
		<p class="lead">Gửi thông tin nhu cầu, đội ngũ của chúng tôi sẽ liên hệ và tư vấn lộ trình phù hợp trong vòng 24 giờ.</p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Thông tin kết nối</h2>
			<ul class="contact-list">
				<li><strong>Hotline:</strong> 0901 234 567</li>
				<li><strong>Email:</strong> contact@tankiet.group</li>
				<li><strong>Địa chỉ:</strong> Quận 1, TP. Hồ Chí Minh</li>
				<li><strong>Giờ làm việc:</strong> 08:30 - 18:00 (Thứ Hai - Thứ Bảy)</li>
			</ul>
			<p class="muted" style="margin-top:16px;">Bạn có thể đặt lịch hẹn trực tiếp để được phân tích nhanh tình hình marketing hiện tại của doanh nghiệp.</p>
		</article>

		<article class="card reveal">
			<h2>Gửi yêu cầu tư vấn</h2>
			<form id="consultationForm" method="POST" action="/api/save_consultation.php">
				<div class="form-grid">
					<input class="input" type="text" name="name" placeholder="Họ và tên" required>
					<input class="input" type="tel" name="phone" placeholder="Số điện thoại" required>
				</div>
				<p></p>
				<input class="input" type="email" name="email" placeholder="Email">
				<p></p>
				<select class="select" name="goal">
					<option>Mục tiêu của bạn</option>
					<option>Tăng lead</option>
					<option>Tăng doanh thu</option>
					<option>Mở rộng nhận diện thương hiệu</option>
				</select>
				<p></p>
				<textarea class="textarea" name="message" placeholder="Chia sẻ ngắn gọn về doanh nghiệp và nhu cầu"></textarea>
				<p></p>
				<button class="btn btn-primary" type="submit">Đăng ký tư vấn miễn phí</button>
				<div id="formMessage" style="margin-top:12px;display:none;padding:12px;border-radius:8px;"></div>
			</form>
		</article>
	</div>
</section>
