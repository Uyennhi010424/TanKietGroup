<?php
// Admin - Services Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];
$csrfToken = csrf_token();
$mediaRoute = '/media.php?path=';

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

try {
    $db = get_db_connection();
    // Auto-migration: add service_type column if missing
    $col = $db->query("SHOW COLUMNS FROM services LIKE 'service_type'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE services ADD COLUMN service_type VARCHAR(100) AFTER industry_id");
    }
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
            $industryId = null;
            $industryName = trim($_POST['industry_name'] ?? '');

            if ($industryName !== '') {
                // Find existing industry by exact name
                $industryStmt = $db->prepare('SELECT id, name FROM industries WHERE name = :name LIMIT 1');
                $industryStmt->execute(['name' => $industryName]);
                $existing = $industryStmt->fetch();

                if ($existing) {
                    $industryId = (int)$existing['id'];
                } else {
                    // Create new industry
                    $newSlug = make_slug($industryName);
                    $insStmt = $db->prepare('INSERT INTO industries (name, slug) VALUES (:name, :slug)');
                    $insStmt->execute(['name' => $industryName, 'slug' => $newSlug]);
                    $industryId = (int)$db->lastInsertId();
                }

                if (preg_match('/cho\s+(.+)$/iu', $industryName, $matches)) {
                    $industryName = trim($matches[1]);
                }
            }
            $serviceType = trim($_POST['service_type'] ?? '');
            $shortDesc = trim($_POST['short_desc'] ?? '');
            $content = sanitize_html(trim($_POST['content'] ?? ''));
            $image = trim($_POST['current_image'] ?? '');
            $status = (int)($_POST['status'] ?? 1);
            $isFeatured = (int)($_POST['is_featured'] ?? 0);

            if ($title === '') {
                throw new RuntimeException('Tên dịch vụ không được để trống');
            }
            if ($slug === '') {

                $slugSource = $title;
                if ($industryName !== '') {
                    $slugSource .= ' ' . $industryName;
                }

                $slug = make_slug($slugSource);
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

            // Save packages
            $packageNames = $_POST['pkg_name'] ?? [];
            $packagePrices = $_POST['pkg_price'] ?? [];
            $packageUnits = $_POST['pkg_unit'] ?? [];
            $packageFeatures = $_POST['pkg_features'] ?? [];
            $packageHighlight = $_POST['pkg_highlight'] ?? [];
            ensure_service_packages_table($db);

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE services SET title = :title, slug = :slug, industry_id = :industry_id, service_type = :service_type, short_desc = :short_desc, content = :content, image = :image, status = :status, is_featured = :is_featured WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'industry_id' => $industryId,
                    'service_type' => $serviceType !== '' ? $serviceType : null,
                    'short_desc' => $shortDesc,
                    'content' => $content,
                    'image' => $image,
                    'status' => $status,
                    'is_featured' => $isFeatured,
                ]);

                // Delete old packages and re-insert
                $db->prepare('DELETE FROM service_packages WHERE service_id = :sid')->execute(['sid' => $id]);
                $insPkg = $db->prepare('INSERT INTO service_packages (service_id, name, price, price_unit, features, is_highlighted, sort_order) VALUES (:sid, :name, :price, :unit, :features, :hl, :ord)');
                for ($i = 0; $i < count($packageNames); $i++) {
                    $pkgName = trim((string)($packageNames[$i] ?? ''));
                    if ($pkgName === '') continue;
                    $insPkg->execute([
                        'sid' => $id,
                        'name' => $pkgName,
                        'price' => trim((string)($packagePrices[$i] ?? '')),
                        'unit' => trim((string)($packageUnits[$i] ?? '')),
                        'features' => trim((string)($packageFeatures[$i] ?? '')),
                        'hl' => in_array($i, $packageHighlight, true) ? 1 : 0,
                        'ord' => $i,
                    ]);
                }

                header('Location: ' . with_query($adminRoutes['services'], ['msg' => 'Đã cập nhật dịch vụ']));
                exit;
            }

            $stmt = $db->prepare('INSERT INTO services (title, slug, industry_id, service_type, short_desc, content, image, status, is_featured) VALUES (:title, :slug, :industry_id, :service_type, :short_desc, :content, :image, :status, :is_featured)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'industry_id' => $industryId,
                'service_type' => $serviceType !== '' ? $serviceType : null,
                'short_desc' => $shortDesc,
                'content' => $content,
                'image' => $image,
                'status' => $status,
                'is_featured' => $isFeatured,
            ]);

            $newId = (int)$db->lastInsertId();

            // Save packages for new service
            if ($newId > 0) {
                $insPkg = $db->prepare('INSERT INTO service_packages (service_id, name, price, price_unit, features, is_highlighted, sort_order) VALUES (:sid, :name, :price, :unit, :features, :hl, :ord)');
                for ($i = 0; $i < count($packageNames); $i++) {
                    $pkgName = trim((string)($packageNames[$i] ?? ''));
                    if ($pkgName === '') continue;
                    $insPkg->execute([
                        'sid' => $newId,
                        'name' => $pkgName,
                        'price' => trim((string)($packagePrices[$i] ?? '')),
                        'unit' => trim((string)($packageUnits[$i] ?? '')),
                        'features' => trim((string)($packageFeatures[$i] ?? '')),
                        'hl' => in_array($i, $packageHighlight, true) ? 1 : 0,
                        'ord' => $i,
                    ]);
                }
            }

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
    'industry_name' => '',
    'service_type' => '',
    'short_desc' => '',
    'content' => '',
    'image' => '',
    'status' => 1,
    'is_featured' => 0,
];

if ($db && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $db->prepare('SELECT s.id, s.title, s.slug, s.industry_id, s.service_type, s.short_desc, s.content, s.image, s.status, s.is_featured, i.name AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id WHERE s.id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $editing = $row;
        }
    }
}

// Load packages for editing
$packages = [];
if ($db && (int)$editing['id'] > 0) {
    try {
        ensure_service_packages_table($db);
        $packages = $db->prepare('SELECT * FROM service_packages WHERE service_id = :sid ORDER BY sort_order ASC, id ASC');
        $packages->execute(['sid' => (int)$editing['id']]);
        $packages = $packages->fetchAll();
    } catch (Throwable $e) {
        $packages = [];
    }
}

$industries = [];
$rows = [];
if ($db) {
    $industries = $db->query('SELECT id, name FROM industries ORDER BY name ASC')->fetchAll();
    $rows = $db->query('SELECT s.id, s.title, s.slug, s.status, s.is_featured, COALESCE(i.name, "-") AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.is_featured DESC, s.id DESC')->fetchAll();
}

admin_header('Dịch vụ', 'Quản lý các dịch vụ', $admin, 'services');
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
                            <input class="form-control" type="text" name="industry_name" id="industrySelect" list="industryList" value="<?php echo h($editing['industry_name'] ?? ''); ?>" placeholder="Chọn hoặc nhập ngành...">
                            <datalist id="industryList">
                                <?php foreach ($industries as $item): ?>
                                    <option value="<?php echo h($item['name']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="small">Loại dịch vụ</label>
                            <select class="form-control" name="service_type">
                                <option value="">-- Không phân loại --</option>
                                <?php
                                $serviceTypeLabels = [
                                    'marketing-tron-goi'  => 'Marketing trọn gói (Chiến lược xây kênh)',
                                    'cham-soc-fanpage'    => 'Chăm sóc Fanpage',
                                    'san-xuat-video'      => 'Sản xuất Video',
                                    'to-chuc-su-kien'     => 'Tổ chức sự kiện',
                                    'thiet-ke-website'    => 'Thiết kế Website chuẩn SEO',
                                ];
                                foreach ($serviceTypeLabels as $typeSlug => $typeName): ?>
                                    <option value="<?php echo h($typeSlug); ?>" <?php echo (($editing['service_type'] ?? '') === $typeSlug) ? 'selected' : ''; ?>><?php echo h($typeName); ?></option>
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
                        <div>
                            <label class="small">Tiêu biểu</label>
                            <select class="form-control" name="is_featured">
                                <option value="0" <?php echo (int)($editing['is_featured'] ?? 0) === 0 ? 'selected' : ''; ?>>Không</option>
                                <option value="1" <?php echo (int)($editing['is_featured'] ?? 0) === 1 ? 'selected' : ''; ?>>Tiêu biểu</option>
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
                                <img src="<?php echo h($mediaRoute . rawurlencode($editing['image'])); ?>" alt="" style="max-height:80px;margin-top:8px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);">
                            <?php endif; ?>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="small">Nội dung chi tiết</label>
                            <textarea class="form-control" name="content" rows="6"><?php echo h($editing['content']); ?></textarea>
                        </div>

                        <!-- Packages Section -->
                        <div style="grid-column:1 / -1;">
                            <label class="small" style="font-weight:700;font-size:0.95rem;">Gói giá dịch vụ</label>
                            <div class="small" style="color:var(--ak-muted);margin-bottom:10px;">Thêm các gói giá để hiển thị cho khách hàng</div>
                            <div id="packagesList" style="display:flex;flex-direction:column;gap:12px;">
                                <?php if (!empty($packages)): ?>
                                    <?php foreach ($packages as $i => $pkg): ?>
                                    <div class="pkg-row" style="display:grid;grid-template-columns:1fr 120px 80px 1fr auto;gap:8px;align-items:start;padding:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:8px;">
                                        <div>
                                            <label class="small">Tên gói</label>
                                            <input class="form-control" type="text" name="pkg_name[]" value="<?php echo h($pkg['name']); ?>" placeholder="VD: Gói Cơ bản">
                                        </div>
                                        <div>
                                            <label class="small">Giá</label>
                                            <input class="form-control" type="text" name="pkg_price[]" value="<?php echo h($pkg['price']); ?>" placeholder="2.000.000">
                                        </div>
                                        <div>
                                            <label class="small">Đơn vị</label>
                                            <input class="form-control" type="text" name="pkg_unit[]" value="<?php echo h($pkg['price_unit']); ?>" placeholder="/tháng">
                                        </div>
                                        <div>
                                            <label class="small">Tính năng (mỗi dòng 1 tính năng)</label>
                                            <textarea class="form-control" name="pkg_features[]" rows="3" placeholder="Quản lý fanpage&#10;Đăng 20 bài/tháng&#10;Thiết kế banner"><?php echo h($pkg['features']); ?></textarea>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:4px;padding-top:20px;">
                                            <label style="font-size:0.75rem;display:flex;align-items:center;gap:4px;cursor:pointer;">
                                                <input type="checkbox" name="pkg_highlight[]" value="<?php echo $i; ?>" <?php echo (int)$pkg['is_highlighted'] ? 'checked' : ''; ?>> Nổi bật
                                            </label>
                                            <button type="button" onclick="this.closest('.pkg-row').remove()" style="background:#ff8c8c;color:#3d1111;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:0.75rem;">Xóa</button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick="addPackage()" class="btn-admin" style="margin-top:10px;background:rgba(146,221,214,0.15);color:var(--ak-primary);">+ Thêm gói giá</button>
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
                                    <th>Tiêu biểu</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu dịch vụ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td>#SVC-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo h($row['title']); ?></td>
                                            <td><span class="badge"><?php echo h($row['industry_name']); ?></span></td>
                                            <td><?php echo h($row['slug']); ?></td>
                                            <td><?php echo (int)($row['is_featured'] ?? 0) === 1 ? 'Tiêu biểu' : '-'; ?></td>
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

<?php admin_footer(); ?>
<script>
    (function() {

        var titleInput = document.getElementById('serviceTitleInput');
        var slugInput = document.getElementById('serviceSlugInput');
        var industryInput = document.getElementById('industrySelect');

        if (!titleInput || !slugInput || !industryInput) return;

        function slugify(text) {
            return String(text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function updateSlug() {

            var title = titleInput.value.trim();

            var industry = industryInput.value.trim();

            if (industry) {
                var match = industry.match(/cho\s+(.+)$/i);

                if (match) {
                    industry = match[1];
                }
            }

            var slug = slugify(title);

            if (industry) {
                slug += '-' + slugify(industry);
            }

            slugInput.value = slug;
        }

        titleInput.addEventListener('input', updateSlug);
        industryInput.addEventListener('input', updateSlug);

        updateSlug();

    })();
</script>
<script>
function addPackage() {
    var list = document.getElementById('packagesList');
    var idx = list.children.length;
    var div = document.createElement('div');
    div.className = 'pkg-row';
    div.style.cssText = 'display:grid;grid-template-columns:1fr 120px 80px 1fr auto;gap:8px;align-items:start;padding:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:8px;';
    div.innerHTML = '<div><label class="small">Tên gói</label><input class="form-control" type="text" name="pkg_name[]" placeholder="VD: Gói Cơ bản"></div>' +
        '<div><label class="small">Giá</label><input class="form-control" type="text" name="pkg_price[]" placeholder="2.000.000"></div>' +
        '<div><label class="small">Đơn vị</label><input class="form-control" type="text" name="pkg_unit[]" placeholder="/tháng"></div>' +
        '<div><label class="small">Tính năng (mỗi dòng 1)</label><textarea class="form-control" name="pkg_features[]" rows="3" placeholder="Quản lý fanpage\\nĐăng 20 bài/tháng"></textarea></div>' +
        '<div style="display:flex;flex-direction:column;gap:4px;padding-top:20px;"><label style="font-size:0.75rem;display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" name="pkg_highlight[]" value="' + idx + '"> Nổi bật</label><button type="button" onclick="this.closest(\'.pkg-row\').remove()" style="background:#ff8c8c;color:#3d1111;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:0.75rem;">Xóa</button></div>';
    list.appendChild(div);
}
</script>
