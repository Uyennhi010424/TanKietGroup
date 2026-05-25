<?php
require_once __DIR__ . '/config/constants.php';

$page = $_GET['page'] ?? 'home';

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
	'contact' => [
		'view' => __DIR__ . '/views/contact.php',
		'title' => 'Liên hệ',
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
	'admin_login' => [
		'view' => __DIR__ . '/admin/login.php',
		'title' => 'Đăng nhập quản trị',
		'layout' => 'none',
	],
	'projects' => [
		'view' => __DIR__ . '/views/projects/index.php',
		'title' => 'Dự án',
	],
];


// Special handling: if requesting a specific project detail via ?page=projects&id=slug
if ($page === 'projects' && !empty($_GET['id'])) {
    $viewMap['projects']['view'] = __DIR__ . '/views/projects/detail.php';
    $viewMap['projects']['title'] = 'Chi tiết dự án';
}

if (!isset($viewMap[$page])) {
    http_response_code(404);
    $page = 'home';
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
