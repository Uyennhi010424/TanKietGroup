<?php
// Admin - Recruitment Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];

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
            throw new RuntimeException('CSRF token không hợp lệ');
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
                    'id' => $id, 'title' => $title, 'slug' => $slug,
                    'location' => $location, 'salary' => $salary,
                    'deadline' => $deadlineValue, 'description' => $description, 'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['recruitments'], ['msg' => 'Đã cập nhật tin tuyển dụng']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO recruitments (title, slug, location, salary, deadline, description, status) VALUES (:title, :slug, :location, :salary, :deadline, :description, :status)');
            $stmt->execute([
                'title' => $title, 'slug' => $slug, 'location' => $location,
                'salary' => $salary, 'deadline' => $deadlineValue,
                'description' => $description, 'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['recruitments'], ['msg' => 'Đã đăng tin tuyển dụng mới']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

$editing = [
    'id' => 0, 'title' => '', 'slug' => '', 'location' => '',
    'salary' => '', 'deadline' => '', 'description' => '', 'status' => 1,
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

admin_header('Tuyển dụng', 'Đăng và quản lý tin tuyển dụng', $admin, 'recruitments');
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

                <div class="card" style="padding:24px; margin-bottom:24px;">
    <h3 style="margin:0 0 20px 0; color:var(--primary-strong);">
        <?php echo (int)$editing['id'] > 0 ? 'Sửa tin tuyển dụng' : 'Đăng tin tuyển dụng mới'; ?>
    </h3>

    <form method="post" class="recruitment-form">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">

        <div class="form-section">
            <h4>Thông tin cơ bản</h4>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label>Tiêu đề tuyển dụng <span class="required">*</span></label>
                    <input class="form-control" type="text" name="title" id="recruitTitle" required value="<?php echo h($editing['title']); ?>" placeholder="VD: Chuyên viên Marketing">
                </div>
                <div>
                    <label>Slug</label>
                    <input class="form-control" type="text" name="slug" id="recruitSlug" value="<?php echo h($editing['slug']); ?>" placeholder="tự động tạo từ tiêu đề">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>Điều kiện & Mức lương</h4>
            <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
                <div>
                    <label>Địa điểm</label>
                    <input class="form-control" type="text" name="location" value="<?php echo h($editing['location']); ?>" placeholder="VD: TP. Hồ Chí Minh">
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

        <div class="form-section">
            <h4>Mô tả công việc</h4>
            <div style="margin-bottom:16px;">
                <label>Trạng thái</label>
                <select class="form-control" name="status" style="width:auto;">
                    <option value="1" <?php echo (int)$editing['status'] === 1 ? 'selected' : ''; ?>>Đang tuyển</option>
                    <option value="0" <?php echo (int)$editing['status'] === 0 ? 'selected' : ''; ?>>Đã đóng</option>
                </select>
            </div>
            <!-- Rich Text Editor Toolbar -->
            <div class="rte-toolbar">
                <button type="button" class="rte-btn" data-cmd="bold" title="In đậm (Ctrl+B)" style="font-weight:700;">B</button>
                <button type="button" class="rte-btn" data-cmd="italic" title="In nghiêng (Ctrl+I)" style="font-style:italic;">I</button>
                <button type="button" class="rte-btn" data-cmd="underline" title="Gạch chân (Ctrl+U)" style="text-decoration:underline;">U</button>
                <span class="rte-divider"></span>
                <button type="button" class="rte-btn" data-cmd="insertUnorderedList" title="Danh sách">• Liệt kê</button>
                <button type="button" class="rte-btn" data-cmd="insertOrderedList" title="Đánh số">1. Danh sách</button>
                <span class="rte-divider"></span>
                <select class="rte-fontsize" title="Cỡ chữ">
                    <option value="">Cỡ chữ</option>
                    <option value="1">Nhỏ</option>
                    <option value="3">Vừa</option>
                    <option value="5">Lớn</option>
                    <option value="7">Rất lớn</option>
                </select>
            </div>
            <!-- Contenteditable Editor -->
            <div class="rte-editor form-control" contenteditable="true" id="rteEditor"><?php echo $editing['description'] !== '' ? $editing['description'] : '<p>Mô tả chi tiết công việc...</p>'; ?></div>
            <!-- Hidden textarea to hold HTML for form submission -->
            <textarea name="description" id="rteHidden" style="display:none;"><?php echo h($editing['description']); ?></textarea>
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

<script>
// Rich Text Editor
(function() {
    var editor = document.getElementById('rteEditor');
    var hidden = document.getElementById('rteHidden');
    if (!editor || !hidden) return;

    // Sync editor content to hidden textarea on form submit
    var form = editor.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            hidden.value = editor.innerHTML;
        });
    }

    // Toolbar button clicks
    document.querySelectorAll('.rte-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var cmd = btn.getAttribute('data-cmd');
            document.execCommand(cmd, false, null);
            editor.focus();
        });
    });

    // Font size select
    var fontSelect = document.querySelector('.rte-fontsize');
    if (fontSelect) {
        fontSelect.addEventListener('change', function() {
            if (fontSelect.value) {
                document.execCommand('fontSize', false, fontSelect.value);
                editor.focus();
            }
            fontSelect.value = '';
        });
    }
})();

// Auto-slug from title
(function() {
    var titleInput = document.getElementById('recruitTitle');
    var slugInput = document.getElementById('recruitSlug');
    if (!titleInput || !slugInput) return;

    var vietnameseMap = {
        'à':'a','á':'a','ạ':'a','ả':'a','ã':'a',
        'â':'a','ầ':'a','ấ':'a','ậ':'a','ẩ':'a','ẫ':'a',
        'ă':'a','ằ':'a','ắ':'a','ặ':'a','ẳ':'a','ẵ':'a',
        'è':'e','é':'e','ẹ':'e','ẻ':'e','ẽ':'e',
        'ê':'e','ề':'e','ế':'e','ệ':'e','ể':'e','ễ':'e',
        'ì':'i','í':'i','ị':'i','ỉ':'i','ĩ':'i',
        'ò':'o','ó':'o','ọ':'o','ỏ':'o','õ':'o',
        'ô':'o','ồ':'o','ố':'o','ộ':'o','ổ':'o','ỗ':'o',
        'ơ':'o','ờ':'o','ớ':'o','ợ':'o','ở':'o','ỡ':'o',
        'ù':'u','ú':'u','ụ':'u','ủ':'u','ũ':'u',
        'ư':'u','ừ':'u','ứ':'u','ự':'u','ử':'u','ữ':'u',
        'ỳ':'y','ý':'y','ỵ':'y','ỷ':'y','ỹ':'y',
        'đ':'d'
    };

    function makeSlug(text) {
        text = text.toLowerCase();
        var result = '';
        for (var i = 0; i < text.length; i++) {
            result += vietnameseMap[text[i]] || text[i];
        }
        result = result.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return result;
    }

    // Track if user manually edited slug
    var userEditedSlug = slugInput.value.trim() !== '';

    titleInput.addEventListener('input', function() {
        if (!userEditedSlug) {
            var slug = makeSlug(titleInput.value);
            slugInput.value = slug;
        }
    });

    slugInput.addEventListener('input', function() {
        userEditedSlug = true;
    });

    // Auto-generate on load if title exists but slug is empty
    if (titleInput.value && !slugInput.value) {
        slugInput.value = makeSlug(titleInput.value);
    }
})();

<?php admin_footer(); ?>
