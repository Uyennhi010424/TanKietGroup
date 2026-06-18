<?php
// Admin - Projects Management
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
                $stmt = $db->prepare('DELETE FROM projects WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['projects'], ['msg' => 'Đã xóa dự án']));
            exit;
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $clientName = trim($_POST['client_name'] ?? '');
            $industryId = null;
            $industryName = trim($_POST['industry_name'] ?? '');
            if ($industryName !== '') {
                $industryStmt = $db->prepare('SELECT id FROM industries WHERE name = :name LIMIT 1');
                $industryStmt->execute(['name' => $industryName]);
                $existing = $industryStmt->fetch();
                if ($existing) {
                    $industryId = (int)$existing['id'];
                } else {
                    $newSlug = make_slug($industryName);
                    $insStmt = $db->prepare('INSERT INTO industries (name, slug) VALUES (:name, :slug)');
                    $insStmt->execute(['name' => $industryName, 'slug' => $newSlug]);
                    $industryId = (int)$db->lastInsertId();
                }
            }
            $serviceId = ($_POST['service_id'] ?? '') === '' ? null : (int)$_POST['service_id'];
            $shortDesc = trim($_POST['short_desc'] ?? '');
            $content = sanitize_html(trim($_POST['content'] ?? ''));
            $resultMetrics = trim($_POST['result_metrics'] ?? '');
            $thumbnail = trim($_POST['current_thumbnail'] ?? '');
            $status = (int)($_POST['status'] ?? 1);

            if ($title === '') {
                throw new RuntimeException('Tên dự án không được để trống');
            }
            if ($slug === '') {
                $slug = make_slug($title);
            }

            $slugCheck = $db->prepare('SELECT COUNT(*) FROM projects WHERE slug = :slug AND id <> :id');
            $slugCheck->execute([
                'slug' => $slug,
                'id' => $id,
            ]);
            if ((int)$slugCheck->fetchColumn() > 0) {
                throw new RuntimeException('Slug dự án đã tồn tại, vui lòng chọn slug khác');
            }

            $uploadedThumbnail = store_uploaded_image('thumbnail_file', 'uploads/projects');
            if ($uploadedThumbnail !== null) {
                $thumbnail = $uploadedThumbnail;
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE projects SET title = :title, slug = :slug, client_name = :client_name, industry_id = :industry_id, service_id = :service_id, short_desc = :short_desc, content = :content, result_metrics = :result_metrics, thumbnail = :thumbnail, status = :status WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'client_name' => $clientName,
                    'industry_id' => $industryId,
                    'service_id' => $serviceId,
                    'short_desc' => $shortDesc,
                    'content' => $content,
                    'result_metrics' => $resultMetrics,
                    'thumbnail' => $thumbnail,
                    'status' => $status,
                ]);
                header('Location: ' . with_query($adminRoutes['projects'], ['msg' => 'Đã cập nhật dự án']));
                exit;
            }
            $stmt = $db->prepare('INSERT INTO projects (title, slug, client_name, industry_id, service_id, short_desc, content, result_metrics, thumbnail, status) VALUES (:title, :slug, :client_name, :industry_id, :service_id, :short_desc, :content, :result_metrics, :thumbnail, :status)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'client_name' => $clientName,
                'industry_id' => $industryId,
                'service_id' => $serviceId,
                'short_desc' => $shortDesc,
                'content' => $content,
                'result_metrics' => $resultMetrics,
                'thumbnail' => $thumbnail,
                'status' => $status,
            ]);
            header('Location: ' . with_query($adminRoutes['projects'], ['msg' => 'Đã thêm dự án']));
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
    'client_name' => '',
    'industry_id' => null,
    'industry_name' => '',
    'service_id' => null,
    'short_desc' => '',
    'content' => '',
    'result_metrics' => '',
    'thumbnail' => '',
    'status' => 1,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT p.id, p.title, p.slug, p.client_name, p.industry_id, p.service_id, p.short_desc, p.content, p.result_metrics, p.thumbnail, p.status, i.name AS industry_name FROM projects p LEFT JOIN industries i ON i.id = p.industry_id WHERE p.id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

$industries = [];
$services = [];
$rows = [];
if ($db) {
    $industries = $db->query('SELECT id, name FROM industries ORDER BY name ASC')->fetchAll();
    $services = $db->query('SELECT s.id, s.title, s.industry_id, i.name AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.title ASC')->fetchAll();
    $rows = $db->query('SELECT p.id, p.title, p.slug, p.client_name, p.thumbnail, p.status, COALESCE(i.name, "-") AS industry_name FROM projects p LEFT JOIN industries i ON i.id = p.industry_id ORDER BY p.id DESC')->fetchAll();
}

admin_header('Dự án', 'Quản lý các dự án', $admin, 'projects');
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
                    <h3 style="margin:0 0 12px 0"><?php echo (int)$editing['id'] > 0 ? 'Sửa dự án' : 'Thêm dự án'; ?></h3>
                    <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
                        <input type="hidden" name="current_thumbnail" value="<?php echo h($editing['thumbnail'] ?? ''); ?>">

                        <div>
                            <label class="small">Tên dự án</label>
                            <input class="form-control" type="text" name="title" required value="<?php echo h($editing['title']); ?>">
                        </div>
                        <div>
                            <label class="small">Slug</label>
                            <input class="form-control" type="text" name="slug" value="<?php echo h($editing['slug']); ?>">
                        </div>
                        <div>
                            <label class="small">Khách hàng</label>
                            <input class="form-control" type="text" name="client_name" value="<?php echo h($editing['client_name']); ?>">
                        </div>

                        <div>
                            <label class="small">Ngành</label>
                            <input class="form-control" type="text" name="industry_name" id="industryInput" list="industryList" value="<?php echo h($editing['industry_name'] ?? ''); ?>" placeholder="Chọn hoặc nhập ngành...">
                            <datalist id="industryList">
                                <?php foreach ($industries as $item): ?>
                                    <option value="<?php echo h($item['name']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="small">Dịch vụ liên quan</label>
                            <select class="form-control" name="service_id">
                                <option value="">-- Chọn dịch vụ --</option>
                                <?php foreach ($services as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>" data-industry="<?php echo h($item['industry_name'] ?? ''); ?>" <?php echo (int)$editing['service_id'] === (int)$item['id'] ? 'selected' : ''; ?>><?php echo h($item['title']); ?></option>
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

                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung dự án</label>
                            <textarea class="form-control" name="content" rows="6"><?php echo h($editing['content'] ?? ''); ?></textarea>
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Kết quả đạt được (result_metrics)</label>
                            <textarea class="form-control" name="result_metrics" rows="4" placeholder='Bạn có thể nhập JSON hoặc mô tả ngắn'><?php echo h($editing['result_metrics'] ?? ''); ?></textarea>
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label class="small">Hình ảnh dự án</label>
                            <input class="form-control" type="file" name="thumbnail_file" accept="image/*">
                            <?php if (!empty($editing['thumbnail'])): ?>
                                <div class="small" style="margin-top:8px;">Ảnh hiện tại: <?php echo h($editing['thumbnail']); ?></div>
                                <img src="<?php echo h($mediaRoute . rawurlencode($editing['thumbnail'])); ?>" alt="Project thumbnail" style="margin-top:8px;max-height:80px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                            <?php endif; ?>
                        </div>

                        <div style="grid-column:1 / -1;display:flex;gap:10px;">
                            <button class="btn-admin" type="submit"><?php echo (int)$editing['id'] > 0 ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ((int)$editing['id'] > 0): ?>
                                <a class="btn-admin" href="<?php echo h($adminRoutes['projects']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Danh sách dự án</h3>
                    <div style="overflow:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên dự án</th>
                                    <th>Khách hàng</th>
                                    <th>Ngành</th>
                                    <th>Slug</th>
                                    <th>Ảnh</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu dự án</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#PRJ-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><?php echo h($row['client_name'] ?: '-'); ?></td>
                                            <td><span class="badge"><?php echo h($row['industry_name']); ?></span></td>
                                            <td><?php echo h($row['slug']); ?></td>
                                            <td>
                                                <?php if (!empty($row['thumbnail'])): ?>
                                                    <img src="<?php echo h($mediaRoute . rawurlencode($row['thumbnail'])); ?>" alt="Thumbnail" style="max-height:44px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                                <a class="btn-admin" style="text-decoration:none;" href="<?php echo h(with_query($adminRoutes['projects'], ['edit' => (int)$row['id']])); ?>">Sửa</a>
                                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa dự án này?');" style="margin:0;">
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

<script>
    (function() {
        function qs(sel) {
            return document.querySelector(sel)
        }
        var industryInput = qs('input[name="industry_name"]');
        var serviceSel = qs('select[name="service_id"]');
        if (!industryInput || !serviceSel) return;

        function filterServices() {
            var selectedIndustry = industryInput.value.trim();
            var currentService = serviceSel.value;
            Array.prototype.forEach.call(serviceSel.options, function(opt) {
                if (opt.value === '') {
                    opt.hidden = false;
                    return;
                }
                var optIndustry = opt.dataset.industry || '';
                if (selectedIndustry === '') {
                    opt.hidden = false;
                } else if (optIndustry === selectedIndustry) {
                    opt.hidden = false;
                } else if (opt.value === currentService) {
                    opt.hidden = false;
                } else {
                    opt.hidden = true;
                }
            });
        }

        industryInput.addEventListener('input', filterServices);
        filterServices();
    })();
</script>

<script>
    function slugify(text) {
        return text
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }

    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');

    let manualSlug = false;

    // nếu user tự sửa slug thì không auto nữa
    slugInput.addEventListener('input', () => {
        manualSlug = true;
    });

    // auto tạo slug từ title
    titleInput.addEventListener('input', () => {
        if (!manualSlug) {
            slugInput.value = slugify(titleInput.value);
        }
    });
</script>
