<?php
// Admin - Services Management
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$isAdminRoot = substr($docRoot, -6) === '/admin';
$assetBase = $isAdminRoot ? '' : '/admin';
$adminRoutes = [
    'dashboard' => $isAdminRoot ? '/index.php' : '/?page=admin_index',
    'courses' => $isAdminRoot ? '/courses.php' : '/?page=admin_courses',
    'projects' => $isAdminRoot ? '/projects.php' : '/?page=admin_projects',
    'services' => $isAdminRoot ? '/services.php' : '/?page=admin_services',
    'users' => $isAdminRoot ? '/users.php' : '/?page=admin_users',
    'blog' => $isAdminRoot ? '/blog.php' : '/?page=admin_blog',
    'recruitments' => $isAdminRoot ? '/recruitments.php' : '/?page=admin_recruitments',
    'stats' => $isAdminRoot ? '/stats.php' : '/?page=admin_stats',
    'settings' => $isAdminRoot ? '/settings.php' : '/?page=admin_settings',
    'consultations' => $isAdminRoot ? '/consultations.php' : '/?page=admin_consultations',
];

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

$loginRoute = $isAdminRoot ? '/login.php' : '/?page=admin_login';
$logoutRoute = $isAdminRoot ? '/login.php?logout=1' : '/?page=admin_login&logout=1';
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
    return $text !== '' ? $text : 'service';
}

function with_query($route, $params)
{
    $sep = strpos($route, '?') !== false ? '&' : '?';
    return $route . $sep . http_build_query($params);
}

$servicePresets = [
    'Marketing trọn gói (Chiến lược xây kênh)',
    'Chăm sóc fanpage (Đăng bài, Quản lý trang, Viết content)',
    'Sản xuất video',
    'Tổ chức sự kiện',
    'Thiết kế Website chuẩn SEO',
];

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
                $stmt = $db->prepare('DELETE FROM services WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['services'], ['msg' => 'Đã xóa dịch vụ']));
            exit;
        }

            if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $industryId = ($_POST['industry_id'] ?? '') === '' ? null : (int)$_POST['industry_id'];
            $shortDesc = trim($_POST['short_desc'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $image = trim($_POST['current_image'] ?? '');
            $status = (int)($_POST['status'] ?? 1);

            if ($title === '') {
                throw new RuntimeException('Tên dịch vụ không được để trống');
            }
            if ($slug === '') {
                $slug = make_slug($title);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM services WHERE slug = :slug AND id <> :id');
            $slugCheck->execute([
                'slug' => $slug,
                'id' => $id,
            ]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug dịch vụ đã tồn tại, vui lòng chọn slug khác');
            }

            // handle uploaded image
            $uploaded = store_uploaded_image('image_file', 'uploads/services');
            if ($uploaded !== null) {
                $image = $uploaded;
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE services SET title = :title, slug = :slug, industry_id = :industry_id, short_desc = :short_desc, content = :content, image = :image, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'industry_id' => $industryId,
                    'short_desc' => $shortDesc,
                    'content' => $content,
                    'image' => $image,
                    'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['services'], ['msg' => 'Đã cập nhật dịch vụ']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO services (title, slug, industry_id, short_desc, content, image, status) VALUES (:title, :slug, :industry_id, :short_desc, :content, :image, :status)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'industry_id' => $industryId,
                'short_desc' => $shortDesc,
                'content' => $content,
                'image' => $image,
                'status' => $status,
            ]);

            header('Location: ' . with_query($adminRoutes['services'], ['msg' => 'Đã thêm dịch vụ']));
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
    'industry_id' => null,
    'short_desc' => '',
    'content' => '',
    'image' => '',
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT id, title, slug, industry_id, short_desc, content, image, status FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$industries = [];
$rows = [];
if ($db) {
    $industries = $db->query('SELECT id, name FROM industries ORDER BY name ASC')->fetchAll();
    $rows = $db->query('SELECT s.id, s.title, s.slug, s.status, COALESCE(i.name, "-") AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dịch vụ - Trang quản trị</title>
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/css/admin.css">
    <script defer src="<?php echo $assetBase; ?>/assets/js/admin.js"></script>
</head>

<body class="role-<?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?>">

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
                        <h1>Dịch vụ</h1>
                        <div class="small">Quản lý các dịch vụ của công ty</div>
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
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa dịch vụ' : 'Thêm dịch vụ'; ?></h3>
                        <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

                        <div>
                            <label class="small">Tên dịch vụ</label>
                            <input class="form-control" type="text" name="title" list="service-title-presets" id="serviceTitleInput" required value="<?php echo h($editing['title']); ?>" placeholder="Chọn hoặc nhập tên dịch vụ">
                            <datalist id="service-title-presets">
                                <?php foreach ($servicePresets as $preset): ?>
                                    <option value="<?php echo h($preset); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <div class="small" style="margin-top:6px;color:var(--ak-muted);">Chọn nhanh tên có sẵn hoặc nhập tên riêng, slug sẽ tự sinh.</div>
                        </div>
                        <div>
                            <label class="small">Slug</label>
                            <input class="form-control" type="text" name="slug" id="serviceSlugInput" value="<?php echo h($editing['slug']); ?>" placeholder="Tự tạo từ tên dịch vụ">
                        </div>
                        <div>
                            <label class="small">Ngành</label>
                            <select class="form-control" name="industry_id">
                                <option value="">-- Chọn ngành --</option>
                                <?php foreach ($industries as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>" <?php echo (int)$editing['industry_id'] === (int)$item['id'] ? 'selected' : ''; ?>><?php echo h($item['name']); ?></option>
                                <?php endforeach; ?>
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
                            <label class="small">Mô tả ngắn</label>
                            <textarea class="form-control" name="short_desc" rows="3"><?php echo h($editing['short_desc']); ?></textarea>
                        </div>
                        <div>
                            <label class="small">Ảnh dịch vụ</label>
                            <input class="form-control" type="file" name="image_file" accept="image/*">
                            <input type="hidden" name="current_image" value="<?php echo h($editing['image'] ?? ''); ?>">
                            <?php if (!empty($editing['image'])): ?>
                                <div class="small" style="margin-top:8px">Ảnh hiện tại: <?php echo h($editing['image']); ?></div>
                                <img src="<?php echo h('/?page=admin_media&path=' . rawurlencode($editing['image'])); ?>" alt="" style="max-height:80px;margin-top:8px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);">
                            <?php endif; ?>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung chi tiết</label>
                            <textarea class="form-control" name="content" rows="6"><?php echo h($editing['content']); ?></textarea>
                        </div>
                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['services']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách dịch vụ</h3>
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
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu dịch vụ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#SVC-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><span class="badge"><?php echo h($row['industry_name']); ?></span></td>
                                            <td><?php echo h($row['slug']); ?></td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['services'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này?');" style="margin:0;">
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
    <script>
    (function () {
        var titleInput = document.getElementById('serviceTitleInput');
        var slugInput = document.getElementById('serviceSlugInput');
        if (!titleInput || !slugInput) return;

        var manualSlugEdit = false;
        var lastTitle = titleInput.value || '';

        function slugify(text) {
            return String(text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'service';
        }

        slugInput.addEventListener('input', function () {
            manualSlugEdit = true;
        });

        titleInput.addEventListener('input', function () {
            var currentTitle = titleInput.value.trim();
            if (!manualSlugEdit || slugInput.value.trim() === '' || slugInput.value === slugify(lastTitle)) {
                slugInput.value = slugify(currentTitle);
            }
            lastTitle = currentTitle;
        });

        if (!slugInput.value.trim() && titleInput.value.trim()) {
            slugInput.value = slugify(titleInput.value);
        }
    })();
    </script>
</body>

</html>
