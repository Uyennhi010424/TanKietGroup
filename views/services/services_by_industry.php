<?php
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($db)) {
    try { $db = get_db_connection(); } catch (Throwable $e) { $db = null; }
}

$slug         = trim((string)($_GET['slug'] ?? ''));
$serviceSlug  = trim((string)($_GET['service'] ?? ''));
$industry     = null;
$services     = [];
$dbError      = '';

$typeLabels = [
    'marketing-tron-goi'  => 'Marketing trọn gói',
    'cham-soc-fanpage'    => 'Chăm sóc Fanpage',
    'san-xuat-video'      => 'Sản xuất Video',
    'to-chuc-su-kien'     => 'Tổ chức sự kiện',
    'thiet-ke-website'    => 'Thiết kế Website chuẩn SEO',
];

if ($slug !== '' && $db) {
    try {
        $stmt = $db->prepare("SELECT id, name, slug, description FROM industries WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $industry = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($industry) {
            $stmt = $db->prepare(
                "SELECT id, title, slug, short_desc, content, image, service_type
                 FROM services
                 WHERE industry_id = :iid AND status = 1
                 ORDER BY sort_order ASC, created_at DESC"
            );
            $stmt->execute([':iid' => (int)$industry['id']]);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

if (!$industry && !$dbError) {
    http_response_code(404);
    echo '<div class="container section"><p>Không tìm thấy ngành.</p></div>';
    return;
}

$industryName = $industry['name'] ?? '';
$industryDesc = $industry['description'] ?? 'Các dịch vụ marketing dành riêng cho ngành ' . $industryName;

// Find selected service
$selectedService = null;
$packages = [];
if ($serviceSlug !== '' && $db) {
    foreach ($services as $svc) {
        if ($svc['slug'] === $serviceSlug) {
            $selectedService = $svc;
            break;
        }
    }
    if ($selectedService) {
        try {
            ensure_service_packages_table(site_db());
            $packages = site_fetch_all('SELECT * FROM service_packages WHERE service_id = :sid ORDER BY sort_order ASC, id ASC', ['sid' => (int)$selectedService['id']]);
        } catch (Throwable $e) {
            $packages = [];
        }
    }
}

// Dynamic SEO
if ($selectedService) {
    $pageTitle = $selectedService['title'] . ' - Marketing cho ' . $industryName;
    $metaDescOverride = $selectedService['short_desc'] ?: $industryDesc;
    $ogImageOverride = site_image_url($selectedService['image'] ?? '', '/img/hero.jpg');
} else {
    $pageTitle = 'Marketing cho ' . $industryName;
    $metaDescOverride = $industryDesc;
}
?>

<!-- HERO -->
<section class="vintage-hero" style="--hero-banner: url('<?php echo $selectedService ? htmlspecialchars(site_image_url($selectedService['image'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8') : '/img/hero.jpg'; ?>');">
    <div class="container reveal">
        <nav class="breadcrumb" style="margin-bottom:16px;">
            <a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
            <span aria-hidden="true">›</span>
            <?php if ($selectedService): ?>
                <a href="<?php echo htmlspecialchars(site_page_url('industry_detail') . '&slug=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8'); ?>">Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></a>
                <span aria-hidden="true">›</span>
                <span><?php echo htmlspecialchars($selectedService['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
                <span>Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </nav>
        <span class="vintage-hero__category">Ngành</span>
        <h1 class="vintage-hero__title"><?php echo $selectedService ? htmlspecialchars($selectedService['title'], ENT_QUOTES, 'UTF-8') : 'Marketing cho ' . htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="vintage-hero__lead"><?php echo htmlspecialchars($selectedService ? ($selectedService['short_desc'] ?: $industryDesc) : $industryDesc, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>

<?php if ($selectedService): ?>
<!-- Service Detail Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <div class="vintage-prose">
                    <?php
                    $content = trim((string)($selectedService['content'] ?? ''));
                    if ($content === '') {
                        echo '<p>Nội dung chi tiết chưa được cập nhật.</p>';
                    } elseif (str_contains($content, '<')) {
                        echo sanitize_html($content);
                    } else {
                        $lines = explode("\n", $content);
                        $inList = false;
                        foreach ($lines as $line):
                            $line = trim($line);
                            if ($line === ''):
                                if ($inList): echo '</ul>'; $inList = false; endif;
                                continue;
                            endif;
                            if (str_starts_with($line, '•') || str_starts_with($line, '-')):
                                if (!$inList): echo '<ul class="vintage-feature-list">'; $inList = true; endif;
                                $text = ltrim($line, '•- ');
                                ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></li><?php
                                continue;
                            endif;
                            if ($inList): echo '</ul>'; $inList = false; endif;
                            if (str_ends_with($line, ':')):
                                ?><h3><?php echo htmlspecialchars(rtrim($line, ':'), ENT_QUOTES, 'UTF-8'); ?></h3><?php
                                continue;
                            endif;
                            if (mb_strtoupper($line) === $line && mb_strlen($line) > 10):
                                ?><h2><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></h2><?php
                                continue;
                            endif;
                            ?><p><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></p><?php
                        endforeach;
                        if ($inList): echo '</ul>'; endif;
                    }
                    ?>
                </div>
            </article>

            <aside class="vintage-article__sidebar reveal">
                <div class="vintage-sidebar-card">
                    <div class="vintage-sidebar-card__header">
                        <span>✦</span> Thông tin dịch vụ
                    </div>
                    <ul class="vintage-sidebar-card__list">
                        <li>
                            <span class="vintage-sidebar-card__label">Ngành</span>
                            <span class="vintage-sidebar-card__value">Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php if (!empty($selectedService['service_type'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Loại dịch vụ</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($typeLabels[$selectedService['service_type']] ?? $selectedService['service_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php
                        $hotline = trim((string)(site_settings()['hotline'] ?? ''));
                        if ($hotline !== ''): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Hotline</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($hotline, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="vintage-sidebar-card__footer">
                        <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="width:100%;text-align:center;">Nhận tư vấn ngay</a>
                        <a href="<?php echo htmlspecialchars(site_page_url('industry_detail') . '&slug=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Xem tất cả dịch vụ
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Pricing Packages -->
<?php if (!empty($packages)): ?>
<section class="vintage-packages">
    <div class="container">
        <h2 class="vintage-packages__title">Bảng giá dịch vụ</h2>
        <p class="vintage-packages__subtitle">Lựa chọn gói phù hợp với nhu cầu của bạn</p>
        <div class="vintage-packages__grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="vintage-package-card <?php echo (int)$pkg['is_highlighted'] ? 'vintage-package-card--highlighted' : ''; ?>">
                <?php if ((int)$pkg['is_highlighted']): ?>
                <div class="vintage-package-card__badge">Phổ biến nhất</div>
                <?php endif; ?>
                <div class="vintage-package-card__header">
                    <h3 class="vintage-package-card__name"><?php echo htmlspecialchars($pkg['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="vintage-package-card__price">
                        <span class="vintage-package-card__amount"><?php echo htmlspecialchars($pkg['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if (!empty($pkg['price_unit'])): ?>
                        <span class="vintage-package-card__unit"><?php echo htmlspecialchars($pkg['price_unit'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $features = array_filter(array_map('trim', explode("\n", (string)($pkg['features'] ?? ''))));
                if (!empty($features)):
                ?>
                <ul class="vintage-package-card__features">
                    <?php foreach ($features as $feat): ?>
                    <li><?php echo htmlspecialchars($feat, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn <?php echo (int)$pkg['is_highlighted'] ? 'btn-primary' : 'btn-outline'; ?>" style="width:100%;text-align:center;margin-top:auto;">
                    Liên hệ tư vấn
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php else: ?>
<!-- Services List -->
<section class="section">
    <div class="container">
        <?php if ($dbError !== ''): ?>
            <div class="alert-error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (empty($services)): ?>
            <p class="empty-state">Chưa có dịch vụ nào cho ngành này.</p>
        <?php else:
            $grouped = [];
            foreach ($services as $svc) {
                $grouped[(string)($svc['service_type'] ?? '')][] = $svc;
            }
        ?>
            <?php foreach ($grouped as $typeSlug => $group):
                $typeLabel = $typeLabels[$typeSlug] ?? ucfirst(str_replace('-', ' ', $typeSlug));
            ?>
                <?php if ($typeSlug !== ''): ?>
                    <div class="services-group">
                        <div class="services-group__header">
                            <h2 class="services-group__title"><?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <a class="services-group__more"
                               href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=' . rawurlencode($typeSlug), ENT_QUOTES, 'UTF-8'); ?>">
                                Xem tất cả →
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-3 services-group__grid">
                    <?php foreach ($group as $svc):
                        $detailUrl = site_page_url('industry_detail') . '&slug=' . rawurlencode($slug) . '&service=' . rawurlencode($svc['slug']);
                    ?>
                        <article class="service-card reveal">
                            <?php if (!empty($svc['image'])): ?>
                                <div class="service-card__thumb">
                                    <img src="<?php echo htmlspecialchars(site_image_url($svc['image']), ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="<?php echo htmlspecialchars($svc['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                         loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="service-card__thumb service-card__thumb--empty">🎯</div>
                            <?php endif; ?>

                            <div class="service-card__body">
                                <?php if (!empty($svc['service_type'])): ?>
                                    <a class="service-card__tag"
                                       href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=' . rawurlencode($svc['service_type']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($typeLabels[$svc['service_type']] ?? $svc['service_type'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php endif; ?>

                                <h3 class="service-card__title">
                                    <a href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($svc['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>

                                <?php if (!empty($svc['short_desc'])): ?>
                                    <p class="service-card__desc">
                                        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($svc['short_desc']), 0, 120, '…'), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>

                                <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">Xem chi tiết →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>
