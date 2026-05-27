<?php
// Admin - Consultations Management
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

function with_query($route, $params)
{
    $sep = strpos($route, '?') !== false ? '&' : '?';
    return $route . $sep . http_build_query($params);
}

$dbError = '';
$flash = $_GET['msg'] ?? '';
$csrfToken = csrf_token();
$db = null;

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

        if ($action === 'update_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'new');
            if (!in_array($status, ['new', 'processing', 'done'], true)) {
                $status = 'new';
            }
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE consultations SET status = :status WHERE id = :id');
                $stmt->execute(['status' => $status, 'id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['consultations'], ['view' => $id, 'msg' => 'Đã cập nhật trạng thái']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$rows = [];
$detail = null;
if ($db) {
    $rows = $db->query('SELECT id, name, email, phone, service, message, status, created_at FROM consultations ORDER BY id DESC')->fetchAll();

    $viewId = (int)($_GET['view'] ?? 0);
    if ($viewId > 0) {
        $stmt = $db->prepare('SELECT id, name, email, phone, service, message, status, created_at FROM consultations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $viewId]);
        $detail = $stmt->fetch();
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tư vấn khách hàng - Trang quản trị</title>
    <link rel="icon" href="<?php echo htmlspecialchars(site_favicon_url(), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/css/admin.css">
    <script defer src="<?php echo $assetBase; ?>/assets/js/admin.js"></script>
</head>

<body class="role-<?php echo h($adminRole); ?>">
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
                <?php endif; ?>
                <li><a href="<?php echo $adminRoutes['consultations']; ?>">Tư vấn khách hàng</a></li>
                <li><a href="<?php echo $logoutRoute; ?>">Đăng xuất</a></li>
            </ul>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <main class="admin-main">
        <header class="topbar">
            <div style="display:flex;gap:20px;align-items:center">
                <div class="title">
                    <h1>Tư vấn khách hàng</h1>
                    <div class="small">Quản lý các yêu cầu tư vấn từ khách hàng</div>
                </div>

    <div class="admin-wrap">
        <aside class="admin-sidebar" style="display:block">
            <div class="sidebar-header">
                <div class="brand-admin"><img src="<?php echo $assetBase; ?>/logo.php" alt="TanKiet Group" class="site-logo"></div>
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
                    <?php endif; ?>
                    <li><a href="<?php echo $adminRoutes['consultations']; ?>">Tư vấn khách hàng</a></li>
                    <li><a href="<?php echo $logoutRoute; ?>">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>

        <main class="admin-main">
            <header class="topbar">
                <div style="display:flex;gap:20px;align-items:center">
                    <div class="title">
                        <h1>Tư vấn khách hàng</h1>
                        <div class="small">Quản lý các yêu cầu tư vấn từ khách hàng</div>
                    </div>
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
                        Lỗi DB: <?php echo h($dbError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($detail): ?>
                    <div class="card" style="padding:16px;margin-bottom:18px;">
                        <h3 style="margin:0 0 12px 0">Chi tiết tư vấn #CONS-<?php echo str_pad((string)$detail['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
                            <div><strong>Khách hàng:</strong> <?php echo h($detail['name']); ?></div>
                            <div><strong>Email:</strong> <?php echo h($detail['email']); ?></div>
                            <div><strong>Điện thoại:</strong> <?php echo h($detail['phone']); ?></div>
                            <div><strong>Dịch vụ quan tâm:</strong> <?php echo h($detail['service'] ?: '-'); ?></div>
                            <div style="grid-column:1 / -1;"><strong>Nội dung:</strong><br><?php echo nl2br(h($detail['message'])); ?></div>
                            <div><strong>Thời gian gửi:</strong> <?php echo h($detail['created_at']); ?></div>
                            <div>
                                <form method="post" style="display:flex;gap:8px;align-items:center;justify-content:flex-start;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$detail['id']; ?>">
                                    <label class="small" style="margin:0;">Trạng thái:</label>
                                    <select class="form-control" name="status" style="max-width:180px;">
                                        <option value="new" <?php echo $detail['status'] === 'new' ? 'selected' : ''; ?>>Mới</option>
                                        <option value="processing" <?php echo $detail['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="done" <?php echo $detail['status'] === 'done' ? 'selected' : ''; ?>>Đã xử lý</option>
                                    </select>
                                    <button class="btn-admin" type="submit">Cập nhật</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách yêu cầu tư vấn</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên khách hàng</th>
                                    <th>Email</th>
                                    <th>Điện thoại</th>
                                    <th>Nội dung</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--ak-muted);">Chưa có yêu cầu tư vấn nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#CONS-<?php echo str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['name']); ?></td>
                                            <td><?php echo h($row['email']); ?></td>
                                            <td><?php echo h($row['phone']); ?></td>
                                            <td><?php echo h(mb_strimwidth((string)$row['message'], 0, 70, '...')); ?></td>
                                            <td><?php echo h($row['status']); ?></td>
                                            <td style="text-align:right;">
                                                <a class="btn-admin" href="<?php echo h(with_query($adminRoutes['consultations'], ['view' => (int)$row['id']])); ?>">Xem chi tiết</a>
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