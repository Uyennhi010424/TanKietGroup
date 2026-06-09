<?php
// Admin - Blog interaction statistics
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
    'consultations' => site_page_url('admin_consultations'),
    'clients' => site_page_url('admin_clients'),
];

$loginRoute = site_page_url('admin_login');
$logoutRoute = site_page_url('admin_login', ['logout' => 1]);
admin_require_login($loginRoute);
admin_require_roles(['admin'], $adminRoutes['courses']);

$currentAdminUser = admin_current_user() ?? [];
$adminRole = (string)($currentAdminUser['role'] ?? 'admin');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$dbError = '';
$totalPosts = 0;
$totalViews = 0;
$featuredCount = 0;
$topPosts = [];

try {
    $db = get_db_connection();
    $totalPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    $totalViews = (int)$db->query('SELECT COALESCE(SUM(views), 0) FROM blog_posts')->fetchColumn();
    $featuredCount = (int)$db->query('SELECT COUNT(*) FROM blog_posts WHERE is_featured = 1')->fetchColumn();

    $topPosts = $db->query('SELECT bp.id, bp.title, bp.slug, bp.views, bp.is_featured, bp.status, COALESCE(u.full_name, u.username, "-") AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id ORDER BY bp.views DESC, bp.id DESC LIMIT 20')->fetchAll();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thống kê tương tác - Trang quản trị</title>
    <link rel="icon" href="<?php echo htmlspecialchars(site_favicon_url(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script defer src="/assets/js/admin.js"></script>
</head>
<body class="role-<?php echo h($adminRole); ?>">
<div class="admin-wrap">
    <aside class="admin-sidebar" style="display:block">
            <div class="sidebar-header">
                <div class="brand-admin"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="TanKiet Group" class="site-logo"></div>
            </div>
        <nav>
            <ul class="nav-admin">
                <li><a href="<?php echo $adminRoutes['dashboard']; ?>">Tổng quan</a></li>
                <li><a href="<?php echo $adminRoutes['courses']; ?>">Khóa học</a></li>
                <li><a href="<?php echo $adminRoutes['projects']; ?>">Dự án</a></li>
                <li><a href="<?php echo $adminRoutes['services']; ?>">Dịch vụ</a></li>
                <li><a href="<?php echo $adminRoutes['clients']; ?>">Khách hàng</a></li>
                <li><a href="<?php echo $adminRoutes['users']; ?>">Người dùng</a></li>
                <li><a href="<?php echo $adminRoutes['blog']; ?>">Blog</a></li>
                <li><a href="<?php echo $adminRoutes['recruitments']; ?>">Tuyển dụng</a></li>
                <li><a href="<?php echo $adminRoutes['stats']; ?>">Thống kê tương tác</a></li>
                <li><a href="<?php echo $adminRoutes['consultations']; ?>">Tư vấn khách hàng</a></li>
                <li><form method="post" action="<?php echo htmlspecialchars($loginRoute, ENT_QUOTES, 'UTF-8'); ?>" style="display:inline"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font:inherit;padding:0;">Đăng xuất</button></form></li>
            </ul>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <main class="admin-main">
        <header class="topbar">
            <div style="display:flex;gap:20px;align-items:center">
                <div class="title">
                    <h1>Thống kê tương tác</h1>
                    <div class="small">Lượt tương tác từng bài viết và bài viết nổi bật</div>
                </div>
            </div>
        </header>

        <?php if ($dbError !== ''): ?>
            <div class="card" style="margin-top:18px;background:rgba(255,120,120,0.12);padding:12px 16px;color:#ffb0b0;">
                Loi DB: <?php echo h($dbError); ?>
            </div>
        <?php endif; ?>

        <section class="card-grid" style="margin-top:18px">
            <div class="card">
                <div class="kpi"><?php echo $totalPosts; ?></div>
                <div class="small">Tổng bài viết</div>
            </div>
            <div class="card">
                <div class="kpi"><?php echo $totalViews; ?></div>
                <div class="small">Tổng lượt tương tác (views)</div>
            </div>
            <div class="card">
                <div class="kpi"><?php echo $featuredCount; ?></div>
                <div class="small">Bài viết nổi bật</div>
            </div>
        </section>

        <section style="margin-top:22px">
            <div class="card" style="padding:8px 16px">
                <h3 style="margin:8px 0 12px 0">Top bài viết theo lượt tương tác</h3>
                <div style="overflow:auto">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Tác giả</th>
                            <th>Lượt tương tác</th>
                            <th>Nổi bật</th>
                            <th>Trạng thái</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$topPosts): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu bài viết</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topPosts as $row): ?>
                                <tr>
                                    <td>#BLOG-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo h($row['title']); ?></td>
                                    <td><?php echo h($row['author_name']); ?></td>
                                    <td><?php echo (int)$row['views']; ?></td>
                                    <td><?php echo (int)$row['is_featured'] === 1 ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo h($row['status']); ?></td>
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
