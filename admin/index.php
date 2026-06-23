<?php

// Serve static files from project root (assets/, img/) when running from admin/
$staticUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/(assets|img)/(.+)$#', $staticUri, $sm)) {
    $staticFile = realpath(__DIR__ . '/../' . $sm[1] . '/' . $sm[2]);
    if ($staticFile && is_file($staticFile)) {
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($staticFile));
        readfile($staticFile);
        exit;
    }
}

$page = $_GET['page'] ?? 'admin_index';

// Chỉ kiểm tra đăng nhập cho trang admin, không chặn trang public
if (str_starts_with($page, 'admin_') && $page !== 'admin_login') {
    require_once __DIR__ . '/../includes/site.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/security.php';
    if (!admin_is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

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

// Public page view map — render through index.php in project root
$publicViewMap = [
    'home', 'about', 'services', 'service_detail', 'services_by_type',
    'industry_detail', 'courses', 'course_detail', 'projects', 'project_detail',
    'blog', 'blog_detail', 'contact', 'consultations', 'recruitments',
];
if (in_array($page, $publicViewMap, true)) {
    require __DIR__ . '/../index.php';
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
$countConsultations = 0;
$countApplications = 0;
$countEnrollments = 0;
$countCourses = 0;
$countClients = 0;

// Chart data
$consultationsByMonth = [];
$applicationsByMonth = [];
$enrollmentsByMonth = [];
$postsByStatus = ['draft' => 0, 'published' => 0];
$servicesByIndustry = [];
$topBlogPosts = [];

try {
    $db = get_db_connection();

    // KPI counts
    $countServices = (int)$db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $countProjects = (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $countPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    $countConsultations = (int)$db->query('SELECT COUNT(*) FROM consultations')->fetchColumn();
    $countCourses = (int)$db->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    $countClients = (int)$db->query('SELECT COUNT(*) FROM clients')->fetchColumn();

    // Applications count (table may not exist)
    try {
        $countApplications = (int)$db->query('SELECT COUNT(*) FROM job_applications')->fetchColumn();
    } catch (Throwable $e) {}

    // Enrollments count
    try {
        $countEnrollments = (int)$db->query('SELECT COUNT(*) FROM course_enrollments')->fetchColumn();
    } catch (Throwable $e) {}

    // Service rows for table
    $serviceRows = $db->query('SELECT s.id, s.title, s.slug, s.status, COALESCE(i.name, "-") AS industry_name FROM services s LEFT JOIN industries i ON i.id = s.industry_id ORDER BY s.id DESC')->fetchAll();

    // === CHART DATA ===

    // 1. Consultations by month (last 6 months)
    $consultationsByMonth = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
               DATE_FORMAT(created_at, '%m/%Y') AS month_label,
               COUNT(*) AS total
        FROM consultations
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month_key, month_label
        ORDER BY month_key ASC
    ")->fetchAll();

    // 2. Applications by month (last 6 months)
    try {
        $applicationsByMonth = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                   DATE_FORMAT(created_at, '%m/%Y') AS month_label,
                   COUNT(*) AS total
            FROM job_applications
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month_key, month_label
            ORDER BY month_key ASC
        ")->fetchAll();
    } catch (Throwable $e) {}

    // 3. Enrollments by month (last 6 months)
    try {
        $enrollmentsByMonth = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                   DATE_FORMAT(created_at, '%m/%Y') AS month_label,
                   COUNT(*) AS total
            FROM course_enrollments
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month_key, month_label
            ORDER BY month_key ASC
        ")->fetchAll();
    } catch (Throwable $e) {}

    // 4. Blog posts by status
    $postsByStatusRows = $db->query("
        SELECT status, COUNT(*) AS total
        FROM blog_posts
        GROUP BY status
    ")->fetchAll();
    foreach ($postsByStatusRows as $r) {
        $postsByStatus[$r['status']] = (int)$r['total'];
    }

    // 5. Services by industry
    $servicesByIndustry = $db->query("
        SELECT COALESCE(i.name, 'Chưa phân loại') AS industry_name, COUNT(*) AS total
        FROM services s
        LEFT JOIN industries i ON i.id = s.industry_id
        GROUP BY i.name
        ORDER BY total DESC
    ")->fetchAll();

    // 6. Top blog posts by views
    $topBlogPosts = $db->query("
        SELECT title, views, status
        FROM blog_posts
        ORDER BY views DESC
        LIMIT 5
    ")->fetchAll();

} catch (Throwable $e) {
    // leave counts as zero and rows empty if DB not available
}

admin_header('Tổng quan', 'Chào mừng đến trang quản trị', $admin, 'dashboard');
?>

            <!-- KPI CARDS -->
            <section class="card-grid" style="margin-top:18px">
                <div class="card">
                    <div class="kpi"><?php echo $countServices; ?></div>
                    <div class="small">Dịch vụ</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo $countProjects; ?></div>
                    <div class="small">Dự án</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo $countPosts; ?></div>
                    <div class="small">Bài viết</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo $countConsultations; ?></div>
                    <div class="small">Yêu cầu tư vấn</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo $countApplications; ?></div>
                    <div class="small">Đơn ứng tuyển</div>
                </div>
                <div class="card">
                    <div class="kpi"><?php echo $countEnrollments; ?></div>
                    <div class="small">Đăng ký khóa học</div>
                </div>
            </section>

            <!-- CHART ROW 1: Line chart + Doughnut -->
            <section class="dashboard-charts-row" style="margin-top:22px">
                <div class="card chart-card">
                    <h3 class="chart-title">Tăng trưởng Lead theo tháng</h3>
                    <p class="chart-subtitle">Tư vấn · Ứng tuyển · Đăng ký khóa học (6 tháng gần nhất)</p>
                    <div class="chart-container">
                        <canvas id="leadGrowthChart"></canvas>
                    </div>
                </div>
                <div class="card chart-card chart-card-sm">
                    <h3 class="chart-title">Trạng thái Blog</h3>
                    <p class="chart-subtitle">Phân bố bài viết nháp / xuất bản</p>
                    <div class="chart-container chart-container-sm">
                        <canvas id="blogStatusChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- CHART ROW 2: Bar chart + Top blog posts -->
            <section class="dashboard-charts-row" style="margin-top:22px">
                <div class="card chart-card">
                    <h3 class="chart-title">Dịch vụ theo ngành</h3>
                    <p class="chart-subtitle">Số lượng dịch vụ phân theo từng ngành nghề</p>
                    <div class="chart-container">
                        <canvas id="servicesByIndustryChart"></canvas>
                    </div>
                </div>
                <div class="card chart-card chart-card-sm">
                    <h3 class="chart-title">Top bài viết được xem nhiều</h3>
                    <p class="chart-subtitle">5 bài viết có lượt xem cao nhất</p>
                    <div class="chart-container chart-container-sm">
                        <canvas id="topPostsChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- RECENT ACTIVITY -->
            <section class="dashboard-activity-row" style="margin-top:22px">
                <div class="card">
                    <h3 style="margin:0 0 12px 0">Hoạt động gần đây</h3>
                    <div class="activity">
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-consult">💬</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countConsultations; ?> yêu cầu tư vấn</div>
                                <div style="color:var(--ak-muted)">Tổng số yêu cầu tư vấn từ khách hàng</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-apply">📄</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countApplications; ?> đơn ứng tuyển</div>
                                <div style="color:var(--ak-muted)">Tổng số đơn ứng tuyển tuyển dụng</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-enroll">🎓</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countEnrollments; ?> đăng ký khóa học</div>
                                <div style="color:var(--ak-muted)">Tổng số học viên đăng ký</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h3 style="margin:0 0 12px 0">Thống kê nhanh</h3>
                    <div class="activity">
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-course">📚</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countCourses; ?> khóa học</div>
                                <div style="color:var(--ak-muted)">Khóa học đang hoạt động</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-client">🏢</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countClients; ?> khách hàng</div>
                                <div style="color:var(--ak-muted)">Khách hàng tiêu biểu</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon activity-icon-project">📁</div>
                            <div>
                                <div style="font-weight:700"><?php echo $countProjects; ?> dự án</div>
                                <div style="color:var(--ak-muted)">Dự án đã thực hiện</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SERVICE TABLE -->
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

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Chart.js global defaults for dark theme ──
    Chart.defaults.color = '#5b656c';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';
    Chart.defaults.font.family = "'Be Vietnam Pro', Inter, sans-serif";

    var primaryColor = '#92ddd6';
    var accentColor = '#ffd082';
    var primarySoft = 'rgba(146, 221, 214, 0.15)';
    var accentSoft = 'rgba(255, 208, 130, 0.15)';

    // ── 1. Lead Growth Line Chart ──
    (function() {
        var canvas = document.getElementById('leadGrowthChart');
        if (!canvas) return;

        // Build full 6-month labels
        var now = new Date();
        var labels = [];
        for (var i = 5; i >= 0; i--) {
            var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            labels.push(mm + '/' + d.getFullYear());
        }

        function mapData(rawData) {
            var map = {};
            rawData.forEach(function(r) { map[r.month_label] = parseInt(r.total); });
            return labels.map(function(l) { return map[l] || 0; });
        }

        var consultData = mapData(<?php echo json_encode($consultationsByMonth); ?>);
        var applyData   = mapData(<?php echo json_encode($applicationsByMonth); ?>);
        var enrollData  = mapData(<?php echo json_encode($enrollmentsByMonth); ?>);

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tư vấn',
                        data: consultData,
                        borderColor: primaryColor,
                        backgroundColor: primarySoft,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: primaryColor,
                        borderWidth: 2.5
                    },
                    {
                        label: 'Ứng tuyển',
                        data: applyData,
                        borderColor: accentColor,
                        backgroundColor: accentSoft,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: accentColor,
                        borderWidth: 2.5
                    },
                    {
                        label: 'Đăng ký khóa học',
                        data: enrollData,
                        borderColor: '#a78bfa',
                        backgroundColor: 'rgba(167, 139, 250, 0.12)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#a78bfa',
                        borderWidth: 2.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 16 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,24,0.95)',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    })();

    // ── 2. Blog Status Doughnut Chart ──
    (function() {
        var canvas = document.getElementById('blogStatusChart');
        if (!canvas) return;

        var draft = <?php echo (int)$postsByStatus['draft']; ?>;
        var published = <?php echo (int)$postsByStatus['published']; ?>;

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Nháp (Draft)', 'Xuất bản (Published)'],
                datasets: [{
                    data: [draft, published],
                    backgroundColor: ['rgba(255,208,130,0.8)', 'rgba(146,221,214,0.8)'],
                    borderColor: ['rgba(255,208,130,1)', 'rgba(146,221,214,1)'],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 16 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,24,0.95)',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12,
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a,b){return a+b;}, 0);
                                var pct = total > 0 ? Math.round(ctx.raw / total * 100) : 0;
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    })();

    // ── 3. Services by Industry Bar Chart ──
    (function() {
        var canvas = document.getElementById('servicesByIndustryChart');
        if (!canvas) return;

        var rawData = <?php echo json_encode($servicesByIndustry); ?>;
        var labels = rawData.map(function(r) { return r.industry_name; });
        var data = rawData.map(function(r) { return parseInt(r.total); });

        // Gradient colors for bars
        var colors = [
            'rgba(146, 221, 214, 0.8)',
            'rgba(255, 208, 130, 0.8)',
            'rgba(167, 139, 250, 0.8)',
            'rgba(251, 146, 146, 0.8)',
            'rgba(96, 165, 250, 0.8)',
            'rgba(52, 211, 153, 0.8)',
            'rgba(251, 191, 36, 0.8)'
        ];

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số dịch vụ',
                    data: data,
                    backgroundColor: colors.slice(0, data.length),
                    borderColor: colors.slice(0, data.length).map(function(c) { return c.replace('0.8', '1'); }),
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 56
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,24,0.95)',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            callback: function(value) {
                                var label = this.getLabelForValue(value);
                                return label.length > 28 ? label.substring(0, 25) + '...' : label;
                            }
                        }
                    }
                }
            }
        });
    })();

    // ── 4. Top Blog Posts Horizontal Bar Chart ──
    (function() {
        var canvas = document.getElementById('topPostsChart');
        if (!canvas) return;

        var rawData = <?php echo json_encode($topBlogPosts); ?>;
        if (rawData.length === 0) {
            canvas.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--ak-muted)">Chưa có dữ liệu bài viết</div>';
            return;
        }

        var labels = rawData.map(function(r) {
            var t = r.title || 'Untitled';
            return t.length > 35 ? t.substring(0, 32) + '...' : t;
        });
        var data = rawData.map(function(r) { return parseInt(r.views) || 0; });
        var bgColors = rawData.map(function(r) {
            return r.status === 'published' ? 'rgba(146, 221, 214, 0.8)' : 'rgba(255, 208, 130, 0.5)';
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Lượt xem',
                    data: data,
                    backgroundColor: bgColors,
                    borderColor: bgColors.map(function(c) { return c.replace('0.8', '1').replace('0.5', '0.8'); }),
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,24,0.95)',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12,
                        callbacks: {
                            label: function(ctx) { return ctx.raw + ' lượt xem'; }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    })();

});
</script>

<?php admin_footer(); ?>
