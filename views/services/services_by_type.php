<?php
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($db)) {
    try { $db = get_db_connection(); } catch (Throwable $e) { $db = null; }
}

$slug = trim((string)($_GET['slug'] ?? ''));
$serviceSlug = trim((string)($_GET['service'] ?? ''));

$typeLabels = [
    'marketing-tron-goi'  => 'Marketing trọn gói (Chiến lược xây kênh)',
    'cham-soc-fanpage'    => 'Chăm sóc Fanpage',
    'san-xuat-video'      => 'Sản xuất Video',
    'to-chuc-su-kien'     => 'Tổ chức sự kiện',
    'thiet-ke-website'    => 'Thiết kế Website chuẩn SEO',
];

$typeDescs = [
    'marketing-tron-goi'  => 'Giải pháp marketing toàn diện — từ chiến lược, nội dung đến phân phối kênh.',
    'cham-soc-fanpage'    => 'Quản lý & vận hành fanpage chuyên nghiệp, tăng tương tác thực.',
    'san-xuat-video'      => 'Sản xuất video chất lượng cao cho quảng cáo, thương hiệu và mạng xã hội.',
    'to-chuc-su-kien'     => 'Lên ý tưởng và tổ chức sự kiện thương hiệu ấn tượng, chuyên nghiệp.',
    'thiet-ke-website'    => 'Thiết kế website chuẩn SEO, tốc độ cao, tối ưu chuyển đổi.',
];

$services = [];
$typeName = $typeLabels[$slug] ?? ucfirst(str_replace('-', ' ', $slug));
$typeDesc = $typeDescs[$slug] ?? 'Danh sách dịch vụ thuộc nhóm ' . $typeName;
$dbError  = '';

// Load all services of this type
if ($slug !== '' && $db) {
    try {
        $stmt = $db->prepare(
            "SELECT id, title, slug, short_desc, content, image, industry_id
             FROM services
             WHERE service_type = :type AND status = 1
             ORDER BY sort_order ASC, created_at DESC"
        );
        $stmt->execute([':type' => $slug]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($services) {
            $industryIds = array_unique(array_filter(array_column($services, 'industry_id')));
            $industryMap = [];
            if ($industryIds) {
                $placeholders = implode(',', array_fill(0, count($industryIds), '?'));
                $iStmt = $db->prepare("SELECT id, name, slug FROM industries WHERE id IN ($placeholders)");
                $iStmt->execute(array_values($industryIds));
                foreach ($iStmt->fetchAll(PDO::FETCH_ASSOC) as $ind) {
                    $industryMap[(int)$ind['id']] = $ind;
                }
            }
            foreach ($services as &$svc) {
                $svc['_industry'] = $industryMap[(int)$svc['industry_id']] ?? null;
            }
            unset($svc);
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

if ($slug === '' || (!isset($typeLabels[$slug]))) {
    http_response_code(404);
    echo '<div class="container section"><p>Không tìm thấy loại dịch vụ.</p></div>';
    return;
}

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

// If only one service, auto-select it
if (!$selectedService && count($services) === 1) {
    $selectedService = $services[0];
    try {
        $packages = site_fetch_all('SELECT * FROM service_packages WHERE service_id = :sid ORDER BY sort_order ASC, id ASC', ['sid' => (int)$selectedService['id']]);
    } catch (Throwable $e) {
        $packages = [];
    }
}

// Dynamic SEO
if ($selectedService) {
    $pageTitle = $selectedService['title'] . ' - ' . $typeName;
    $metaDescOverride = $selectedService['short_desc'] ?: $typeDesc;
    $ogImageOverride = site_image_url($selectedService['image'] ?? '', '/img/hero.jpg');
} else {
    $pageTitle = $typeName . ' - Dịch vụ';
    $metaDescOverride = $typeDesc;
}
?>

<!-- HERO -->
<section class="vintage-hero">
    <div class="hero-bg" style="background-image:url('<?php echo $selectedService ? htmlspecialchars(site_image_url($selectedService['image'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8') : '/img/hero.jpg'; ?>');"></div>
    <div class="container reveal">
        <nav class="breadcrumb" aria-label="Đường dẫn">
            <a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
            <span aria-hidden="true">›</span>
            <?php if ($selectedService): ?>
                <a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></a>
                <span aria-hidden="true">›</span>
                <span><?php echo htmlspecialchars($selectedService['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
                <span><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </nav>
        <span class="vintage-hero__category">Dịch vụ</span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($selectedService ? $selectedService['title'] : $typeName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="vintage-hero__lead"><?php echo htmlspecialchars($selectedService ? ($selectedService['short_desc'] ?: $typeDesc) : $typeDesc, ENT_QUOTES, 'UTF-8'); ?></p>
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
                            <span class="vintage-sidebar-card__label">Loại dịch vụ</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php if (!empty($selectedService['_industry'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Ngành</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($selectedService['_industry']['name'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                        <?php if (count($services) > 1): ?>
                        <a href="<?php echo htmlspecialchars(site_page_url('services_by_type') . '&slug=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Xem tất cả <?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <?php endif; ?>
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
            <p class="empty-state">Chưa có dịch vụ nào trong nhóm này.</p>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($services as $svc):
                    $detailUrl = site_page_url('services_by_type') . '&slug=' . rawurlencode($slug) . '&service=' . rawurlencode($svc['slug']);
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
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
