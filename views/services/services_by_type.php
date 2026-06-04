<?php
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($db)) {
    try { $db = get_db_connection(); } catch (Throwable $e) { $db = null; }
}

$slug = trim((string)($_GET['slug'] ?? ''));

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

if ($slug !== '' && $db) {
    try {
        $stmt = $db->prepare(
            "SELECT id, title, slug, short_desc, image, industry_id
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

if ($slug === '' || (!$services && !$dbError)) {
    http_response_code(404);
    echo '<div class="container section"><p>Không tìm thấy dịch vụ.</p></div>';
    return;
}
?>

<!-- HERO -->
<section class="hero">
    <div class="container reveal">
        <nav class="breadcrumb">
            <a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
            <span aria-hidden="true">›</span>
            <span><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>
        <span class="tag">Dịch vụ</span>
        <h1><?php echo htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="lead"><?php echo htmlspecialchars($typeDesc, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>

<!-- SERVICES GRID -->
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
                    $detailUrl = htmlspecialchars(
                        site_page_url('service_detail') . '&slug=' . rawurlencode($svc['slug']),
                        ENT_QUOTES, 'UTF-8'
                    );
                    $industryLabel = $svc['_industry']['name'] ?? '';
                    $industrySlug  = $svc['_industry']['slug'] ?? '';
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
                            <?php if ($industryLabel !== ''): ?>
                                <a class="service-card__tag"
                                   href="<?php echo htmlspecialchars(site_page_url('industry_detail') . '&slug=' . rawurlencode($industrySlug), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($industryLabel, ENT_QUOTES, 'UTF-8'); ?>
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
        <?php endif; ?>

    </div>
</section>