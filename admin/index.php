<?php

$page = $_GET['page'] ?? 'admin_index';

$adminViewMap = [
    'admin_courses'      => __DIR__ . '/courses.php',
    'admin_projects'     => __DIR__ . '/projects.php',
    'admin_services'     => __DIR__ . '/services.php',
    'admin_users'        => __DIR__ . '/users.php',
    'admin_blog'         => __DIR__ . '/blog.php',
    'admin_recruitments' => __DIR__ . '/recruitments.php',
    'admin_applications' => __DIR__ . '/applications.php',
    'admin_stats'        => __DIR__ . '/stats.php',
    'admin_settings'     => __DIR__ . '/settings.php',
    'admin_consultations'=> __DIR__ . '/consultations.php',
    'admin_clients'      => __DIR__ . '/clients.php',
    'admin_login'        => __DIR__ . '/login.php',
    'admin_media'        => __DIR__ . '/media.php',
];

if (isset($adminViewMap[$page])) {
    require $adminViewMap[$page];
    exit;
}

require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init(['require_admin' => true]);
$adminRoutes = $admin['routes'];

$db = null;
$serviceRows = [];
$countServices = 0;
$countProjects = 0;
$countPosts = 0;
try {
    $db = get_db_connection();
    $serviceRows = $db->query('SELECT s.id, s.title, s.slug, s.status, COALESCE(i.name, "-") AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.id DESC')->fetchAll();
    $countServices = (int)$db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $countProjects = (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $countPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
} catch (Throwable $e) {
    // leave counts as zero and rows empty if DB not available
}

admin_header('Tổng quan', 'Chào mừng đến trang quản trị', $admin, 'dashboard');
?>

            <section class="card-grid" style="margin-top:18px">
                <div class="card">
                    <div class="kpi"><?php echo (int)$countServices; ?></div>
                    <div class="small">Dịch vụ</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo (int)$countProjects; ?></div>
                    <div class="small">Dự án</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo (int)$countPosts; ?></div>
                    <div class="small">Bài viết</div>
                </div>
            </section>

            <section style="display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-top:22px">
                <div class="card" style="min-height:320px">
                    <h3 style="margin:0 0 12px 0">Tăng trưởng Lead</h3>
                    <div style="height:240px;background:linear-gradient(180deg,rgba(146,221,214,0.06),transparent);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--ak-muted)">Biểu đồ (placeholder)</div>
                </div>
                <div class="card">
                    <h3 style="margin:0 0 12px 0">Hoạt động gần đây</h3>
                    <div class="activity">
                        <div class="activity-item">
                            <div style="width:40px;height:40px;border-radius:8px;background:rgba(146,221,214,0.06);display:flex;align-items:center;justify-content:center;color:var(--ak-primary)">A</div>
                            <div>
                                <div style="font-weight:700">Admin</div>
                                <div style="color:var(--ak-muted)">Cập nhật bài viết mới</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div style="width:40px;height:40px;border-radius:8px;background:rgba(146,221,214,0.06);display:flex;align-items:center;justify-content:center;color:var(--ak-primary)">L</div>
                            <div>
                                <div style="font-weight:700">Khách hàng</div>
                                <div style="color:var(--ak-muted)">Gửi yêu cầu tư vấn</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section style="margin-top:22px">
                <div class="card" style="padding:8px 16px">
                    <h3 style="margin:8px 0 12px 0">Dịch vụ</h3>
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
                                <?php if (empty($serviceRows)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu dịch vụ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($serviceRows as $row): ?>
                                        <tr>
                                            <td>#SVC-<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge"><?php echo htmlspecialchars($row['industry_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int)$row['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td style="text-align:right"> <a class="btn-admin" href="<?php echo $adminRoutes['services']; ?>?edit=<?php echo (int)$row['id']; ?>">Sửa</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <button class="fab" title="Thêm">+</button>

<?php admin_footer(); ?>
