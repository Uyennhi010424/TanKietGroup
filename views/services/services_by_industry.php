<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$industry = null;

if ($slug !== '') {
    try {
        $industry = site_fetch_one('SELECT * FROM industries WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    } catch (Throwable $e) {
        $industry = null;
    }
}

if (!$industry) {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy bài viết</h3><p class="muted">Bài viết bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Quay lại Blog</a></div></div></section>';
    return;
}

$industryName = $industry['name'] ?? '';
$industryDesc = $industry['description'] ?? '';
$industryContent = trim((string)($industry['content'] ?? ''));
$industryImage = $industry['image'] ?? '';

// Dynamic SEO
$pageTitle = $industryName . ' - Kiến thức Marketing';
$metaDescOverride = $industryDesc ?: 'Kiến thức marketing cho ngành ' . $industryName;
$ogImageOverride = site_image_url($industryImage, '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => site_page_url('blog')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $industryName],
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
    <div class="container">
        <a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
        <span class="separator" aria-hidden="true">›</span>
        <a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>">Blog</a>
        <span class="separator" aria-hidden="true">›</span>
        <span class="current"><?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container reveal">
        <span class="tag">Kiến thức Marketing</span>
        <h1><?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($industryDesc): ?>
            <p class="lead"><?php echo htmlspecialchars($industryDesc, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Content -->
<?php if ($industryContent !== ''): ?>
<section class="section">
    <div class="container">
            <article class="card reveal" style="max-width:860px;margin:0 auto;">
                <div class="vintage-prose">
                    <?php
                    if (str_contains($industryContent, '<')) {
                        echo sanitize_html($industryContent);
                    } else {
                        $lines = explode("\n", $industryContent);
                        $inList = false;
                        foreach ($lines as $line):
                            $line = trim($line);
                            if ($line === ''):
                                if ($inList): echo '</ul>'; $inList = false; endif;
                                continue;
                            endif;
                            if (str_starts_with($line, '•') || str_starts_with($line, '-')):
                                if (!$inList): echo '<ul>'; $inList = true; endif;
                                $text = ltrim($line, '•- ');
                                ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></li><?php
                                continue;
                            endif;
                            if ($inList): echo '</ul>'; $inList = false; endif;
                            if (str_ends_with($line, ':')):
                                ?><h3><?php echo htmlspecialchars(rtrim($line, ':'), ENT_QUOTES, 'UTF-8'); ?></h3><?php
                                continue;
                            endif;
                            ?><p><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></p><?php
                        endforeach;
                        if ($inList): echo '</ul>'; endif;
                    }
                    ?>
                </div>
            </article>
    </div>
</section>
<?php endif; ?>

<?php
// Fetch blog posts related to this industry (match by category name)
$industryPosts = [];
try {
    $industryPosts = site_fetch_all(
        "SELECT bp.id, bp.title, bp.slug, bp.thumbnail, bp.published_at, bp.views,
                COALESCE(bc.name, '') AS category_name
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE bp.status = 'published'
           AND (bc.name LIKE :name1 OR bc.name LIKE :name2 OR bc.name LIKE :name3)
         ORDER BY bp.is_featured DESC, bp.published_at DESC
         LIMIT 6",
        [
            'name1' => '%' . $industryName . '%',
            'name2' => '%xây dựng%',
            'name3' => '%marketing%',
        ]
    );
} catch (Throwable $e) {
    $industryPosts = [];
}
?>

<?php if ($industryPosts): ?>
<!-- Blog Posts -->
<section class="section">
    <div class="container">
        <h2 class="section-title reveal">Bài viết liên quan</h2>
        <div class="grid-3" style="margin-top:24px;">
            <?php foreach ($industryPosts as $post): ?>
                <a href="<?php echo htmlspecialchars(site_page_url('blog_detail', ['slug' => $post['slug']]), ENT_QUOTES, 'UTF-8'); ?>" class="card card-link reveal">
                    <?php if ($post['thumbnail']): ?>
                        <div class="card-img">
                            <img src="<?php echo htmlspecialchars(site_image_url($post['thumbnail']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <?php if ($post['category_name']): ?>
                            <span class="tag"><?php echo htmlspecialchars($post['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="btn btn-outline" style="margin-top:auto;">Xem chi tiết</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="section section-muted">
    <div class="container" style="text-align:center;">
        <h2 class="reveal">Bạn cần tư vấn marketing cho ngành <?php echo htmlspecialchars($industryName, ENT_QUOTES, 'UTF-8'); ?>?</h2>
        <p class="muted reveal" style="max-width:560px;margin:12px auto 24px;">Liên hệ ngay để được đội ngũ TanKiet Group tư vấn chiến lược phù hợp.</p>
        <div class="reveal">
            <a href="<?php echo htmlspecialchars(site_page_url('consultations'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Đăng ký tư vấn miễn phí</a>
            <a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="margin-left:12px;">← Quay lại Blog</a>
        </div>
    </div>
</section>
