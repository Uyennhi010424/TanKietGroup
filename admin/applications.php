<?php
// Admin - Job Applications Management
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../views/admin/layout.php';

$admin = admin_init();
$adminRoutes = $admin['routes'];

$db = null;
$dbError = '';
$flash = $_GET['msg'] ?? '';
$csrfToken = csrf_token();
$mediaRoute = site_page_url('admin_media') . '&path=';

try {
    $db = get_db_connection();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

// Update application status
if ($db && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('CSRF token không hợp lệ');
        }

        if ($action === 'update_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'new');
            if (!in_array($status, ['new', 'contacted', 'interviewed', 'accepted', 'rejected'], true)) {
                $status = 'new';
            }
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE job_applications SET status = :status WHERE id = :id');
                $stmt->execute(['status' => $status, 'id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['applications'], ['msg' => 'Đã cập nhật trạng thái']));
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Delete CV file if exists
                $stmt = $db->prepare('SELECT cv_file FROM job_applications WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $app = $stmt->fetch();
                if ($app && !empty($app['cv_file'])) {
                    $cvPath = __DIR__ . '/../uploads/' . $app['cv_file'];
                    if (is_file($cvPath)) {
                        @unlink($cvPath);
                    }
                }
                $stmt = $db->prepare('DELETE FROM job_applications WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: ' . with_query($adminRoutes['applications'], ['msg' => 'Đã xóa đơn ứng tuyển']));
            exit;
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

// Fetch applications
$applications = [];
if ($db) {
    try {
        $applications = $db->query('SELECT ja.*, r.title AS job_title FROM job_applications ja LEFT JOIN recruitments r ON r.id = ja.recruitment_id ORDER BY ja.created_at DESC LIMIT 100')->fetchAll();
    } catch (Throwable $e) {
        // table might not exist yet
    }
}

// View single application
$viewId = (int)($_GET['view'] ?? 0);
$detail = null;
if ($viewId > 0 && $db) {
    try {
        $stmt = $db->prepare('SELECT ja.*, r.title AS job_title FROM job_applications ja LEFT JOIN recruitments r ON r.id = ja.recruitment_id WHERE ja.id = :id LIMIT 1');
        $stmt->execute(['id' => $viewId]);
        $detail = $stmt->fetch();
    } catch (Throwable $e) {}
}

$statusLabels = [
    'new' => 'Mới',
    'contacted' => 'Đã liên hệ',
    'interviewed' => 'Đã phỏng vấn',
    'accepted' => 'Đã nhận',
    'rejected' => 'Từ chối',
];

$statusColors = [
    'new' => 'rgba(134,215,223,0.15)',
    'contacted' => 'rgba(255,208,130,0.15)',
    'interviewed' => 'rgba(134,215,223,0.25)',
    'accepted' => 'rgba(46,204,113,0.15)',
    'rejected' => 'rgba(231,76,60,0.15)',
];

admin_header('Đơn ứng tuyển', 'Quản lý đơn ứng tuyển việc làm', $admin, 'applications');
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

                <?php if ($detail): ?>
                    <!-- Application Detail View -->
                    <div class="card" style="padding:24px;margin-bottom:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                            <h3 style="margin:0;">Đơn ứng tuyển #APP-<?php echo str_pad((string)$detail['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                            <a class="btn-admin" href="<?php echo h($adminRoutes['applications']); ?>">Quay lại</a>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div>
                                <label class="small" style="color:var(--ak-muted);">Họ tên</label>
                                <div style="font-size:1.1rem;font-weight:600;"><?php echo h($detail['name']); ?></div>
                            </div>
                            <div>
                                <label class="small" style="color:var(--ak-muted);">Email</label>
                                <div><a href="mailto:<?php echo h($detail['email']); ?>" style="color:var(--ak-primary);"><?php echo h($detail['email']); ?></a></div>
                            </div>
                            <div>
                                <label class="small" style="color:var(--ak-muted);">Điện thoại</label>
                                <div><a href="tel:<?php echo h($detail['phone']); ?>" style="color:var(--ak-primary);"><?php echo h($detail['phone']); ?></a></div>
                            </div>
                            <div>
                                <label class="small" style="color:var(--ak-muted);">Vị trí ứng tuyển</label>
                                <div><?php echo h($detail['position'] ?: $detail['job_title'] ?: '-'); ?></div>
                            </div>
                            <div>
                                <label class="small" style="color:var(--ak-muted);">Ngày gửi</label>
                                <div><?php echo h($detail['created_at']); ?></div>
                            </div>
                            <div>
                                <label class="small" style="color:var(--ak-muted);">CV đính kèm</label>
                                <div>
                                    <?php if (!empty($detail['cv_file'])): ?>
                                        <a href="<?php echo h($mediaRoute . rawurlencode($detail['cv_file'])); ?>" target="_blank" class="btn-admin" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                                            Tải CV
                                        </a>
                                    <?php else: ?>
                                        Không có CV
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label class="small" style="color:var(--ak-muted);">Giới thiệu bản thân</label>
                                <div class="card" style="padding:16px;margin-top:8px;background:var(--ak-surface-2);line-height:1.7;">
                                    <?php echo nl2br(h($detail['message'] ?: 'Không có')); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Update status -->
                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--ak-border);">
                            <form method="post" style="display:flex;gap:12px;align-items:center;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$detail['id']; ?>">
                                <label class="small" style="margin:0;">Trạng thái:</label>
                                <select class="form-control" name="status" style="max-width:200px;">
                                    <?php foreach ($statusLabels as $val => $label): ?>
                                        <option value="<?php echo $val; ?>" <?php echo $detail['status'] === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-admin" type="submit">Cập nhật</button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Applications List -->
                    <div class="card" style="padding:8px 16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin:8px 0 12px 0;">
                            <h3 style="margin:0;">Danh sách đơn ứng tuyển</h3>
                            <span class="small" style="color:var(--ak-muted);">Tổng: <?php echo count($applications); ?> đơn</span>
                        </div>
                        <div style="overflow:auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Điện thoại</th>
                                        <th>Vị trí</th>
                                        <th>CV</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày gửi</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$applications): ?>
                                        <tr>
                                            <td colspan="9" style="text-align:center;color:var(--ak-muted);padding:24px;">Chưa có đơn ứng tuyển nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td>#APP-<?php echo str_pad((string)$app['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo h($app['name']); ?></td>
                                                <td><?php echo h($app['email']); ?></td>
                                                <td><?php echo h($app['phone']); ?></td>
                                                <td><?php echo h($app['position'] ?: $app['job_title'] ?: '-'); ?></td>
                                                <td>
                                                    <?php if (!empty($app['cv_file'])): ?>
                                                        <a href="<?php echo h($mediaRoute . rawurlencode($app['cv_file'])); ?>" target="_blank" style="color:var(--ak-primary);">Xem CV</a>
                                                    <?php else: ?>
                                                        <span style="color:var(--ak-muted);">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span style="padding:3px 10px;border-radius:12px;font-size:0.8rem;background:<?php echo $statusColors[$app['status']] ?? 'rgba(255,255,255,0.05)'; ?>;">
                                                        <?php echo $statusLabels[$app['status']] ?? $app['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo h($app['created_at']); ?></td>
                                                <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
                                                    <a class="btn-admin" href="<?php echo h(with_query($adminRoutes['applications'], ['view' => (int)$app['id']])); ?>">Chi tiết</a>
                                                    <form method="post" onsubmit="return confirm('Xóa đơn này?');" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int)$app['id']; ?>">
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
                <?php endif; ?>
            </section>

<?php admin_footer(); ?>
