<?php
// Admin - Users Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init(['require_admin' => true]);
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
            throw new RuntimeException('CSRF token khong hop le');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['users'], ['msg' => 'Đã xóa người dùng']));
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
                throw new RuntimeException('Username và email không được để trống');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email không đúng định dạng');
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
                throw new RuntimeException('Username đã tồn tại');
            }

            $emailCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
            $emailCheck->execute([
                'email' => $email,
                'id' => $id,
            ]);
            if ((int)$emailCheck->fetchColumn() > 0) {
                throw new RuntimeException('Email đã tồn tại');
            }

            if ($id > 0) {
                if ($password !== '') {
                    if (mb_strlen($password) < 8) {
                        throw new RuntimeException('Mật khẩu phải có ít nhất 8 ký tự');
                    }
                    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                        throw new RuntimeException('Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (vd: !@#$%...)');
                    }
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
            if (mb_strlen($password) < 8) {
                throw new RuntimeException('Mật khẩu phải có ít nhất 8 ký tự');
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                throw new RuntimeException('Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (vd: !@#$%...)');
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

admin_header('Người dùng', 'Quản lý tài khoản người dùng', $admin, 'users');
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
                <input class="form-control" type="password" name="password" <?php echo (int)$editing['id'] > 0 ? '' : 'required'; ?> minlength="8" placeholder="Tối thiểu 8 ký tự, có ký tự đặc biệt">
                <div class="small" style="margin-top:4px;color:var(--ak-muted);">Tối thiểu 8 ký tự, phải có ít nhất 1 ký tự đặc biệt (!@#$%...)</div>
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

<?php admin_footer(); ?>
