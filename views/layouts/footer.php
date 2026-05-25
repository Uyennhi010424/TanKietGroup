<footer class="site-footer">
<?php
require_once __DIR__ . '/../../includes/site.php';
$site = site_settings();
$footerLogo = site_image_url($site['logo'] ?? '', '/img/logo1.jpg');
?>
    <div class="container footer-grid">
        <div>
            <a class="brand" href="/?page=home">
            <img src="<?php echo htmlspecialchars($footerLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="TanKiet Group" class="site-logo">
        </a>
            <p class="muted"><?php echo htmlspecialchars($site['meta_description'] ?: 'Giải pháp Marketing tăng trưởng toàn diện và bền vững cho doanh nghiệp Việt.', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div>
            <h4 class="footer-title">Khám phá</h4>
            <ul class="footer-links">
                <li><a href="/?page=home">Trang chủ</a></li>
                <li><a href="/?page=about">Giới thiệu</a></li>
                <li><a href="/?page=services">Dịch vụ</a></li>
                <li><a href="/?page=courses">Khóa học</a></li>
                <li><a href="/?page=projects">Dự án</a></li>
                <li><a href="/?page=blog">Blog</a></li>
                <li><a href="/?page=contact">Liên hệ</a></li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Dịch vụ</h4>
            <ul class="footer-links">
                <li>Marketing tổng thể</li>
                <li>Quản trị Fanpage</li>
                <li>Thiết kế Website</li>
                <li>Sản xuất Video</li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Liên hệ</h4>
            <ul class="footer-links">
                <li><?php echo htmlspecialchars($site['hotline'] ?: '0901 234 567', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><?php echo htmlspecialchars($site['email'] ?: 'contact@tankiet.group', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><?php echo htmlspecialchars($site['address'] ?: 'TP. Hồ Chí Minh', ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> TanKiet Group. All rights reserved.</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
