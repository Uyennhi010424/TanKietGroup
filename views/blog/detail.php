<?php
require_once __DIR__ . '/../../includes/site.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$post = null;

if ($slug !== '') {
    $post = site_fetch_one(
        'SELECT p.*, u.full_name AS author_name, c.name AS category_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         LEFT JOIN blog_categories c ON c.id = p.category_id
         WHERE p.slug = :slug AND p.status = "published"
         LIMIT 1',
        ['slug' => $slug]
    );
}

if (!$post && $slug !== '') {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="card"><h3>Không tìm thấy bài viết</h3><p class="muted">Bài viết bạn tìm không tồn tại hoặc đã bị ẩn.</p><a href="' . htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary" style="margin-top:12px;">Xem tất cả bài viết</a></div></div></section>';
    return;
}

if (!$post) {
    $post = site_fetch_one(
        'SELECT p.*, u.full_name AS author_name, c.name AS category_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         LEFT JOIN blog_categories c ON c.id = p.category_id
         WHERE p.status = "published"
         ORDER BY p.is_featured DESC, p.published_at DESC, p.created_at DESC
         LIMIT 1'
    );
}

if (!$post) {
    echo '<section class="section"><div class="container"><div class="card"><h3>Chưa có bài viết</h3><p class="muted">Thêm bài viết trong admin để trang này hiển thị.</p></div></div></section>';
    return;
}

// Format date
$publishedDate = '';
if (!empty($post['published_at'])) {
    $publishedDate = date('d/m/Y', strtotime($post['published_at']));
} elseif (!empty($post['created_at'])) {
    $publishedDate = date('d/m/Y', strtotime($post['created_at']));
}

// Dynamic SEO
$pageTitle = $post['title'] . ' - Blog';
$contentPreview = mb_substr(strip_tags((string)($post['content'] ?? '')), 0, 155);
$metaDescOverride = $contentPreview !== '' ? $contentPreview . '...' : ($post['meta_title'] ?? 'Bài viết từ TanKiet Group');
$ogImageOverride = site_image_url($post['thumbnail'] ?? '', '/img/hero.jpg');
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_page_url('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => site_page_url('blog')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title']],
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
		<span class="current"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</nav>

<!-- Hero Banner + Title -->
<section class="vintage-hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($post['thumbnail'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
    <div class="container reveal">
        <span class="vintage-hero__category"><?php echo htmlspecialchars($post['category_name'] ?: 'Blog', ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="vintage-hero__title"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="vintage-hero__meta">
            <span class="vintage-hero__author">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php echo htmlspecialchars($post['author_name'] ?: 'TanKiet Group', ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php if ($publishedDate): ?>
            <span class="vintage-hero__date">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php echo $publishedDate; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Article Content -->
<section class="vintage-article">
    <div class="container">
        <div class="vintage-article__layout">
            <article class="vintage-article__content reveal">
                <div class="vintage-prose">
                    <?php echo $post['content'] ? sanitize_html($post['content']) : '<p>Nội dung bài viết chưa được cập nhật.</p>'; ?>
                </div>
            </article>

            <aside class="vintage-article__sidebar reveal">
                <div class="vintage-sidebar-card">
                    <div class="vintage-sidebar-card__header">
                        <span>✦</span> Thông tin bài viết
                    </div>
                    <ul class="vintage-sidebar-card__list">
                        <li>
                            <span class="vintage-sidebar-card__label">Danh mục</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($post['category_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li>
                            <span class="vintage-sidebar-card__label">Tác giả</span>
                            <span class="vintage-sidebar-card__value"><?php echo htmlspecialchars($post['author_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php if ($publishedDate): ?>
                        <li>
                            <span class="vintage-sidebar-card__label">Ngày đăng</span>
                            <span class="vintage-sidebar-card__value"><?php echo $publishedDate; ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="vintage-sidebar-card__footer">
                        <a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>" class="vintage-btn-back">
                            ← Quay lại Blog
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
