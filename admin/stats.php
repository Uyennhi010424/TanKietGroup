<?php
// Admin - Blog interaction statistics
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init(['require_admin' => true]);
$adminRoutes = $admin['routes'];

$dbError = '';
$totalPosts = 0;
$totalViews = 0;
$featuredCount = 0;
$topPosts = [];

try {
    $db = get_db_connection();
    $totalPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    $totalViews = (int)$db->query('SELECT COALESCE(SUM(views), 0) FROM blog_posts')->fetchColumn();
    $featuredCount = (int)$db->query('SELECT COUNT(*) FROM blog_posts WHERE is_featured = 1')->fetchColumn();
    $topPosts = $db->query('SELECT bp.id, bp.title, bp.slug, bp.views, bp.is_featured, bp.status, COALESCE(u.full_name, u.username, "-") AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id ORDER BY bp.views DESC, bp.id DESC LIMIT 20')->fetchAll();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

admin_header('Thống kê tương tác', 'Lượt tương tác từng bài viết và bài viết nổi bật', $admin, 'stats');
?>

        <?php if ($dbError !== ''): ?>
            <div class="card" style="margin-top:18px;background:rgba(255,120,120,0.12);padding:12px 16px;color:#ffb0b0;">
                Lỗi DB: <?php echo h($dbError); ?>
            </div>
        <?php endif; ?>

        <section class="card-grid" style="margin-top:18px">
            <div class="card">
                <div class="kpi"><?php echo $totalPosts; ?></div>
                <div class="small">Tổng bài viết</div>
            </div>
            <div class="card">
                <div class="kpi"><?php echo $totalViews; ?></div>
                <div class="small">Tổng lượt tương tác (views)</div>
            </div>
            <div class="card">
                <div class="kpi"><?php echo $featuredCount; ?></div>
                <div class="small">Bài viết nổi bật</div>
            </div>
        </section>

        <section style="margin-top:22px">
            <div class="card" style="padding:8px 16px">
                <h3 style="margin:8px 0 12px 0">Top bài viết theo lượt tương tác</h3>
                <div style="overflow:auto">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Tác giả</th>
                            <th>Lượt tương tác</th>
                            <th>Nổi bật</th>
                            <th>Trạng thái</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$topPosts): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--ak-muted);">Chưa có dữ liệu bài viết</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topPosts as $row): ?>
                                <tr>
                                    <td>#BLOG-<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo h($row['title']); ?></td>
                                    <td><?php echo h($row['author_name']); ?></td>
                                    <td><?php echo (int)$row['views']; ?></td>
                                    <td><?php echo (int)$row['is_featured'] === 1 ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo h($row['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

<?php admin_footer(); ?>
