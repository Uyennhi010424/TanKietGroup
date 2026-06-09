<?php
// Admin - Clients Management (Khách hàng tiêu biểu)
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

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
    'clients' => site_page_url('admin_clients'),
];
$mediaRoute = site_page_url('admin_media') . '&path=';

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
            throw new RuntimeException('CSRF token không hợp lệ');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare('DELETE FROM clients WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['clients'], ['msg' => 'Đã xóa khách hàng']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $websiteUrl = trim($_POST['website_url'] ?? '');
            $logo = trim($_POST['current_logo'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $status = (int)($_POST['status'] ?? 1);

            if ($name === '') {
                throw new RuntimeException('Tên công ty không được để trống');
            }

            // handle uploaded logo
            $uploaded = store_uploaded_image('logo_file', 'uploads/clients');
            if ($uploaded !== null) {
                $logo = $uploaded;
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE clients SET name = :name, logo = :logo, website_url = :website_url, sort_order = :sort_order, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'name' => $name,
                    'logo' => $logo,
                    'website_url' => $websiteUrl,
                    'sort_order' => $sortOrder,
                    'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['clients'], ['msg' => 'Đã cập nhật khách hàng']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO clients (name, logo, website_url, sort_order, status) VALUES (:name, :logo, :website_url, :sort_order, :status)');
            $stmt->execute([
                'name' => $name,
                'logo' => $logo,
                'website_url' => $websiteUrl,
                'sort_order' => $sortOrder,
                'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['clients'], ['msg' => 'Đã thêm khách hàng']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0,
    'name' => '',
    'logo' => '',
    'website_url' => '',
    'sort_order' => 0,
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, name, logo, website_url, sort_order, status FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$rows = [];
if ($db) {
    $rows = $db->query('SELECT id, name, logo, website_url, sort_order, status FROM clients ORDER BY sort_order ASC, id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Khách hàng - Trang quản trị</title>
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
                    <li><a href="<?php echo $adminRoutes['clients']; ?>" class="active">Khách hàng</a></li>
                    <li><a href="<?php echo $adminRoutes['users']; ?>">Người dùng</a></li>
                    <li><a href="<?php echo $adminRoutes['blog']; ?>">Blog</a></li>
                    <li><a href="<?php echo $adminRoutes['recruitments']; ?>">Tuyển dụng</a></li>
                    <?php if (!$isEditor): ?>
                        <li><a href="<?php echo $adminRoutes['stats']; ?>">Thống kê tương tác</a></li>
                        <li><a href="<?php echo $adminRoutes['settings']; ?>">Cài đặt hệ thống</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $adminRoutes['consultations']; ?>">Tư vấn khách hàng</a></li>
                    <li class="nav-admin-logout"><form method="post" action="<?php echo htmlspecialchars($loginRoute, ENT_QUOTES, 'UTF-8'); ?>" style="display:inline"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font:inherit;padding:0;">Đăng xuất</button></form></li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>

        <main class="admin-main">
            <header class="topbar">
                <div style="display:flex;gap:20px;align-items:center">
                    <div class="title">
                        <h1>Khách hàng tiêu biểu</h1>
                        <div class="small">Quản lý logo và thông tin khách hàng hợp tác</div>
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
                        Lỗi DB: <?php echo h($dbError); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="padding:16px;margin-bottom:18px;">
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa khách hàng' : 'Thêm khách hàng'; ?></h3>
                    <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

                        <div>
                            <label class="small">Tên công ty</label>
                            <input class="form-control" type="text" name="name" required value="<?php echo h($editing['name']); ?>" placeholder="Nhập tên công ty">
                        </div>
                        <div>
                            <label class="small">Website URL</label>
                            <input class="form-control" type="url" name="website_url" value="<?php echo h($editing['website_url']); ?>" placeholder="https://example.com">
                        </div>
                        <div>
                            <label class="small">Thứ tự hiển thị</label>
                            <input class="form-control" type="number" name="sort_order" value="<?php echo (int)$editing['sort_order']; ?>" min="0">
                        </div>
                        <div>
                            <label class="small">Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="1" <?php echo (int)$editing['status'] === 1 ? 'selected' : ''; ?>>Hiển thị</option>
                                <option value="0" <?php echo (int)$editing['status'] === 0 ? 'selected' : ''; ?>>Ẩn</option>
                            </select>
                        </div>
                        <div>
                            <label class="small">Logo công ty</label>
                            <input class="form-control" type="file" name="logo_file" accept="image/*">
                            <input type="hidden" name="current_logo" value="<?php echo h($editing['logo'] ?? ''); ?>">
                            <?php if (!empty($editing['logo'])): ?>
                                <div class="small" style="margin-top:8px">Logo hiện tại: <?php echo h($editing['logo']); ?></div>
                                <img src="<?php echo h($mediaRoute . rawurlencode($editing['logo'])); ?>" alt="" style="max-height:60px;margin-top:8px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);">
                            <?php endif; ?>
                        </div>
                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['clients']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách khách hàng</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Logo</th>
                                    <th>Tên công ty</th>
                                    <th>Website</th>
                                    <th>Thứ tự</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu khách hàng</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#CL-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <?php if (!empty($row['logo'])): ?>
                                                    <img src="<?php echo h($mediaRoute . rawurlencode($row['logo'])); ?>" alt="<?php echo h($row['name']); ?>" style="max-height:40px;max-width:80px;border-radius:6px;">
                                                <?php else: ?>
                                                    <span class="small" style="color:var(--ak-muted);">Chưa có</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo h($row['name']); ?></td>
                                            <td>
                                                <?php if (!empty($row['website_url'])): ?>
                                                    <a href="<?php echo h($row['website_url']); ?>" target="_blank" style="color:var(--ak-primary);"><?php echo h($row['website_url']); ?></a>
                                                <?php else: ?>
                                                    <span class="small" style="color:var(--ak-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo (int)$row['sort_order']; ?></td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Hiển thị' : 'Ẩn'; ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['clients'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa khách hàng này?');" style="margin:0;">
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
