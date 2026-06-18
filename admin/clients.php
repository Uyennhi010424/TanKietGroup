<?php
// Admin - Clients Management (Khách hàng tiêu biểu)
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];

$db = null;
$dbError = '';
$flash = $_GET['msg'] ?? '';
$csrfToken = csrf_token();
$mediaRoute = '/media.php?path=';

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

            $uploaded = store_uploaded_image('logo_file', 'uploads/clients');
            if ($uploaded !== null) {
                $logo = $uploaded;
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE clients SET name = :name, logo = :logo, website_url = :website_url, sort_order = :sort_order, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id, 'name' => $name, 'logo' => $logo,
                    'website_url' => $websiteUrl, 'sort_order' => $sortOrder, 'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['clients'], ['msg' => 'Đã cập nhật khách hàng']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO clients (name, logo, website_url, sort_order, status) VALUES (:name, :logo, :website_url, :sort_order, :status)');
            $stmt->execute([
                'name' => $name, 'logo' => $logo, 'website_url' => $websiteUrl,
                'sort_order' => $sortOrder, 'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['clients'], ['msg' => 'Đã thêm khách hàng']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0, 'name' => '', 'logo' => '', 'website_url' => '',
    'sort_order' => 0, 'status' => 1,
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

admin_header('Khách hàng', 'Quản lý logo và thông tin khách hàng hợp tác', $admin, 'clients');
?>

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

<?php admin_footer(); ?>
