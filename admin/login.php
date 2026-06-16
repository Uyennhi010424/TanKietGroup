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

// Use media proxy for uploads paths (avoids realpath() issues on Windows with Unicode paths)
$loginSite = site_settings();
$loginLogo = trim((string)($loginSite['logo'] ?? ''));
if ($loginLogo !== '' && str_starts_with($loginLogo, 'uploads/')) {
    $logoSrc = site_page_url('admin_media') . '&path=' . rawurlencode($loginLogo);
} else {
    $logoSrc = site_logo_url('/img/logo.jpg');
}
$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$isLocalDebug = str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');

$loginRoute = site_page_url('admin_login');
$adminHome = site_page_url('admin_index');
$editorHome = site_page_url('admin_courses');
$siteHome = site_page_url('home');

// Process logout FIRST (before redirect)
if (isset($_GET['logout'])) {
	// Legacy GET logout — redirect to login without action (deprecated)
	header('Location: ' . $loginRoute);
	exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'logout') {
	if (csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
		admin_logout_user();
	}
	header('Location: ' . $loginRoute);
	exit;
}

// If already logged in, redirect to admin
if (admin_is_logged_in()) {
    $redirect = trim((string)($_GET['redirect'] ?? ''));
    if ($redirect !== '' && str_starts_with($redirect, '/')) {
        header('Location: ' . $redirect);
    } else {
        $currentUser = admin_current_user();
        $target = ((string)($currentUser['role'] ?? '') === 'admin') ? $adminHome : $editorHome;
        header('Location: ' . $target);
    }
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
		$error = 'Phiên làm việc không hợp lệ, vui lòng tải lại trang';
	} else {
	$waitSeconds = ip_rate_limit_check('login', 5, 600);
	if ($waitSeconds > 0) {
		$error = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . $waitSeconds . ' giây.';
	} else {
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
				ip_rate_limit_record('login');
			} elseif (!in_array((string)$row['role'], ['admin', 'editor'], true)) {
				$error = 'Tài khoản không có quyền truy cập khu vực quản trị';
				ip_rate_limit_record('login');
			} elseif (!password_verify($password, (string)$row['password'])) {
				$error = 'Sai mật khẩu';
				ip_rate_limit_record('login');
			} else {
				if (!password_get_info((string)$row['password'])['algo']) {
					$rehash = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
					$rehash->execute([
						'password' => password_hash($password, PASSWORD_DEFAULT),
						'id' => (int)$row['id'],
					]);
				}

				ip_rate_limit_reset('login');
				admin_login_user($row);
				$redirect = trim((string)($_POST['redirect'] ?? $_GET['redirect'] ?? ''));
				if ($redirect !== '' && str_starts_with($redirect, '/')) {
					header('Location: ' . $redirect);
				} else {
					$target = ((string)$row['role'] === 'admin') ? $adminHome : $editorHome;
					header('Location: ' . $target);
				}
				exit;
			}
		} catch (Throwable $e) {
			$error = $isLocalDebug
				? 'Không thể đăng nhập lúc này: ' . $e->getMessage()
				: 'Không thể đăng nhập lúc này';
		}
	}
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
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
				<?php $redirectVal = trim((string)($_GET['redirect'] ?? '')); ?>
				<?php if ($redirectVal !== ''): ?>
					<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectVal, ENT_QUOTES, 'UTF-8'); ?>">
				<?php endif; ?>
				<div class="form-group">
					<label for="user">Tài khoản</label>
					<input id="user" name="user" class="form-control" autocomplete="username" value="<?php echo htmlspecialchars($_POST['user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
				</div>
				<div class="form-group">
					<label for="pass">Mật khẩu</label>
					<div style="position:relative;">
						<input id="pass" type="password" name="pass" class="form-control" autocomplete="current-password" style="padding-right:40px;">
						<button type="button" id="togglePass" aria-label="Hiện mật khẩu" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ak-muted);padding:4px;">
							<svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
							<svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
						</button>
					</div>
				</div>
				<div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
					<button class="btn-admin" type="submit">Đăng nhập</button>
					<a href="<?php echo htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8'); ?>" style="color:var(--ak-muted);text-decoration:none">Quay về trang chính</a>
				</div>
			</form>
		</div>
	</div>
	<script>
	document.getElementById('togglePass').addEventListener('click', function() {
		var pass = document.getElementById('pass');
		var eyeOpen = document.getElementById('eyeOpen');
		var eyeClosed = document.getElementById('eyeClosed');
		if (pass.type === 'password') {
			pass.type = 'text';
			eyeOpen.style.display = 'none';
			eyeClosed.style.display = 'block';
			this.setAttribute('aria-label', 'Ẩn mật khẩu');
		} else {
			pass.type = 'password';
			eyeOpen.style.display = 'block';
			eyeClosed.style.display = 'none';
			this.setAttribute('aria-label', 'Hiện mật khẩu');
		}
	});
	</script>
</body>
</html>