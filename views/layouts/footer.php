<footer class="site-footer">
<?php
require_once __DIR__ . '/../../includes/site.php';
$site = site_settings();
$footerLogo = site_logo_url('/img/logo1.jpg');
?>
    <div class="container footer-grid">
        <div>
            <a class="brand" href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">
            <img src="<?php echo htmlspecialchars($footerLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="TanKiet Group" class="site-logo" loading="lazy">
        </a>
            <p class="muted"><?php echo htmlspecialchars($site['meta_description'] ?: 'Giải pháp Marketing tăng trưởng toàn diện và bền vững cho doanh nghiệp Việt.', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div>
            <h4 class="footer-title">Khám phá</h4>
            <ul class="footer-links">
                <li><a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('about'), ENT_QUOTES, 'UTF-8'); ?>">Giới thiệu</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('courses'), ENT_QUOTES, 'UTF-8'); ?>">Khóa học</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('projects'), ENT_QUOTES, 'UTF-8'); ?>">Dự án</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>">Blog</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('contact'), ENT_QUOTES, 'UTF-8'); ?>">Liên hệ</a></li>
            </ul>
        </div>
        <div class="footer-hide-mobile">
            <h4 class="footer-title">Dịch vụ</h4>
            <ul class="footer-links">
                <li><a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=marketing-tron-goi', ENT_QUOTES, 'UTF-8'); ?>">Marketing tổng thể</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=cham-soc-fanpage', ENT_QUOTES, 'UTF-8'); ?>">Quản trị Fanpage</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=thiet-ke-website', ENT_QUOTES, 'UTF-8'); ?>">Thiết kế Website</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=san-xuat-video', ENT_QUOTES, 'UTF-8'); ?>">Sản xuất Video</a></li>
                <li><a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=to-chuc-su-kien', ENT_QUOTES, 'UTF-8'); ?>">Tổ chức sự kiện</a></li>
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
<?php if (!empty($loadSwiper)): ?>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<?php endif; ?>
<script src="<?php echo site_base_path() . '/assets/js/main.js'; ?>"></script>
</body>
</html>
