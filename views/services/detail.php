<?php 
// services/detail.php
include '../header.php'; 

$slug = $_GET['slug'] ?? '';

// Dữ liệu dịch vụ (bạn có thể mở rộng sau)
$services = [
    'marketing-tong-the' => [
        'title' => 'Marketing Tổng Thể',
        'short_desc' => 'Đồng bộ tất cả kênh từ branding đến performance',
        'image' => '../assets/images/services/marketing.jpg',
        'description' => 'Chúng tôi cung cấp giải pháp marketing tổng thể giúp doanh nghiệp xây dựng thương hiệu và tăng doanh thu bền vững.'
    ],
    'san-xuat' => [
        'title' => 'Sản Xuất',
        'short_desc' => 'Xây dựng giao diện tối ưu SEO và tối đa chuyển đổi',
        'image' => '../assets/images/services/sanxuat.jpg',
        'description' => 'Dịch vụ sản xuất nội dung, video, website chất lượng cao.'
    ]
    // Thêm dịch vụ khác ở đây...
];

$service = $services[$slug] ?? $services['marketing-tong-the'];
?>

<!-- Hero Section -->
<section class="service-hero py-5 text-white" style="background: linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)), url('<?= $service['image'] ?>') center/cover no-repeat;">
    <div class="container">
        <h1 class="display-4 fw-bold text-center"><?= $service['title'] ?></h1>
        <p class="lead text-center"><?= $service['short_desc'] ?></p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h2>Về dịch vụ</h2>
                <p><?= $service['description'] ?></p>

                <h3 class="mt-5">Dịch vụ bao gồm</h3>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Giao diện chuẩn thương hiệu</li>
                    <li><i class="fas fa-check text-success me-2"></i> Responsive mobile</li>
                    <li><i class="fas fa-check text-success me-2"></i> Tối ưu tốc độ & SEO</li>
                    <li><i class="fas fa-check text-success me-2"></i> Tích hợp chat, form liên hệ</li>
                </ul>

                <h3 class="mt-5">Quy trình thực hiện</h3>
                <div class="row text-center">
                    <div class="col-md-3"><div class="step">01</div><p>Tư vấn</p></div>
                    <div class="col-md-3"><div class="step">02</div><p>Thiết kế</p></div>
                    <div class="col-md-3"><div class="step">03</div><p>Triển khai</p></div>
                    <div class="col-md-3"><div class="step">04</div><p>Bàn giao</p></div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h4>Thông tin dịch vụ</h4>
                        <p><strong>Thời gian:</strong> 15 - 45 ngày</p>
                        <p><strong>Giá:</strong> <span class="text-primary">Liên hệ báo giá</span></p>
                        <a href="../lien-he.php" class="btn btn-primary w-100">Nhận tư vấn ngay</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>