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

ob_start();
include $viewMap[$page]['view'];
$content = ob_get_clean();

include __DIR__ . '/views/layouts/main.php';
