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
?>
<section class="hero" style="--hero-banner: url('<?php echo htmlspecialchars(site_image_url($post['thumbnail'] ?? '', '/img/hero.jpg'), ENT_QUOTES, 'UTF-8'); ?>');">
	<div class="container reveal">
		<span class="tag"><?php echo htmlspecialchars($post['category_name'] ?: 'Blog', ENT_QUOTES, 'UTF-8'); ?></span>
		<h1><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="lead">Tác giả: <?php echo htmlspecialchars($post['author_name'] ?: 'TanKiet Group', ENT_QUOTES, 'UTF-8'); ?></p>
	</div>
</section>

<section class="section">
	<div class="container grid grid-2">
		<article class="card reveal">
			<h2>Nội dung bài viết</h2>
			<?php echo $post['content'] ? $post['content'] : '<p>Nội dung bài viết chưa được cập nhật.</p>'; ?>
		</article>
		<article class="card reveal">
			<h2>Thông tin nhanh</h2>
			<ul class="contact-list">
				<li><strong>Danh mục:</strong> <?php echo htmlspecialchars($post['category_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Tác giả:</strong> <?php echo htmlspecialchars($post['author_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Lượt xem:</strong> <?php echo htmlspecialchars((string)($post['views'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></li>
				<li><strong>Trạng thái:</strong> published</li>
			</ul>
		</article>
	</div>
</section>
