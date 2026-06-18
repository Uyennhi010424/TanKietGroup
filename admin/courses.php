<?php
// Admin - Courses Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];
$csrfToken = csrf_token();
$mediaRoute = '/media.php?path=';

$db = null;
$dbError = '';
$flash = $_GET['msg'] ?? '';

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

admin_header('Khóa học', 'Quản lý các khóa học', $admin, 'courses');
?>

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
                    <img src="<?php echo h($mediaRoute . rawurlencode($editing['thumbnail'])); ?>" alt="Course thumbnail" style="margin-top:8px;max-height:80px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
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
                                        <img src="<?php echo h($mediaRoute . rawurlencode($row['thumbnail'])); ?>" alt="Thumbnail" style="max-height:44px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
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

<?php admin_footer(); ?>
