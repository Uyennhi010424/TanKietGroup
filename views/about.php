<?php
require_once __DIR__ . '/../includes/site.php';

$site = site_settings();
?>
<!-- ==================== PHẦN FOUNDER / CEO ==================== -->
<section class="founder-section">
	<div class="container">
		<div class="founder-card">
			<div class="founder-grid">
				<!-- Ảnh Founder -->
				<div class="founder-image">
					<img src="/img/founder.jpg" alt="Trần Văn Tấn Kiệt" loading="lazy">
				</div>

				<!-- Nội dung -->
				<div class="founder-info">
					<h2 class="founder-title">Người sáng lập</h2>
					<h3 class="founder-name">Trần Văn Tấn Kiệt</h3>
					<p class="founder-position">Founder & CEO - TanKiet Group</p>

					<p class="founder-desc">
						Với hơn 5 năm kinh nghiệm trong lĩnh vực Marketing và phát triển kinh doanh,
						anh Trần Văn Tấn Kiệt đã đồng hành cùng hàng trăm doanh nghiệp xây dựng,
						kiến trúc, nội thất tại Đồng Bằng Sông Cửu Long.
						Tầm nhìn của anh là đưa TanKiet Group trở thành đơn vị Marketing hàng đầu
						cho ngành xây dựng tại miền Tây.
					</p>

					<div class="founder-badge">
						<span>✓ 100+ Khách hàng</span>
					</div>
				</div>
			</div>
		</div>
	</div>

</section>
<!-- ==================== PHẦN THỐNG KÊ 4 CARD NGANG ==================== -->
<section class="stats-section">
	<div class="container">
		<div class="stats-grid">

			<div class="stat-card">
				<div class="stat-number-wrap">
					<div class="stat-number count" data-target="100">0</div>
					<span class="plus">+</span>
				</div>
				<div class="stat-label">Khách hàng</div>
			</div>

			<div class="stat-card">
				<div class="stat-number-wrap">
					<div class="stat-number count" data-target="2">2</div>
					<span class="plus"> Tỷ+</span>
				</div>
				<div class="stat-label">Ngân sách quản lý</div>
			</div>

			<div class="stat-card">
				<div class="stat-number-wrap">
					<div class="stat-number count" data-target="5">5</div>
					<span class="plus"> năm+</span>
				</div>
				<div class="stat-label">Kinh nghiệm</div>
			</div>

			<div class="stat-card">
				<div class="stat-number-wrap">
					<div class="stat-number count" data-target="89.79">0</div>
					<span class="percent">%</span>
				</div>
				<div class="stat-label">Khách hàng gia hạn HĐ</div>
			</div>

		</div>
	</div>
</section>
<!-- ==================== TẦM NHÌN & TRÁCH NHIỆM ==================== -->
<section class="about-tabs-section">

	<div class="about-tabs">

		<button class="tab-btn active" data-tab="vision">
			Tầm nhìn
		</button>

		<button class="tab-btn" data-tab="responsibility">
			Trách nhiệm
		</button>

	</div>

	<div class="tab-content active" id="vision">

		<h2>Tầm nhìn</h2>
		<div class="title-line"></div>

		<p>
			TanKiet Group hướng tới trở thành đơn vị tiên phong và uy tín hàng đầu
			trong lĩnh vực Marketing chuyên sâu cho ngành xây dựng, kiến trúc và
			nội thất tại Việt Nam.
		</p>

		<p>
			Chúng tôi đồng hành cùng doanh nghiệp trong hành trình chuyển đổi số,
			xây dựng thương hiệu mạnh mẽ, tối ưu chi phí quảng cáo và mang lại
			nguồn khách hàng chất lượng cao, bền vững.
		</p>

	</div>

	<div class="tab-content" id="responsibility">

		<h2>Trách nhiệm</h2>
		<div class="title-line"></div>

		<p>
			TanKiet Group cam kết đặt lợi ích khách hàng làm trọng tâm trong mọi
			hoạt động. Chúng tôi minh bạch trong tư vấn, tối ưu ngân sách hiệu quả
			và đồng hành cùng doanh nghiệp trong suốt quá trình phát triển.
		</p>

		<p>
			Mỗi chiến dịch đều được xây dựng dựa trên dữ liệu thực tế, mục tiêu rõ
			ràng và cam kết mang lại giá trị bền vững cho khách hàng.
		</p>

	</div>

</section>
<script>
	const tabs = document.querySelectorAll('.tab-btn');
	const contents = document.querySelectorAll('.tab-content');

	function showTab(index) {

		tabs.forEach(tab => {
			tab.classList.remove('active');
		});

		contents.forEach(content => {
			content.classList.remove('active');
		});

		tabs[index].classList.add('active');
		contents[index].classList.add('active');
	}

	tabs.forEach((tab, index) => {

		tab.addEventListener('click', () => {
			showTab(index);
		});

	});
</script>