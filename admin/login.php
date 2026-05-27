<?php
// Admin login form
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

$adminCssHref = site_admin_url('assets/css/admin.css');
$inlineAdminCss = '';
$adminCssFile = __DIR__ . '/assets/css/admin.css';
if (is_file($adminCssFile)) {
	$inlineAdminCss = (string)file_get_contents($adminCssFile);
}

$logoSrc = site_logo_url('/img/logo.jpg');
$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$isLocalDebug = str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');

$loginRoute = site_page_url('admin_login');
$adminHome = site_page_url('admin_index');
$editorHome = site_page_url('admin_courses');
$siteHome = site_page_url('home');

if (isset($_GET['logout'])) {
	admin_logout_user();
	header('Location: ' . $loginRoute);
	exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$userInput = trim($_POST['user'] ?? '');
	$password = (string)($_POST['pass'] ?? '');

	if ($userInput === '' || $password === '') {
		$error = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu';
	} else {
		try {
			$db = get_db_connection();
			$stmt = $db->prepare('SELECT id, username, full_name, role, password, status FROM users WHERE (LOWER(username) = LOWER(:u1) OR LOWER(email) = LOWER(:u2)) LIMIT 1');
			$stmt->execute([
				'u1' => $userInput,
				'u2' => $userInput,
			]);
			$row = $stmt->fetch();

			if (!$row || (int)$row['status'] !== 1) {
				$error = 'Tài khoản không tồn tại hoặc đã bị khóa';
			} elseif (!in_array((string)$row['role'], ['admin', 'editor'], true)) {
				$error = 'Tài khoản không có quyền truy cập khu vực quản trị';
			} elseif (!(password_verify($password, (string)$row['password']) || hash_equals((string)$row['password'], $password))) {
				$error = 'Sai mật khẩu';
			} else {
				if (!password_get_info((string)$row['password'])['algo']) {
					$rehash = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
					$rehash->execute([
						'password' => password_hash($password, PASSWORD_DEFAULT),
						'id' => (int)$row['id'],
					]);
				}

				admin_login_user($row);
				$target = ((string)$row['role'] === 'admin') ? $adminHome : $editorHome;
				header('Location: ' . $target);
				exit;
			}
		} catch (Throwable $e) {
			$error = $isLocalDebug
				? 'Không thể đăng nhập lúc này: ' . $e->getMessage()
				: 'Không thể đăng nhập lúc này';
		}
	}
}
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Đăng nhập quản trị - TanKiet Group</title>
	<?php require_once __DIR__ . '/../includes/favicon_links.php'; ?>
	<?php if ($inlineAdminCss !== ''): ?>
		<style><?php echo $inlineAdminCss; ?></style>
	<?php else: ?>
		<link rel="stylesheet" href="<?php echo htmlspecialchars($adminCssHref, ENT_QUOTES, 'UTF-8'); ?>">
	<?php endif; ?>
</head>
<body>
	<div class="login-wrap">
		<div class="login-box">
			<div style="text-align:center;margin-bottom:12px"><img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="TanKiet Group" style="max-width:180px"></div>
			<h2 style="margin-top:0;color:var(--ak-primary)">Đăng nhập</h2>
			<?php if ($error !== ''): ?>
				<div class="card" style="margin-bottom:14px;background:rgba(255,120,120,0.12);padding:10px 12px;color:#ffb0b0;">
					<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo htmlspecialchars($loginRoute, ENT_QUOTES, 'UTF-8'); ?>">
				<div class="form-group">
					<label for="user">Tài khoản</label>
					<input id="user" name="user" class="form-control" autocomplete="username" value="<?php echo htmlspecialchars($_POST['user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
				</div>
				<div class="form-group">
					<label for="pass">Mật khẩu</label>
					<input id="pass" type="password" name="pass" class="form-control" autocomplete="current-password">
				</div>
				<div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
					<button class="btn-admin" type="submit">Đăng nhập</button>
					<a href="<?php echo htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8'); ?>" style="color:var(--ak-muted);text-decoration:none">Quay về trang chính</a>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
