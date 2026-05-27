<?php
$page = $_GET['page'] ?? 'home';

$publicViewMap = [
    'home' => [
        'view' => __DIR__ . '/../views/home.php',
        'title' => 'Trang chủ',
    ],
    'about' => [
        'view' => __DIR__ . '/../views/about.php',
        'title' => 'Giới thiệu',
    ],
    'services' => [
        'view' => __DIR__ . '/../views/services/index.php',
        'title' => 'Dịch vụ',
    ],
    'service_detail' => [
        'view' => __DIR__ . '/../views/services/detail.php',
        'title' => 'Chi tiết dịch vụ',
    ],
    'courses' => [
        'view' => __DIR__ . '/../views/courses/index.php',
        'title' => 'Khóa học',
    ],
    'course_detail' => [
        'view' => __DIR__ . '/../views/courses/detail.php',
        'title' => 'Chi tiết khóa học',
    ],
    'projects' => [
        'view' => __DIR__ . '/../views/projects/index.php',
        'title' => 'Dự án',
    ],
    'project_detail' => [
        'view' => __DIR__ . '/../views/projects/detail.php',
        'title' => 'Chi tiết dự án',
    ],
    'blog' => [
        'view' => __DIR__ . '/../views/blog/index.php',
        'title' => 'Blog',
    ],
    'blog_detail' => [
        'view' => __DIR__ . '/../views/blog/detail.php',
        'title' => 'Chi tiết bài viết',
    ],
    'contact' => [
        'view' => __DIR__ . '/../views/contact.php',
        'title' => 'Liên hệ',
    ],
    'consultations' => [
        'view' => __DIR__ . '/../views/consultations.php',
        'title' => 'Tư vấn',
    ],
];

if (isset($publicViewMap[$page])) {
    $currentPage = $page;
    $pageTitle = $publicViewMap[$page]['title'];

    if (($publicViewMap[$page]['layout'] ?? 'main') === 'none') {
        include $publicViewMap[$page]['view'];
        return;
    }

    ob_start();
    include $publicViewMap[$page]['view'];
    $content = ob_get_clean();

    include __DIR__ . '/../views/layouts/main.php';
    return;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/site.php';

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
admin_require_roles(['admin'], $adminRoutes['courses']);
$currentAdminUser = admin_current_user() ?? [];
$adminRole = (string)($currentAdminUser['role'] ?? 'admin');

$db = null;
$serviceRows = [];
$countServices = 0;
$countProjects = 0;
$countPosts = 0;
try {
    $db = get_db_connection();
    $serviceRows = $db->query('SELECT s.id, s.title, s.slug, s.status, COALESCE(i.name, "-") AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.id DESC')->fetchAll();
    $countServices = (int)$db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $countProjects = (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $countPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
} catch (Throwable $e) {
    // leave counts as zero and rows empty if DB not available
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Trang quản trị - TanKiet Group</title>
    <link rel="icon" href="<?php echo htmlspecialchars(site_favicon_url(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/css/admin.css">
    <script defer src="<?php echo $assetBase; ?>/assets/js/admin.js"></script>
</head>

<body class="role-<?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?>">

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
                    <li><a href="<?php echo $adminRoutes['users']; ?>">Người dùng</a></li>
                    <li><a href="<?php echo $adminRoutes['blog']; ?>">Blog</a></li>
                    <li><a href="<?php echo $adminRoutes['recruitments']; ?>">Tuyển dụng</a></li>
                    <li><a href="<?php echo $adminRoutes['stats']; ?>">Thống kê tương tác</a></li>
                    <li><a href="<?php echo $adminRoutes['settings']; ?>">Cài đặt hệ thống</a></li>
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
                        <h1>Tổng quan</h1>
                        <div class="small">Chào mừng đến trang quản trị</div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:center">
                    <div class="search"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="opacity:0.7">
                            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"></circle>
                        </svg><input placeholder="Tìm kiếm..." style="background:transparent;border:0;color:var(--ak-text);outline:none"></div>
                </div>
            </header>

            <section class="card-grid" style="margin-top:18px">
                <div class="card">
                    <div class="kpi"><?php echo (int)$countServices; ?></div>
                    <div class="small">Dịch vụ</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo (int)$countProjects; ?></div>
                    <div class="small">Dự án</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo (int)$countPosts; ?></div>
                    <div class="small">Bài viết</div>
                </div>
            </section>

            <section style="display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-top:22px">
                <div class="card" style="min-height:320px">
                    <h3 style="margin:0 0 12px 0">Tăng trưởng Lead</h3>
                    <div style="height:240px;background:linear-gradient(180deg,rgba(146,221,214,0.06),transparent);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--ak-muted)">Biểu đồ (placeholder)</div>
                </div>
                <div class="card">
                    <h3 style="margin:0 0 12px 0">Hoạt động gần đây</h3>
                    <div class="activity">
                        <div class="activity-item">
                            <div style="width:40px;height:40px;border-radius:8px;background:rgba(146,221,214,0.06);display:flex;align-items:center;justify-content:center;color:var(--ak-primary)">A</div>
                            <div>
                                <div style="font-weight:700">Admin</div>
                                <div style="color:var(--ak-muted)">Cập nhật bài viết mới</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div style="width:40px;height:40px;border-radius:8px;background:rgba(146,221,214,0.06);display:flex;align-items:center;justify-content:center;color:var(--ak-primary)">L</div>
                            <div>
                                <div style="font-weight:700">Khách hàng</div>
                                <div style="color:var(--ak-muted)">Gửi yêu cầu tư vấn</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section style="margin-top:22px">
                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Dịch vụ</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên dịch vụ</th>
                                    <th>Ngành</th>
                                    <th>Slug</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($serviceRows)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu dịch vụ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($serviceRows as $row): ?>
                                        <tr>
                                            <td>#SVC-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge"><?php echo htmlspecialchars($row['industry_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td style="text-align:right"> <a class="btn-admin" href="<?php echo $adminRoutes['services']; ?>?edit=<?php echo (int)$row['id']; ?>">Sửa</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <button class="fab" title="Thêm">+</button>

        </main>
    </div>
</body>

</html>