<?php
// Admin - Blog Management
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

$assetBase = site_admin_base_path();
$logoUrl = site_logo_url('/img/logo.jpg');
$adminRoutes = [
    'dashboard' => site_page_url('admin_index'),
    'courses' => site_page_url('admin_courses'),
    'projects' => site_page_url('admin_projects'),
    'services' => site_page_url('admin_services'),
    'users' => site_page_url('admin_users'),
    'blog' => site_page_url('admin_blog'),
    'recruitments' => site_page_url('admin_recruitments'),
    'stats' => site_page_url('admin_stats'),
    'settings' => site_page_url('admin_settings'),
    'consultations' => site_page_url('admin_consultations'),
];

$loginRoute = site_page_url('admin_login');
$logoutRoute = site_page_url('admin_login', ['logout' => 1]);
admin_require_login($loginRoute);
$currentAdminUser = admin_current_user() ?? [];
$adminRole = (string)($currentAdminUser['role'] ?? 'editor');
$isEditor = $adminRole === 'editor';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function make_slug($text)
{
    $text = trim((string)$text);
    $text = mb_strtolower($text, 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'đ' => 'd',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y'
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'blog-post';
}

function with_query($route, $params)
{
    $sep = strpos($route, '?') !== false ? '&' : '?';
    return $route . $sep . http_build_query($params);
}

$db = null;
$dbError = '';
$flash = $_GET['msg'] ?? '';
$csrfToken = csrf_token();

try {
    $db = get_db_connection();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

if ($db && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('CSRF token khong hop le');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare('DELETE FROM blog_posts WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['blog'], ['msg' => 'Da xoa bai viet']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $authorId = ($_POST['author_id'] ?? '') === '' ? null : (int)$_POST['author_id'];
            $categoryId = ($_POST['category_id'] ?? '') === '' ? null : (int)$_POST['category_id'];
            $authorName = trim($_POST['author_name'] ?? '');
            $categoryName = trim($_POST['category_name'] ?? '');
            $status = trim($_POST['status'] ?? 'draft');
            $isFeatured = (int)($_POST['is_featured'] ?? 0) === 1 ? 1 : 0;
            $content = trim($_POST['content'] ?? '');

            if ($title === '') {
                throw new RuntimeException('Tieu de bai viet khong duoc de trong');
            }
            if (!in_array($status, ['draft', 'published'], true)) {
                $status = 'draft';
            }
            if ($slug === '') {
                $slug = make_slug($title);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = :slug AND id <> :id');
            $slugCheck->execute([
                'slug' => $slug,
                'id' => $id,
            ]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug bai viet da ton tai');
            }

            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

            // Resolve or create category when category_name provided
            if (($categoryId === null || $categoryId === 0) && $categoryName !== '') {
                $catSlug = make_slug($categoryName);
                $stmt = $db->prepare('SELECT id FROM blog_categories WHERE slug = :slug OR name = :name LIMIT 1');
                $stmt->execute(['slug' => $catSlug, 'name' => $categoryName]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $categoryId = (int)$found;
                } else {
                    $ins = $db->prepare('INSERT INTO blog_categories (name, slug) VALUES (:name, :slug)');
                    $ins->execute(['name' => $categoryName, 'slug' => $catSlug]);
                    $categoryId = (int)$db->lastInsertId();
                }
            }

            // Resolve or create author when author_name provided
            if (($authorId === null || $authorId === 0) && $authorName !== '') {
                if (preg_match('/^(.+?)\s*\([^\)]*\)$/', $authorName, $matches)) {
                    $authorName = trim($matches[1]);
                }
                $usernameBase = make_slug($authorName);
                if ($usernameBase === '') {
                    $usernameBase = 'author';
                }
                // try find by full_name or username
                $stmt = $db->prepare('SELECT id FROM users WHERE full_name = :name OR username = :uname LIMIT 1');
                $stmt->execute(['name' => $authorName, 'uname' => $usernameBase]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $authorId = (int)$found;
                } else {
                    // ensure unique username
                    $try = $usernameBase;
                    $i = 0;
                    while (true) {
                        $chk = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :uname');
                        $chk->execute(['uname' => $try]);
                        if ((int)$chk->fetchColumn() === 0) break;
                        $i++;
                        $try = $usernameBase . ($i > 0 ? '-' . $i : '');
                    }
                    $randomPass = bin2hex(random_bytes(8));
                    $pwHash = password_hash($randomPass, PASSWORD_DEFAULT);
                    $ins = $db->prepare('INSERT INTO users (username, password, full_name, email, role, status) VALUES (:username, :password, :full_name, :email, :role, :status)');
                    $ins->execute([
                        'username' => $try,
                        'password' => $pwHash,
                        'full_name' => $authorName,
                        'email' => $try . '@local.invalid',
                        'role' => 'editor',
                        'status' => 1,
                    ]);
                    $authorId = (int)$db->lastInsertId();
                }
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE blog_posts SET title = :title, slug = :slug, author_id = :author_id, category_id = :category_id, status = :status, is_featured = :is_featured, content = :content, published_at = :published_at WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'author_id' => $authorId,
                    'category_id' => $categoryId,
                    'status' => $status,
                    'is_featured' => $isFeatured,
                    'content' => $content,
                    'published_at' => $publishedAt,
                ]);
                header('Location: ' . with_query($adminRoutes['blog'], ['msg' => 'Đã cập nhật bài viết']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO blog_posts (title, slug, author_id, category_id, status, is_featured, content, published_at) VALUES (:title, :slug, :author_id, :category_id, :status, :is_featured, :content, :published_at)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'author_id' => $authorId,
                'category_id' => $categoryId,
                'status' => $status,
                'is_featured' => $isFeatured,
                'content' => $content,
                'published_at' => $publishedAt,
            ]);
            header('Location: ' . with_query($adminRoutes['blog'], ['msg' => 'Đã thêm bài viết']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'author_id' => null,
    'category_id' => null,
    'status' => 'draft',
    'is_featured' => 0,
    'content' => '',
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, title, slug, author_id, category_id, status, is_featured, content FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$authors = [];
$categories = [];
$rows = [];
if ($db) {
    $authors = $db->query('SELECT id, full_name, username FROM users ORDER BY id DESC')->fetchAll();
    $categories = $db->query('SELECT id, name FROM blog_categories ORDER BY name ASC')->fetchAll();
    $rows = $db->query('SELECT bp.id, bp.title, bp.status, bp.is_featured, bp.views, bp.published_at, COALESCE(u.full_name, u.username, "-") AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id ORDER BY bp.id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Blog - Trang quản trị</title>
    <link rel="icon" href="<?php echo htmlspecialchars(site_favicon_url(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script defer src="/assets/js/admin.js"></script>
</head>

<body class="role-<?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="admin-wrap">
        <aside class="admin-sidebar" style="display:block">
            <div class="sidebar-header">
                <div class="brand-admin"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="TanKiet Group" class="site-logo"></div>
            </div>
            <nav>
                <ul class="nav-admin">
                    <?php if (!$isEditor): ?>
                        <li><a href="<?php echo $adminRoutes['dashboard']; ?>">Tổng quan</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $adminRoutes['courses']; ?>">Khóa học</a></li>
                    <li><a href="<?php echo $adminRoutes['projects']; ?>">Dự án</a></li>
                    <li><a href="<?php echo $adminRoutes['services']; ?>">Dịch vụ</a></li>
                    <li><a href="<?php echo $adminRoutes['users']; ?>">Người dùng</a></li>
                    <li><a href="<?php echo $adminRoutes['blog']; ?>">Blog</a></li>
                    <li><a href="<?php echo $adminRoutes['recruitments']; ?>">Tuyển dụng</a></li>
                    <?php if (!$isEditor): ?>
                        <li><a href="<?php echo $adminRoutes['stats']; ?>">Thống kê tương tác</a></li>
                        <li><a href="<?php echo $adminRoutes['settings']; ?>">Cài đặt hệ thống</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $adminRoutes['consultations']; ?>">Tư vấn khách hàng</a></li>
                    <li class="nav-admin-logout"><a href="<?php echo $logoutRoute; ?>">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>

        <main class="admin-main">
            <header class="topbar">
                <div style="display:flex;gap:20px;align-items:center">
                    <div class="title">
                        <h1>Blog</h1>
                        <div class="small">Quản lý các bài viết blog</div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:center">
                    <div class="search"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="opacity:0.7">
                            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"></circle>
                        </svg><input placeholder="Tìm kiếm..." style="background:transparent;border:0;color:var(--ak-text);outline:none"></div>
                </div>
            </header>

            <section style="margin-top:22px">
                <?php if ($flash !== ''): ?>
                    <div class="card" style="margin-bottom:16px;background:rgba(146,221,214,0.12);padding:12px 16px;color:#92ddd6;">
                        <?php echo h($flash); ?>
                    </div>
                <?php endif; ?>

                <?php if ($dbError !== ''): ?>
                    <div class="card" style="margin-bottom:16px;background:rgba(255,120,120,0.12);padding:12px 16px;color:#ffb0b0;">
                        Loi DB: <?php echo h($dbError); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="padding:16px;margin-bottom:18px;">
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa bài viết' : 'Thêm bài viết'; ?></h3>
                    <form method="post" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

                        <div>
                            <label class="small">Tiêu đề</label>
                            <input class="form-control" type="text" name="title" required value="<?php echo h($editing['title']); ?>">
                        </div>
                        <div>
                            <label class="small">Slug</label>
                            <input class="form-control" type="text" name="slug" value="<?php echo h($editing['slug']); ?>">
                        </div>
                        <div>
                            <label class="small">Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="draft" <?php echo $editing['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $editing['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        <div>
                            <label class="small">Bài viết nổi bật</label>
                            <select class="form-control" name="is_featured">
                                <option value="0" <?php echo (int)$editing['is_featured'] === 0 ? 'selected' : ''; ?>>Không</option>
                                <option value="1" <?php echo (int)$editing['is_featured'] === 1 ? 'selected' : ''; ?>>Có</option>
                            </select>
                        </div>

                        <div>
                            <label class="small">Tác giả</label>
                            <input class="form-control" type="text" name="author_name" list="authors-list" value="<?php
                                    $authorDisplay = '';
                                    if ((int)$editing['author_id'] > 0) {
                                        foreach ($authors as $a) { if ((int)$a['id'] === (int)$editing['author_id']) { $authorDisplay = trim(($a['full_name'] ?? '') . ' (' . ($a['username'] ?? '') . ')'); break; } }
                                    }
                                    echo h($authorDisplay);
                                ?>">
                            <datalist id="authors-list">
                                <?php foreach ($authors as $author): ?>
                                    <?php $authorLabel = trim(($author['full_name'] ?? '') . ' (' . ($author['username'] ?? '') . ')'); ?>
                                    <option value="<?php echo h($authorLabel); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div>
                            <label class="small">Danh mục</label>
                            <input class="form-control" type="text" name="category_name" list="categories-list" value="<?php
                                    $catDisplay = '';
                                    if ((int)$editing['category_id'] > 0) {
                                        foreach ($categories as $c) { if ((int)$c['id'] === (int)$editing['category_id']) { $catDisplay = $c['name']; break; } }
                                    }
                                    echo h($catDisplay);
                                ?>">
                            <datalist id="categories-list">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo h($category['name']); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung</label>
                            <textarea class="form-control" name="content" rows="5"><?php echo h($editing['content']); ?></textarea>
                        </div>

                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['blog']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách bài viết</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Tác giả</th>
                                    <th>Ngày đăng</th>
                                    <th>Lượt tương tác</th>
                                    <th>Nổi bật</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu blog</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#BLOG-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><?php echo h($row['author_name']); ?></td>
                                            <td><?php echo h($row['published_at'] ? date('d/m/Y', strtotime($row['published_at'])) : '-'); ?></td>
                                            <td><?php echo (int)($row['views'] ?? 0); ?></td>
                                            <td><?php echo (int)($row['is_featured'] ?? 0) === 1 ? 'Yes' : 'No'; ?></td>
                                            <td><?php echo h($row['status']); ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['blog'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');" style="margin:0;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                    <button class="btn-admin" style="background:#ff8c8c;color:#3d1111;" type="submit">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </main>
    </div>
</body>

</html>
