<?php
$id = $_GET['id'] ?? '';

$projects = [
	'du_an3' => [
		'title' => 'Hệ sinh thái bất động sản',
		'images' => ['/img/du_an3.jpg'],
		'intro' => 'Tái cấu trúc bộ nhận diện, website và hệ thống lead-gen, tăng 2.7 lần tỷ lệ chốt.',
		'objectives' => [
			'Tái cấu trúc bộ nhận diện thương hiệu',
			'Tăng chuyển đổi từ lead → khách hàng',
			'Xây dựng hệ thống lead-gen ổn định'
		],
		'services' => ['Thiết kế nhận diện', 'Xây dựng website', 'Chiến dịch quảng cáo performance', 'Lead generation'],
		'process' => ['Khảo sát & phân tích', 'Lên chiến lược', 'Thiết kế & phát triển', 'Triển khai quảng cáo', 'Tối ưu & báo cáo'],
		'tech' => ['WordPress', 'Google Analytics', 'Facebook Ads', 'Google Ads'],
		'results' => 'Tăng 2.7 lần tỷ lệ chốt, giảm CPA, tạo nguồn lead ổn định'
	],
	'du_an4' => [
		'title' => 'Chiến dịch F&B đa kênh',
		'images' => ['/img/du_an4.jpg'],
		'intro' => 'Tối ưu creative theo data, giảm 35% chi phí/khách hàng tiềm năng trong 3 tháng.',
		'objectives' => [
			'Giảm chi phí/lead',
			'Tăng nhận diện thương hiệu tại khu vực mục tiêu'
		],
		'services' => ['Creative production', 'Chạy quảng cáo đa kênh', 'Tối ưu landing page'],
		'process' => ['Nghiên cứu khách hàng', 'Sáng tạo concept', 'Thử nghiệm A/B', 'Mở rộng quy mô'],
		'tech' => ['Facebook Ads', 'Google Ads', 'Hotjar'],
		'results' => 'Giảm 35% chi phí/khách hàng tiềm năng; cải thiện quality lead'
	]
];

$project = $projects[$id] ?? null;

if (!$project) {
	echo '<section class="section"><div class="container"><div class="card">';
	echo '<h3>Không tìm thấy dự án</h3>';
	echo '<p class="muted">Dự án bạn tìm không tồn tại hoặc đã bị xóa.</p>';
	echo '<p><a class="btn btn-outline" href="/?page=home">Quay lại trang chủ</a></p>';
	echo '</div></div></section>';
	return;
}

?>

<section class="section">
	<div class="container project-detail">

		<div class="project-back">
			<a class="btn btn-outline" href="/?page=home">← Quay lại</a>
		</div>

		<div class="grid grid-2">
			<div>
				<div class="project-gallery card">
					<?php foreach ($project['images'] as $img): ?>
						<img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" style="width:100%;border-radius:12px;">
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<h2><?php echo htmlspecialchars($project['title']); ?></h2>
				<p class="lead" style="margin-top:8px;"><?php echo htmlspecialchars($project['intro']); ?></p>

				<h4 style="margin-top:18px;">Mục tiêu dự án</h4>
				<ul>
					<?php foreach ($project['objectives'] as $obj): ?>
						<li class="muted"><?php echo htmlspecialchars($obj); ?></li>
					<?php endforeach; ?>
				</ul>

				<h4 style="margin-top:12px;">Dịch vụ thực hiện</h4>
				<ul>
					<?php foreach ($project['services'] as $s): ?>
						<li class="muted"><?php echo htmlspecialchars($s); ?></li>
					<?php endforeach; ?>
				</ul>

				<h4 style="margin-top:12px;">Quy trình thực hiện</h4>
				<ol>
					<?php foreach ($project['process'] as $step): ?>
						<li class="muted"><?php echo htmlspecialchars($step); ?></li>
					<?php endforeach; ?>
				</ol>

				<h4 style="margin-top:12px;">Công nghệ / Công cụ</h4>
				<p class="muted"><?php echo htmlspecialchars(implode(', ', $project['tech'])); ?></p>

				<h4 style="margin-top:12px;">Kết quả đạt được</h4>
				<p class="muted"><?php echo htmlspecialchars($project['results']); ?></p>
			</div>
		</div>
	</div>
</section>