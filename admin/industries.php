<?php
// Admin - Industries (Marketing theo ngành) Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];
$csrfToken = csrf_token();

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
            throw new RuntimeException('CSRF token không hợp lệ');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare('DELETE FROM industries WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['industries'], ['msg' => 'Đã xóa ngành']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $content = sanitize_html(trim($_POST['content'] ?? ''));
            $image = trim($_POST['image'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($name === '') {
                throw new RuntimeException('Tên ngành không được để trống');
            }
            if ($slug === '') {
                $slug = make_slug($name);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM industries WHERE slug = :slug AND id <> :id');
            $slugCheck->execute(['slug' => $slug, 'id' => $id]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug đã tồn tại');
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE industries SET name = :name, slug = :slug, description = :description, content = :content, image = :image, sort_order = :sort_order WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'content' => $content,
                    'image' => $image,
                    'sort_order' => $sortOrder,
                ]);
                header('Location: ' . with_query($adminRoutes['industries'], ['msg' => 'Đã cập nhật ngành']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO industries (name, slug, description, content, image, sort_order) VALUES (:name, :slug, :description, :content, :image, :sort_order)');
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'content' => $content,
                'image' => $image,
                'sort_order' => $sortOrder,
            ]);
            header('Location: ' . with_query($adminRoutes['industries'], ['msg' => 'Đã thêm ngành']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0,
    'name' => '',
    'slug' => '',
    'description' => '',
    'content' => '',
    'image' => '',
    'sort_order' => 0,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT * FROM industries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$rows = [];
if ($db) {
    $rows = $db->query('SELECT id, name, slug, description, image, sort_order FROM industries ORDER BY sort_order ASC, id ASC')->fetchAll();
}
?>
<?php admin_header('Marketing theo ngành', 'Quản lý kiến thức marketing theo ngành', $admin, 'industries'); ?>

            <section style="margin-top:22px">
                <?php if ($flash !== ''): ?>
                    <div class="card" style="margin-bottom:16px;background:rgba(146,221,214,0.12);padding:12px 16px;color:#92ddd6;">
                        <?php echo h($flash); ?>
                    </div>
                <?php endif; ?>

                <?php if ($dbError !== ''): ?>
                    <div class="card" style="margin-bottom:16px;background:rgba(255,120,120,0.12);padding:12px 16px;color:#ffb0b0;">
                        Lỗi: <?php echo h($dbError); ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="padding:16px;margin-bottom:18px;">
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa ngành' : 'Thêm ngành mới'; ?></h3>
                    <form method="post" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

                        <div>
                            <label class="small">Tên ngành</label>
                            <input class="form-control" type="text" name="name" required value="<?php echo h($editing['name']); ?>">
                        </div>
                        <div>
                            <label class="small">Slug</label>
                            <input class="form-control" type="text" name="slug" value="<?php echo h($editing['slug']); ?>">
                        </div>
                        <div>
                            <label class="small">Thứ tự</label>
                            <input class="form-control" type="number" name="sort_order" value="<?php echo (int)($editing['sort_order'] ?? 0); ?>">
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Mô tả ngắn</label>
                            <input class="form-control" type="text" name="description" value="<?php echo h($editing['description']); ?>">
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung bài viết</label>
                            <textarea class="form-control" name="content" rows="10" placeholder="Nhập nội dung chi tiết về marketing cho ngành này..."><?php echo h($editing['content']); ?></textarea>
                        </div>

                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['industries']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách ngành</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên ngành</th>
                                    <th>Slug</th>
                                    <th>Mô tả</th>
                                    <th>Thứ tự</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#IND-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['name']); ?></td>
                                            <td><?php echo h($row['slug']); ?></td>
                                            <td style="max-width:300px;"><?php echo h(mb_strimwidth($row['description'] ?? '', 0, 80, '…')); ?></td>
                                            <td><?php echo (int)($row['sort_order'] ?? 0); ?></td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['industries'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa ngành này?');" style="margin:0;">
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
