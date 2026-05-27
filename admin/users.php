<?php
// Admin - Users Management
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
                $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['users'], ['msg' => 'Da xoa nguoi dung']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = trim($_POST['role'] ?? 'user');
            $status = (int)($_POST['status'] ?? 1);
            $password = trim($_POST['password'] ?? '');

            if ($username === '' || $email === '') {
                throw new RuntimeException('Username va email khong duoc de trong');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email khong dung dinh dang');
            }

            if (!in_array($role, ['admin', 'editor', 'user'], true)) {
                $role = 'user';
            }

            $userNameCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND id <> :id');
            $userNameCheck->execute([
                'username' => $username,
                'id' => $id,
            ]);
            if ((int)$userNameCheck->fetchColumn() > 0) {
                throw new RuntimeException('Username da ton tai');
            }

            $emailCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
            $emailCheck->execute([
                'email' => $email,
                'id' => $id,
            ]);
            if ((int)$emailCheck->fetchColumn() > 0) {
                throw new RuntimeException('Email da ton tai');
            }

            if ($id > 0) {
                if ($password !== '') {
                    $stmt = $db->prepare('UPDATE users SET username = :username, full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status, password = :password WHERE id = :id');
                    $stmt->execute([
                        'id' => $id,
                        'username' => $username,
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'role' => $role,
                        'status' => $status,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ]);
                } else {
                    $stmt = $db->prepare('UPDATE users SET username = :username, full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status WHERE id = :id');
                    $stmt->execute([
                        'id' => $id,
                        'username' => $username,
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'role' => $role,
                        'status' => $status,
                    ]);
                }

                header('Location: ' . with_query($adminRoutes['users'], ['msg' => 'Đã cập nhật người dùng']));
                exit;
            }

            if ($password === '') {
                throw new RuntimeException('Mật khẩu không được để trống khi tạo mới người dùng');
            }

            $stmt = $db->prepare('INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES (:username, :password, :full_name, :email, :phone, :role, :status)');
            $stmt->execute([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['users'], ['msg' => 'Đã thêm người dùng']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0,
    'username' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'user',
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, username, full_name, email, phone, role, status FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$rows = [];
if ($db) {
    $rows = $db->query('SELECT id, full_name, email, phone, role, status FROM users ORDER BY id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Người dùng - Trang quản trị</title>
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
                        <h1>Người dùng</h1>
                        <div class="small">Quản lý các người dùng hệ thống</div>
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
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa người dùng' : 'Thêm người dùng'; ?></h3>
                    <form method="post" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

                        <div>
                            <label class="small">Username</label>
                            <input class="form-control" type="text" name="username" required value="<?php echo h($editing['username']); ?>">
                        </div>
                        <div>
                            <label class="small">Họ tên</label>
                            <input class="form-control" type="text" name="full_name" value="<?php echo h($editing['full_name']); ?>">
                        </div>
                        <div>
                            <label class="small">Email</label>
                            <input class="form-control" type="email" name="email" required value="<?php echo h($editing['email']); ?>">
                        </div>

                        <div>
                            <label class="small">Điện thoại</label>
                            <input class="form-control" type="text" name="phone" value="<?php echo h($editing['phone']); ?>">
                        </div>
                        <div>
                            <label class="small">Vai trò</label>
                            <select class="form-control" name="role">
                                <option value="admin" <?php echo $editing['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="editor" <?php echo $editing['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                <option value="user" <?php echo $editing['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        <div>
                            <label class="small">Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="1" <?php echo (int)$editing['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo (int)$editing['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Mật khẩu <?php echo (int)$editing['id'] > 0 ? '(để trống nếu không đổi)' : ''; ?></label>
                            <input class="form-control" type="password" name="password" <?php echo (int)$editing['id'] > 0 ? '' : 'required'; ?>>
                        </div>

                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['users']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách người dùng</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Email</th>
                                    <th>Điện thoại</th>
                                    <th>Vai trò</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu người dùng</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#USR-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['full_name'] ?: '-'); ?></td>
                                            <td><?php echo h($row['email']); ?></td>
                                            <td><?php echo h($row['phone'] ?: '-'); ?></td>
                                            <td><span class="badge"><?php echo h($row['role']); ?></span></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['users'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');" style="margin:0;">
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
