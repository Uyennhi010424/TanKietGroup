<?php
require_once __DIR__ . '/config/constants.php';

// Mode-based routing: separate user (8000) and admin (8001) ports
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAdminPage = str_starts_with($_GET['page'] ?? '', 'admin_');
$isApiRoute = str_starts_with($reqPath, '/api/');
$serverPort = (string)($_SERVER['SERVER_PORT'] ?? '');

// Port 8000 = user only (block admin pages)
if ($serverPort === '8000' && $isAdminPage) {
    http_response_code(404);
    echo 'Not found';
    exit;
}
// Port 8001 = admin + API only (block user pages)
if ($serverPort === '8001' && !$isAdminPage && !$isApiRoute) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$page = $_GET['page'] ?? 'home';

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!isset($_GET['page'])) {
	$path = trim($reqPath, '/');

	if (preg_match('#^dich-vu/(.+)$#', $path, $matches)) {
		$slug = $matches[1];

		require_once __DIR__ . '/includes/site.php';

		$service = site_fetch_one(
			'SELECT slug FROM services WHERE slug = ?',
			[$slug]
		);

		if ($service) {
			$_GET['page'] = 'service_detail';
			$_GET['slug'] = $slug;
			$page = 'service_detail';
		} 
	} else if(preg_match('#^du-an/(.+)$#', $path, $matches)) {
			$slug = $matches[1];

			require_once __DIR__ . '/includes/site.php';

			$project = site_fetch_one(
				'SELECT slug FROM projects WHERE slug = ?',
				[$slug]
			);

			if ($project) {
				$_GET['page'] = 'project_detail';
				$_GET['slug'] = $slug;
				$page = 'project_detail';
			}
		} else if(preg_match('#^blog/(.+)$#', $path, $matches)) {
			$slug = $matches[1];

			require_once __DIR__ . '/includes/site.php';

			$post = site_fetch_one(
				'SELECT slug FROM blog_posts WHERE slug = ?',
				[$slug]
			);

			if ($post) {
				$_GET['page'] = 'blog_detail';
				$_GET['slug'] = $slug;
				$page = 'blog_detail';
			}
		} else if(preg_match('#^khoa-hoc/(.+)$#', $path, $matches)) {
			$slug = $matches[1];

			require_once __DIR__ . '/includes/site.php';

			$course = site_fetch_one(
				'SELECT slug FROM courses WHERE slug = ?',
				[$slug]
			);

			if ($course) {
				$_GET['page'] = 'course_detail';
				$_GET['slug'] = $slug;
				$page = 'course_detail';
			}
		} else if ($path === 'lien-he') {
			$_GET['page'] = 'contact';
			$page = 'contact';
		}	
}
if (preg_match('#^/(admin/)?(assets|img)/(.*)$#', $reqPath, $m)) {
	$prefix = !empty($m[1]) ? 'admin/' : '';
	$type = $m[2];
	$sub = $m[3];
	$file = realpath(__DIR__ . '/' . $prefix . $type . '/' . $sub);
	$baseDir = realpath(__DIR__ . '/' . $prefix . $type);
	if ($file && $baseDir && str_starts_with($file, $baseDir) && is_file($file)) {
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		$mimes = [
			'css' => 'text/css',
			'js' => 'application/javascript',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'webp' => 'image/webp',
			'svg' => 'image/svg+xml',
			'gif' => 'image/gif',
			'woff' => 'font/woff',
			'woff2' => 'font/woff2'
		];
		header('X-Accel-Buffered-Response: no');
		header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
		header('Content-Length: ' . filesize($file));
		readfile($file);
		exit;
	}
	http_response_code(404);
	echo 'Not Found';
	exit;
}

// API routes — forward to PHP files in /api/ directory
if (preg_match('#^/api/([a-z_]+)\.php$#', $reqPath, $m)) {
	$apiFile = __DIR__ . '/api/' . $m[1] . '.php';
	if (is_file($apiFile)) {
		require $apiFile;
		exit;
	}
}

$viewMap = [
	'home' => [
		'view' => __DIR__ . '/views/home.php',
		'title' => 'Trang chủ',
	],
	'about' => [
		'view' => __DIR__ . '/views/about.php',
		'title' => 'Giới thiệu',
	],
	'services' => [
		'view' => __DIR__ . '/views/services/index.php',
		'title' => 'Dịch vụ',
	],
	'service_detail' => [
		'view' => __DIR__ . '/views/services/detail.php',
		'title' => 'Chi tiết dịch vụ',
	],
	'services_by_type' => [
		'view' => __DIR__ . '/views/services/services_by_type.php',
		'title' => 'Dịch vụ theo loại',
	],
	'industry_detail' => [
		'view' => __DIR__ . '/views/services/services_by_industry.php',
		'title' => 'Dịch vụ theo ngành',
	],
	'courses' => [
		'view' => __DIR__ . '/views/courses/index.php',
		'title' => 'Khóa học',
	],
	'course_detail' => [
		'view' => __DIR__ . '/views/courses/detail.php',
		'title' => 'Chi tiết khóa học',
	],
	'projects' => [
		'view' => __DIR__ . '/views/projects/index.php',
		'title' => 'Dự án',
	],
	'project_detail' => [
		'view' => __DIR__ . '/views/projects/detail.php',
		'title' => 'Chi tiết dự án',
	],
	'blog' => [
		'view' => __DIR__ . '/views/blog/index.php',
		'title' => 'Blog',
	],
	'blog_detail' => [
		'view' => __DIR__ . '/views/blog/detail.php',
		'title' => 'Chi tiết bài viết',
	],
	'contact' => [
		'view' => __DIR__ . '/views/contact.php',
		'title' => 'Liên hệ',
	],
	'recruitments' => [
		'view' => __DIR__ . '/views/recruitments.php',
		'title' => 'Tuyển dụng',
	],
	'consultations' => [
		'view' => __DIR__ . '/views/consultations.php',
		'title' => 'Tư vấn',
	],
	'admin_index' => [
		'view' => __DIR__ . '/admin/index.php',
		'title' => 'Trang quản trị',
		'layout' => 'none',
	],
	'admin_courses' => [
		'view' => __DIR__ . '/admin/courses.php',
		'title' => 'Quản lý khóa học',
		'layout' => 'none',
	],
	'admin_projects' => [
		'view' => __DIR__ . '/admin/projects.php',
		'title' => 'Quản lý dự án',
		'layout' => 'none',
	],
	'admin_services' => [
		'view' => __DIR__ . '/admin/services.php',
		'title' => 'Quản lý dịch vụ',
		'layout' => 'none',
	],
	'admin_users' => [
		'view' => __DIR__ . '/admin/users.php',
		'title' => 'Quản lý người dùng',
		'layout' => 'none',
	],
	'admin_blog' => [
		'view' => __DIR__ . '/admin/blog.php',
		'title' => 'Quản lý blog',
		'layout' => 'none',
	],
	'admin_recruitments' => [
		'view' => __DIR__ . '/admin/recruitments.php',
		'title' => 'Quản lý tuyển dụng',
		'layout' => 'none',
	],
	'admin_applications' => [
		'view' => __DIR__ . '/admin/applications.php',
		'title' => 'Đơn ứng tuyển',
		'layout' => 'none',
	],
	'admin_stats' => [
		'view' => __DIR__ . '/admin/stats.php',
		'title' => 'Thống kê tương tác',
		'layout' => 'none',
	],
	'admin_settings' => [
		'view' => __DIR__ . '/admin/settings.php',
		'title' => 'Cài đặt hệ thống',
		'layout' => 'none',
	],
	'admin_media' => [
		'view' => __DIR__ . '/admin/media.php',
		'title' => 'Media',
		'layout' => 'none',
	],
	'admin_consultations' => [
		'view' => __DIR__ . '/admin/consultations.php',
		'title' => 'Tư vấn khách hàng',
		'layout' => 'none',
	],
	'admin_clients' => [
		'view' => __DIR__ . '/admin/clients.php',
		'title' => 'Quản lý khách hàng',
		'layout' => 'none',
	],
	'admin_login' => [
		'view' => __DIR__ . '/admin/login.php',
		'title' => 'Đăng nhập quản trị',
		'layout' => 'none',
	],
];

// Backward-compatible aliases for older query params.
if ($page === 'services' && !empty($_GET['slug'])) {
	$page = 'service_detail';
}

if ($page === 'courses' && !empty($_GET['slug'])) {
	$page = 'course_detail';
}

if ($page === 'projects' && !empty($_GET['id'])) {
	$page = 'project_detail';
}

if ($page === 'blog' && !empty($_GET['slug'])) {
	$page = 'blog_detail';
}

if (!isset($viewMap[$page])) {
	http_response_code(404);
	$pageTitle = '404 - Không tìm thấy trang';
	include __DIR__ . '/views/layouts/header.php';
	include __DIR__ . '/views/404.php';
	include __DIR__ . '/views/layouts/footer.php';
	exit;
}

$currentPage = $page;
$pageTitle = $viewMap[$page]['title'];

if (($viewMap[$page]['layout'] ?? 'main') === 'none') {
	include $viewMap[$page]['view'];
	return;
}

ob_start();
include $viewMap[$page]['view'];
$content = ob_get_clean();

include __DIR__ . '/views/layouts/main.php';
