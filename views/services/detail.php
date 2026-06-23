<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$service = null;

if ($slug !== '') {
    $service = site_fetch_one(
        'SELECT s.*, i.name AS industry_name
         FROM services s
         LEFT JOIN industries i ON i.id = s.industry_id
         WHERE s.slug = :slug AND s.status = 1
         LIMIT 1',
        ['slug' => $slug]
    );
}

if (!$service && $slug !== '') {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy dịch vụ</h3><p class="muted">Dịch vụ bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Xem tất cả dịch vụ</a></div></div></section>';
    return;
}

if (!$service) {
    $service = site_fetch_one(
        'SELECT s.*, i.name AS industry_name
         FROM services s
         LEFT JOIN industries i ON i.id = s.industry_id
         WHERE s.status = 1
         ORDER BY s.sort_order ASC, s.id DESC
         LIMIT 1'
    );
}

if (!$service) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có dịch vụ</h3><p class="muted">Thêm dịch vụ trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}

// Load packages
$packages = [];
try {
    ensure_service_packages_table(site_db());
    $packages = site_fetch_all('SELECT * FROM service_packages WHERE service_id = :sid ORDER BY sort_order ASC, id ASC', ['sid' => (int)$service['id']]);
} catch (Throwable $e) {
    $packages = [];
}

// Dynamic SEO
$pageTitle = $service['title'] . ' - Dịch vụ';
$metaDescOverride = $service['short_desc'] ?: ($service['meta_description'] ?? '');
$ogImageOverride = site_image_url($service['image'] ?? '', '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dịch vụ', 'item' => site_page_url('services')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $service['title']],
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>">Dịch vụ</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>

<!-- Hero Banner + Title -->
<section class="vintage-hero">
    <div class="hero-bg" style="background-image:url('<?php echo htmlspecialchars(site_image_url($service['image'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');"></div>
    <div class="container reveal">
        <span class="vintage-hero__category"><?php echo htmlspecialchars($service['industry_name'] ?? 'Dịch vụ', ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if (!empty($service['short_desc'])): ?>
        <p class="vintage-hero__lead"><?php echo htmlspecialchars($service['short_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Article Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <div class="vintage-prose">
                    <?php
                    $content = trim((string)($service['content'] ?? ''));
                    if ($content === '') {
                        echo '<p>Nội dung chi tiết chưa được cập nhật.</p>';
                    } elseif (str_contains($content, '<')) {
                        echo sanitize_html($content);
                    } else {
                        // Parse plain text to structured HTML
                        $lines = explode("\n", $content);
                        $inList = false;
                        foreach ($lines as $line):
                            $line = trim($line);
                            if ($line === ''):
                                if ($inList): echo '</ul>'; $inList = false; endif;
                                continue;
                            endif;

                            // Bullet point (starts with • or -)
                            if (str_starts_with($line, '•') || str_starts_with($line, '-')):
                                if (!$inList): echo '<ul class="vintage-feature-list">'; $inList = true; endif;
                                $text = ltrim($line, '•- ');
                                ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></li><?php
                                continue;
                            endif;

                            if ($inList): echo '</ul>'; $inList = false; endif;

                            // Section header (ends with :)
                            if (str_ends_with($line, ':')):
                                ?><h3><?php echo htmlspecialchars(rtrim($line, ':'), ENT_QUOTES, 'UTF-8'); ?></h3><?php
                                continue;
                            endif;

                            // Title detection (ALL CAPS or first meaningful line with special chars)
                            if (mb_strtoupper($line) === $line && mb_strlen($line) > 10):
                                ?><h2><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></h2><?php
                                continue;
                            endif;

                            // Regular paragraph
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
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($service['industry_name'] ?? 'Chưa phân loại', ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li>
                            <span class="vintage-sidebar-card__label">Trạng thái</span>
                            <span class="vintage-sidebar-card__value">Đang hiển thị</span>
                        </li>
                        <?php if (!empty($service['service_type'])): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Loại dịch vụ</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($service['service_type'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                        <a href="<?php echo htmlspecialchars(site_page_url('services'), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back" style="margin-top:12px;display:block;text-align:center;">
                            ← Quay lại Dịch vụ
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
