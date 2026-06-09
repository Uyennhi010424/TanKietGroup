<?php
// Shared admin layout functions — call admin_header() at the top, admin_footer() at the bottom.

/**
 * Render the admin page header (DOCTYPE, head, sidebar, topbar opening).
 *
 * @param string $pageTitle    Page title shown in topbar
 * @param string $pageSubtitle Subtitle shown below title
 * @param array  $admin        Admin init data from admin_init()
 * @param string $currentPage  Current page key for active nav highlight (e.g. 'blog', 'courses')
 */
function admin_header(string $pageTitle, string $pageSubtitle, array $admin, string $currentPage = ''): void
{
    $routes    = $admin['routes'];
    $loginRoute= $admin['loginRoute'];
    $isEditor  = $admin['isEditor'];
    $role      = $admin['role'];
    $logoUrl   = $admin['logoUrl'];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo h($pageTitle); ?> - Trang quản trị</title>
    <link rel="icon" href="<?php echo h(site_favicon_url()); ?>">
    <style><?php echo file_get_contents(__DIR__ . '/../../admin/assets/css/admin.css'); ?></style>
    <script><?php echo file_get_contents(__DIR__ . '/../../admin/assets/js/admin.js'); ?></script>
</head>
<body class="role-<?php echo h($role); ?>">

    <div class="admin-wrap">
        <aside class="admin-sidebar" style="display:block">
            <div class="sidebar-header">
                <div class="brand-admin"><img src="<?php echo h($logoUrl); ?>" alt="TanKiet Group" class="site-logo"></div>
            </div>
            <nav>
                <ul class="nav-admin">
                    <?php if (!$isEditor): ?>
                        <li><a href="<?php echo $routes['dashboard']; ?>"<?php if ($currentPage === 'dashboard') echo ' class="active"'; ?>>Tổng quan</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $routes['courses']; ?>"<?php if ($currentPage === 'courses') echo ' class="active"'; ?>>Khóa học</a></li>
                    <li><a href="<?php echo $routes['projects']; ?>"<?php if ($currentPage === 'projects') echo ' class="active"'; ?>>Dự án</a></li>
                    <li><a href="<?php echo $routes['services']; ?>"<?php if ($currentPage === 'services') echo ' class="active"'; ?>>Dịch vụ</a></li>
                    <li><a href="<?php echo $routes['clients']; ?>"<?php if ($currentPage === 'clients') echo ' class="active"'; ?>>Khách hàng</a></li>
                    <li><a href="<?php echo $routes['users']; ?>"<?php if ($currentPage === 'users') echo ' class="active"'; ?>>Người dùng</a></li>
                    <li><a href="<?php echo $routes['blog']; ?>"<?php if ($currentPage === 'blog') echo ' class="active"'; ?>>Blog</a></li>
                    <li><a href="<?php echo $routes['recruitments']; ?>"<?php if ($currentPage === 'recruitments') echo ' class="active"'; ?>>Tuyển dụng</a></li>
                    <?php if (!$isEditor): ?>
                        <li><a href="<?php echo $routes['stats']; ?>"<?php if ($currentPage === 'stats') echo ' class="active"'; ?>>Thống kê tương tác</a></li>
                        <li><a href="<?php echo $routes['settings']; ?>"<?php if ($currentPage === 'settings') echo ' class="active"'; ?>>Cài đặt hệ thống</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $routes['consultations']; ?>"<?php if ($currentPage === 'consultations') echo ' class="active"'; ?>>Tư vấn khách hàng</a></li>
                    <li class="nav-admin-logout">
                        <form method="post" action="<?php echo h($loginRoute); ?>" style="display:inline">
                            <input type="hidden" name="action" value="logout">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                            <button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font:inherit;padding:0;">Đăng xuất</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>

        <main class="admin-main">
            <header class="topbar">
                <div style="display:flex;gap:20px;align-items:center">
                    <div class="title">
                        <h1><?php echo h($pageTitle); ?></h1>
                        <div class="small"><?php echo h($pageSubtitle); ?></div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:center">
                    <div class="search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="opacity:0.7">
                            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"></circle>
                        </svg>
                        <input placeholder="Tìm kiếm..." style="background:transparent;border:0;color:var(--ak-text);outline:none">
                    </div>
                </div>
            </header>
<?php
}

/**
 * Render the admin page footer (closing tags).
 */
function admin_footer(): void
{
?>
        </main>
    </div>
</body>
</html>
<?php
}
