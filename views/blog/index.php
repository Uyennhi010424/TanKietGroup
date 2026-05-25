<?php
require_once __DIR__ . '/../../includes/site.php';

$posts = site_fetch_all(
    'SELECT p.id, p.title, p.slug, p.thumbnail, p.meta_title, p.published_at, u.full_name AS author_name, c.name AS category_name
     FROM blog_posts p
     LEFT JOIN users u ON u.id = p.author_id
     LEFT JOIN blog_categories c ON c.id = p.category_id
     WHERE p.status = "published"
     ORDER BY p.is_featured DESC, p.published_at DESC, p.created_at DESC'
);
?>
<section class="hero">
	<div class="container reveal">
		<span class="tag">Blog</span>
		<h1>Bài viết được đăng từ admin</h1>
		<p class="lead">Bất kỳ bài viết nào admin tạo và đặt trạng thái `published` đều sẽ xuất hiện ở đây.</p>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="grid grid-3">
			<?php if (!$posts): ?>
				<article class="card reveal"><h3>Chưa có bài viết</h3><p class="muted">Thêm bài viết trong admin để hiển thị tại đây.</p></article>
			<?php else: ?>
				<?php foreach ($posts as $post): ?>
					<article class="card reveal">
						<img src="<?php echo htmlspecialchars(site_image_url($post['thumbnail'] ?? '', '/img/du_an4.jpg'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:12px;">
						<h3 style="margin-top:14px;"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
						<p class="muted"><?php echo htmlspecialchars($post['category_name'] ?: 'Blog', ENT_QUOTES, 'UTF-8'); ?></p>
						<p><a href="/?page=blog_detail&amp;slug=<?php echo rawurlencode($post['slug']); ?>">Đọc bài viết</a></p>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
