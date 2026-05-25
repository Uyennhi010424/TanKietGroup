<section class="hero">
	<div class="container reveal">
		<span class="tag">Dịch vụ</span>
		<h1>Tư vấn chuyên nghiệp</h1>
		<p class="lead">Đội ngũ chuyên gia của TanKiet Group sẵn sàng tư vấn và hỗ trợ giải pháp kinh doanh tối ưu cho doanh nghiệp của bạn.</p>
	</div>
</section>

<section class="section">
	<div class="container">
		<h2 style="text-align:center;margin-bottom:2rem">Lĩnh vực tư vấn</h2>
		<div class="grid grid-3">
			<article class="card reveal">
				<h3>Chiến lược kinh doanh</h3>
				<p>Tư vấn phát triển chiến lược dài hạn, định vị thị trường và xây dựng lợi thế cạnh tranh cho doanh nghiệp.</p>
			</article>

			<article class="card reveal">
				<h3>Chuyển đổi số</h3>
				<p>Hỗ trợ doanh nghiệp chuyển đổi số, triển khai hệ thống quản lý hiện đại và tối ưu hóa quy trình.</p>
			</article>

			<article class="card reveal">
				<h3>Marketing & Bán hàng</h3>
				<p>Xây dựng chiến lược marketing toàn diện, tăng hiệu quả bán hàng và mở rộng thị trường.</p>
			</article>

			<article class="card reveal">
				<h3>Thiết kế & Phát triển</h3>
				<p>Tư vấn và triển khai các giải pháp công nghệ, website, ứng dụng phù hợp với nhu cầu kinh doanh.</p>
			</article>

			<article class="card reveal">
				<h3>Quản lý chuỗi cung ứng</h3>
				<p>Tối ưu hóa chuỗi cung ứng, giảm chi phí vận hành và cải thiện hiệu suất logistics.</p>
			</article>

			<article class="card reveal">
				<h3>Phát triển nhân sự</h3>
				<p>Xây dựng đội ngũ chuyên nghiệp, nâng cao kỹ năng cán bộ và phát triển văn hóa công ty.</p>
			</article>
		</div>
	</div>
</section>

<section class="section" style="background:rgba(146,221,214,0.03)">
	<div class="container">
		<h2 style="text-align:center;margin-bottom:2rem">Quy trình tư vấn</h2>
		<div class="grid grid-4" style="gap:2rem">
			<div class="card" style="text-align:center">
				<div style="font-size:2.5rem;font-weight:700;color:var(--primary);margin-bottom:1rem">01</div>
				<h3>Tiếp nhận</h3>
				<p style="color:var(--muted)">Ghi nhận thông tin nhu cầu từ khách hàng</p>
			</div>

			<div class="card" style="text-align:center">
				<div style="font-size:2.5rem;font-weight:700;color:var(--primary);margin-bottom:1rem">02</div>
				<h3>Phân tích</h3>
				<p style="color:var(--muted)">Phân tích tình hình hiện tại và xác định vấn đề</p>
			</div>

			<div class="card" style="text-align:center">
				<div style="font-size:2.5rem;font-weight:700;color:var(--primary);margin-bottom:1rem">03</div>
				<h3>Đề xuất</h3>
				<p style="color:var(--muted)">Đề xuất giải pháp tối ưu phù hợp với mục tiêu</p>
			</div>

			<div class="card" style="text-align:center">
				<div style="font-size:2.5rem;font-weight:700;color:var(--primary);margin-bottom:1rem">04</div>
				<h3>Triển khai</h3>
				<p style="color:var(--muted)">Hỗ trợ triển khai và theo dõi hiệu quả</p>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Liên hệ tư vấn</h2>
			<ul class="contact-list">
				<li><strong>Hotline:</strong> 0901 234 567</li>
				<li><strong>Email:</strong> consultation@tankiet.group</li>
				<li><strong>Địa chỉ:</strong> Quận 1, TP. Hồ Chí Minh</li>
				<li><strong>Giờ làm việc:</strong> 08:30 - 18:00 (Thứ Hai - Thứ Bảy)</li>
			</ul>
			<p class="muted" style="margin-top:16px;">Chúng tôi sẵn sàng nghe, hiểu và cung cấp giải pháp tốt nhất cho doanh nghiệp của bạn.</p>
		</article>

		<article class="card reveal">
			<h2>Gửi yêu cầu tư vấn</h2>
			<form id="consultationForm" method="POST" action="/api/save_consultation.php">
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
