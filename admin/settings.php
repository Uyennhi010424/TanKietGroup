<?php
// Admin - Global Settings Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init(['require_admin' => true]);
$adminRoutes = $admin['routes'];
$isEditor = $admin['isEditor'];

$csrfToken = csrf_token();
$mediaRoute = '/media.php?path=';

function ensure_settings_schema(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        site_name VARCHAR(255) NOT NULL,
        logo VARCHAR(255),
        banner VARCHAR(255),
        favicon VARCHAR(100),
        meta_title VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT,
        hotline VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        company_info TEXT,
        facebook VARCHAR(255),
        tiktok VARCHAR(255),
        youtube VARCHAR(255),
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = [
        'banner' => 'VARCHAR(255) NULL',
        'meta_keywords' => 'TEXT NULL',
        'company_info' => 'TEXT NULL',
    ];

    foreach ($columns as $column => $definition) {
        // Use quote() + query() because some MySQL drivers don't support placeholders in SHOW statements
        $quoted = $db->quote($column);
        $check = $db->query('SHOW COLUMNS FROM settings LIKE ' . $quoted);
        if (!$check || !$check->fetch()) {
            $db->exec('ALTER TABLE settings ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}

$db = null;
$dbError = '';
$flash = $_GET['msg'] ?? '';

$setting = [
    'id' => 1,
    'site_name' => 'TanKiet Group',
    'logo' => '',
    'banner' => '',
    'hotline' => '',
    'email' => '',
    'address' => '',
    'company_info' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'facebook' => '',
    'tiktok' => '',
    'youtube' => '',
];

try {
    $db = get_db_connection();
    ensure_settings_schema($db);
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

if ($db && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('CSRF token khong hop le');
        }

        $siteName = trim($_POST['site_name'] ?? '');
        $hotline = trim($_POST['hotline'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $companyInfo = trim($_POST['company_info'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $tiktok = trim($_POST['tiktok'] ?? '');
        $youtube = trim($_POST['youtube'] ?? '');

        $logo = trim($_POST['current_logo'] ?? '');
        $banner = trim($_POST['current_banner'] ?? '');

        if ($siteName === '') {
            throw new RuntimeException('Ten cong ty khong duoc de trong');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email khong dung dinh dang');
        }

        $newLogo = store_uploaded_image('logo_file', 'uploads/settings');
        if ($newLogo !== null) {
            $logo = $newLogo;
        }

        $newBanner = store_uploaded_image('banner_file', 'uploads/settings');
        if ($newBanner !== null) {
            $banner = $newBanner;
        }

        $stmt = $db->prepare('REPLACE INTO settings (id, site_name, logo, banner, hotline, email, address, company_info, meta_title, meta_description, meta_keywords, facebook, tiktok, youtube) VALUES (1, :site_name, :logo, :banner, :hotline, :email, :address, :company_info, :meta_title, :meta_description, :meta_keywords, :facebook, :tiktok, :youtube)');
        $stmt->execute([
            'site_name' => $siteName,
            'logo' => $logo,
            'banner' => $banner,
            'hotline' => $hotline,
            'email' => $email,
            'address' => $address,
            'company_info' => $companyInfo,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'facebook' => $facebook,
            'tiktok' => $tiktok,
            'youtube' => $youtube,
        ]);

        header('Location: ' . with_query($adminRoutes['settings'], ['msg' => 'Đã cập nhật cài đặt hệ thống']));
        exit;
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

if ($db) {
    try {
        $stmt = $db->query('SELECT id, site_name, logo, banner, hotline, email, address, company_info, meta_title, meta_description, meta_keywords, facebook, tiktok, youtube FROM settings ORDER BY id ASC LIMIT 1');
    } catch (PDOException $e) {
        // If the settings table exists but some columns are missing, try to ensure schema and retry once
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
            try {
                ensure_settings_schema($db);
                $stmt = $db->query('SELECT id, site_name, logo, banner, hotline, email, address, company_info, meta_title, meta_description, meta_keywords, facebook, tiktok, youtube FROM settings ORDER BY id ASC LIMIT 1');
            } catch (Throwable $e2) {
                $dbError = $e2->getMessage();
                $stmt = false;
            }
        } else {
            $dbError = $e->getMessage();
            $stmt = false;
        }
    }

    if ($stmt) {
        $row = $stmt->fetch();
        if ($row) {
            $setting = array_merge($setting, $row);
        }
    }
}

admin_header('Cài đặt hệ thống', 'Quản lý cấu hình website', $admin, 'settings');
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

            <div class="card" style="padding:16px;">
                <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="current_logo" value="<?php echo h($setting['logo']); ?>">
                    <input type="hidden" name="current_banner" value="<?php echo h($setting['banner']); ?>">

                    <div>
                        <label class="small">Tên công ty</label>
                        <input class="form-control" type="text" name="site_name" required value="<?php echo h($setting['site_name']); ?>">
                    </div>
                    <div>
                        <label class="small">Hotline</label>
                        <input class="form-control" type="text" name="hotline" value="<?php echo h($setting['hotline']); ?>">
                    </div>
                    <div>
                        <label class="small">Email công ty</label>
                        <input class="form-control" type="email" name="email" value="<?php echo h($setting['email']); ?>">
                    </div>
                    <div>
                        <label class="small">Địa chỉ</label>
                        <input class="form-control" type="text" name="address" value="<?php echo h($setting['address']); ?>">
                    </div>

                    <div style="grid-column:1 / -1;">
                        <label class="small">Thông tin công ty</label>
                        <textarea class="form-control" name="company_info" rows="4"><?php echo h($setting['company_info']); ?></textarea>
                    </div>

                    <div>
                        <label class="small">Logo</label>
                        <input class="form-control" type="file" name="logo_file" accept="image/*">
                        <?php if (!empty($setting['logo'])): ?>
                            <img src="<?php echo h($mediaRoute . rawurlencode($setting['logo'])); ?>" alt="Logo" style="margin-top:8px;max-height:70px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="small">Banner</label>
                        <input class="form-control" type="file" name="banner_file" accept="image/*">
                        <?php if (!empty($setting['banner'])): ?>
                            <img src="<?php echo h($mediaRoute . rawurlencode($setting['banner'])); ?>" alt="Banner" style="margin-top:8px;max-height:70px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);">
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="small">SEO Title (global)</label>
                        <input class="form-control" type="text" name="meta_title" value="<?php echo h($setting['meta_title']); ?>">
                    </div>
                    <div>
                        <label class="small">SEO Keywords (global)</label>
                        <input class="form-control" type="text" name="meta_keywords" value="<?php echo h($setting['meta_keywords']); ?>">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label class="small">SEO Description (global)</label>
                        <textarea class="form-control" name="meta_description" rows="3"><?php echo h($setting['meta_description']); ?></textarea>
                    </div>

                    <div>
                        <label class="small">Facebook</label>
                        <input class="form-control" type="url" name="facebook" value="<?php echo h($setting['facebook']); ?>">
                    </div>
                    <div>
                        <label class="small">TikTok</label>
                        <input class="form-control" type="url" name="tiktok" value="<?php echo h($setting['tiktok']); ?>">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label class="small">YouTube</label>
                        <input class="form-control" type="url" name="youtube" value="<?php echo h($setting['youtube']); ?>">
                    </div>

                    <div style="grid-column:1 / -1;display:flex;gap:10px;">
                        <button class="btn-admin" type="submit">Lưu cài đặt</button>
                    </div>
                </form>
            </div>
        </section>

<?php admin_footer(); ?>
