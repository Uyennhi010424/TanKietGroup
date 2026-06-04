<?php
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($db)) {
    try { $db = get_db_connection(); } catch (Throwable $e) { $db = null; }
}

$slug     = trim((string)($_GET['slug'] ?? ''));
$industry = null;
$services = [];
$dbError  = '';

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
                "SELECT id, title, slug, short_desc, image, service_type
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
?>

<!-- HERO -->
<section class="hero">
    <div class="container reveal">
        <nav class="breadcrumb">
            <a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
            <span aria-hidden="true">›</span>
            <span>Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>
        <span class="tag">Ngành</span>
        <h1>Marketing cho <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="lead"><?php echo htmlspecialchars($industryDesc, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>

<!-- SERVICES -->
<section class="section">
    <div class="container">

        <?php if ($dbError !== ''): ?>
            <div class="alert-error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (empty($services)): ?>
            <p class="empty-state">Chưa có dịch vụ nào cho ngành này.</p>
        <?php else:
            // Nhóm theo service_type
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
                        $detailUrl = htmlspecialchars(
                            site_page_url('service_detail') . '&slug=' . rawurlencode($svc['slug']),
                            ENT_QUOTES, 'UTF-8'
                        );
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
                                    <a href="<?php echo $detailUrl; ?>">
                                        <?php echo htmlspecialchars($svc['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>

                                <?php if (!empty($svc['short_desc'])): ?>
                                    <p class="service-card__desc">
                                        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($svc['short_desc']), 0, 120, '…'), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>

                                <a class="btn btn-outline btn-sm" href="<?php echo $detailUrl; ?>">Xem chi tiết →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>