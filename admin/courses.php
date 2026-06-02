<?php
// Admin - Courses Management
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
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd'
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'course';
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
                $stmt = $db->prepare('DELETE FROM courses WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['courses'], ['msg' => 'Đã xóa khóa học']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $shortDesc = trim($_POST['short_desc'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $status = (int)($_POST['status'] ?? 1);
            $thumbnail = trim($_POST['current_thumbnail'] ?? '');

            if ($title === '') {
                throw new RuntimeException('Tên khóa học không được để trống');
            }

            if ($slug === '') {
                $slug = make_slug($title);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM courses WHERE slug = :slug AND id <> :id');
            $slugCheck->execute([
                'slug' => $slug,
                'id' => $id,
            ]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug khóa học đã tồn tại, vui lòng chọn slug khác');
            }

            $uploadedThumbnail = store_uploaded_image('thumbnail_file', 'uploads/courses');
            if ($uploadedThumbnail !== null) {
                $thumbnail = $uploadedThumbnail;
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE courses SET title = :title, slug = :slug, short_desc = :short_desc, duration = :duration, price = :price, thumbnail = :thumbnail, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'short_desc' => $shortDesc,
                    'duration' => $duration,
                    'price' => $price,
                    'thumbnail' => $thumbnail,
                    'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['courses'], ['msg' => 'Đã cập nhật khóa học']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO courses (title, slug, short_desc, duration, price, thumbnail, status) VALUES (:title, :slug, :short_desc, :duration, :price, :thumbnail, :status)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'short_desc' => $shortDesc,
                'duration' => $duration,
                'price' => $price,
                'thumbnail' => $thumbnail,
                'status' => $status,
            ]);

            header('Location: ' . with_query($adminRoutes['courses'], ['msg' => 'Đã thêm khóa học']));
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
    'short_desc' => '',
    'duration' => '',
    'price' => '0',
    'thumbnail' => '',
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, title, slug, short_desc, duration, price, thumbnail, status FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$rows = [];
if ($db) {
    $stmt = $db->query('SELECT id, title, slug, duration, price, thumbnail, status FROM courses ORDER BY id DESC');
    $rows = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Khóa học - Trang quản trị</title>
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
                        <h1>Khóa học</h1>
                        <div class="small">Quản lý các khóa học của công ty</div>
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
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa khóa học' : 'Thêm khóa học'; ?></h3>
                    <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
                        <input type="hidden" name="current_thumbnail" value="<?php echo h($editing['thumbnail'] ?? ''); ?>">

                        <div>
                            <label class="small">Tên khóa học</label>
                            <input class="form-control" type="text" name="title" required value="<?php echo h($editing['title']); ?>">
                        </div>
                        <div>
                            <label class="small">Slug</label>
                            <input class="form-control" type="text" name="slug" value="<?php echo h($editing['slug']); ?>">
                        </div>
                        <div>
                            <label class="small">Thời lượng</label>
                            <input class="form-control" type="text" name="duration" value="<?php echo h($editing['duration']); ?>">
                        </div>
                        <div>
                            <label class="small">Giá</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="price" value="<?php echo h($editing['price']); ?>">
                        </div>
                        <div>
                            <label class="small">Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="1" <?php echo (int)$editing['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo (int)$editing['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung khóa học</label>
                            <textarea class="form-control" name="short_desc" rows="3"><?php echo h($editing['short_desc']); ?></textarea>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="small">Hình ảnh khóa học</label>
                            <input class="form-control" type="file" name="thumbnail_file" accept="image/*">
                            <?php if (!empty($editing['thumbnail'])): ?>
                                <div class="small" style="margin-top:8px;">Ảnh hiện tại: <?php echo h($editing['thumbnail']); ?></div>
                                <img src="/<?php echo h($editing['thumbnail']); ?>" alt="Course thumbnail" style="margin-top:8px;max-height:80px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                            <?php endif; ?>
                        </div>
                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['courses']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách khóa học</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên khóa học</th>
                                    <th>Slug</th>
                                    <th>Thời lượng</th>
                                    <th>Giá</th>
                                    <th>Ảnh</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu khóa học</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#COURSE-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><?php echo h($row['slug']); ?></td>
                                            <td><span class="badge"><?php echo h($row['duration'] ?: 'Chưa cập nhật'); ?></span></td>
                                            <td><?php echo h(format_vnd($row['price'] ?? 0)); ?></td>
                                            <td>
                                                <?php if (!empty($row['thumbnail'])): ?>
                                                    <img src="/<?php echo h($row['thumbnail']); ?>" alt="Thumbnail" style="max-height:44px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['courses'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa khóa học này?');" style="margin:0;">
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
