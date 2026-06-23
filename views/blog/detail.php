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

$thumbUrl = site_image_url($post['thumbnail'] ?? '', '/img/hero.jpg');
function bl_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Đường dẫn">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Trang chủ</a>
		<span class="separator" aria-hidden="true">›</span>
		<a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>">Blog</a>
		<span class="separator" aria-hidden="true">›</span>
		<span class="current"><?php echo bl_h($post['title']); ?></span>
	</div>
</nav>

<!-- Hero -->
<section class="bl-hero">
	<div class="container bl-hero__inner reveal">
		<div class="bl-hero__content">
			<?php if (!empty($post['category_name'])): ?>
				<span class="bl-hero__tag"><?php echo bl_h($post['category_name']); ?></span>
			<?php endif; ?>
			<h1 class="bl-hero__title"><?php echo bl_h($post['title']); ?></h1>
			<div class="bl-hero__meta">
				<?php if (!empty($post['author_name'])): ?>
					<span class="bl-meta__item"><?php echo bl_h($post['author_name']); ?></span>
				<?php endif; ?>
				<?php if ($publishedDate): ?>
					<span class="bl-meta__item bl-meta__item--date"><?php echo $publishedDate; ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<!-- Thumbnail -->
<?php if (!empty($post['thumbnail'])): ?>
<section class="bl-thumb reveal">
	<div class="container">
		<div class="bl-thumb__wrap">
			<img src="<?php echo htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo bl_h($post['title']); ?>">
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Content -->
<section class="bl-body">
	<div class="container">
		<article class="bl-content reveal">
			<?php
			$rawContent = trim((string)($post['content'] ?? ''));
			if ($rawContent === '') {
				echo '<p>Nội dung bài viết chưa được cập nhật.</p>';
			} elseif (str_contains($rawContent, '<')) {
				// Content has HTML tags — render as sanitized HTML
				echo sanitize_html($rawContent);
			} else {
				// Plain text / Markdown — parse into HTML
				$lines = preg_split('/\r\n|\r|\n/', $rawContent);
				$inList = false;
				foreach ($lines as $line) {
					$line = trim($line);
					if ($line === '') {
						if ($inList) { echo '</ul>'; $inList = false; }
						continue;
					}
					// ## Heading 2
					if (str_starts_with($line, '## ')) {
						if ($inList) { echo '</ul>'; $inList = false; }
						echo '<h2>' . htmlspecialchars(trim(substr($line, 3)), ENT_QUOTES, 'UTF-8') . '</h2>';
						continue;
					}
					// # Heading 1
					if (str_starts_with($line, '# ')) {
						if ($inList) { echo '</ul>'; $inList = false; }
						echo '<h2>' . htmlspecialchars(trim(substr($line, 2)), ENT_QUOTES, 'UTF-8') . '</h2>';
						continue;
					}
					// Bullet points: * or - or •
					if (preg_match('/^[\*\-•]\s+/', $line)) {
						if (!$inList) { echo '<ul>'; $inList = true; }
						$text = preg_replace('/^[\*\-•]\s+/', '', $line);
						echo '<li>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';
						continue;
					}
					// ✅ Checkmark lines
					if (str_starts_with($line, '✅')) {
						if (!$inList) { echo '<ul>'; $inList = true; }
						$text = trim(substr($line, 3));
						echo '<li>✅ ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';
						continue;
					}
					// ALL CAPS line → bold heading
					if ($inList) { echo '</ul>'; $inList = false; }
					if (preg_match('/^[A-ZÀ-ẠẢÃÁÂẦẤẬẨẪĂẰẮẶẲẴÈẸẺẼÉÊỀẾỆỂỄÌÍỊỈĨÒỌỎÕÓÔỒỐỘỔỖƠỜỚỢỞỠÙỤỦŨÚỪỨỰỬỮỲỴỶỸĐ\s\.\&\(\)\-:]+$/u', $line) && mb_strlen($line) > 3) {
						echo '<h3>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</h3>';
					} else {
						echo '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
					}
				}
				if ($inList) { echo '</ul>'; }
			}
			?>
		</article>
	</div>
</section>

<!-- Back to Blog -->
<section class="bl-footer reveal">
	<div class="container">
		<a href="<?php echo htmlspecialchars(site_page_url('blog'), ENT_QUOTES, 'UTF-8'); ?>" class="bl-back">
			← Quay lại tất cả bài viết
		</a>
	</div>
</section>
