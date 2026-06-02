<?php
// Admin - Recruitment Management
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
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'recruitment';
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
    $db->exec("CREATE TABLE IF NOT EXISTS recruitments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        location VARCHAR(150),
        salary VARCHAR(150),
        deadline DATE,
        description TEXT,
        status TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
                $stmt = $db->prepare('DELETE FROM recruitments WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['recruitments'], ['msg' => 'Đã xóa tin tuyển dụng']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $salary = trim($_POST['salary'] ?? '');
            $deadline = trim($_POST['deadline'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = (int)($_POST['status'] ?? 1);

            if ($title === '') {
                throw new RuntimeException('Tiêu đề tuyển dụng không được để trống');
            }
            if ($slug === '') {
                $slug = make_slug($title);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM recruitments WHERE slug = :slug AND id <> :id');
            $slugCheck->execute(['slug' => $slug, 'id' => $id]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug tin tuyển dụng đã tồn tại, vui lòng chọn slug khác');
            }

            $deadlineValue = $deadline !== '' ? $deadline : null;

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE recruitments SET title = :title, slug = :slug, location = :location, salary = :salary, deadline = :deadline, description = :description, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'location' => $location,
                    'salary' => $salary,
                    'deadline' => $deadlineValue,
                    'description' => $description,
                    'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['recruitments'], ['msg' => 'Đã cập nhật tin tuyển dụng']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO recruitments (title, slug, location, salary, deadline, description, status) VALUES (:title, :slug, :location, :salary, :deadline, :description, :status)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'location' => $location,
                'salary' => $salary,
                'deadline' => $deadlineValue,
                'description' => $description,
                'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['recruitments'], ['msg' => 'Đã đăng tin tuyển dụng mới']));
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
    'location' => '',
    'salary' => '',
    'deadline' => '',
    'description' => '',
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, title, slug, location, salary, deadline, description, status FROM recruitments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$rows = [];
if ($db) {
    $rows = $db->query('SELECT id, title, location, salary, deadline, status FROM recruitments ORDER BY id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tuyển dụng - Trang quản trị</title>
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
                    <li><a href="<?php echo $logoutRoute; ?>">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>

        <main class="admin-main">
            <header class="topbar">
                <div style="display:flex;gap:20px;align-items:center">
                    <div class="title">
                        <h1>Tuyển dụng</h1>
                        <div class="small">Đăng và quản lý tin tuyển dụng</div>
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
                        Loi DB: <?php echo h($dbError); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="padding:24px; margin-bottom:24px;">
    <h3 style="margin:0 0 20px 0; color:var(--primary-strong);">
        <?php echo (int)$editing['id'] > 0 ? 'Sửa tin tuyển dụng' : 'Đăng tin tuyển dụng mới'; ?>
    </h3>

    <form method="post" class="recruitment-form">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

        <!-- Phần 1: Thông tin cơ bản -->
        <div class="form-section">
            <h4>Thông tin cơ bản</h4>
            <div class="form-grid">
                <div>
                    <label>Tiêu đề tuyển dụng <span class="required">*</span></label>
                    <input class="form-control" type="text" name="title" required value="<?php echo h($editing['title']); ?>">
                </div>
                <div>
                    <label>Slug</label>
                    <input class="form-control" type="text" name="slug" value="<?php echo h($editing['slug']); ?>">
                </div>
            </div>
        </div>

        <!-- Phần 2: Điều kiện & Lương -->
        <div class="form-section">
            <h4>Điều kiện & Mức lương</h4>
            <div class="form-grid">
                <div>
                    <label>Địa điểm</label>
                    <input class="form-control" type="text" name="location" value="<?php echo h($editing['location']); ?>">
                </div>
                <div>
                    <label>Mức lương</label>
                    <input class="form-control" type="text" name="salary" value="<?php echo h($editing['salary']); ?>" placeholder="Ví dụ: 15-25 triệu">
                </div>
                <div>
                    <label>Hạn nộp</label>
                    <input class="form-control" type="date" name="deadline" value="<?php echo h((string)$editing['deadline']); ?>">
                </div>
            </div>
        </div>

        <!-- Phần 3: Trạng thái & Mô tả -->
        <div class="form-section">
            <h4>Mô tả công việc</h4>
            <div style="margin-bottom:16px;">
                <label>Trạng thái</label>
                <select class="form-control" name="status" style="width:auto;">
                    <option value="1" <?php echo (int)$editing['status'] === 1 ? 'selected' : ''; ?>>Đang tuyển</option>
                    <option value="0" <?php echo (int)$editing['status'] === 0 ? 'selected' : ''; ?>>Đã đóng</option>
                </select>
            </div>
            <textarea class="form-control" name="description" rows="12" placeholder="Mô tả chi tiết công việc..."><?php echo h($editing['description']); ?></textarea>
        </div>

        <div style="margin-top:24px; display:flex; gap:12px;">
            <button class="btn-admin btn-primary" type="submit" style="padding:12px 28px;">
                <?php echo (int)$editing['id'] > 0 ? 'Cập nhật tin' : 'Đăng tin tuyển dụng'; ?>
            </button>
            <?php if ((int)$editing['id'] > 0): ?>
                <a class="btn-admin" href="<?php echo h($adminRoutes['recruitments']); ?>" style="padding:12px 28px;">Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách tuyển dụng</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Địa điểm</th>
                                    <th>Lương</th>
                                    <th>Hạn nộp</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--ak-muted);">Chưa có tin tuyển dụng</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#REC-<?php echo str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><?php echo h($row['location'] ?: '-'); ?></td>
                                            <td><?php echo h($row['salary'] ?: '-'); ?></td>
                                            <td><?php echo h($row['deadline'] ?: '-'); ?></td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Đang tuyển' : 'Đóng'; ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['recruitments'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa tin tuyển dụng này?');" style="margin:0;">
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