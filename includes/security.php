<?php
// Security helpers

function ensure_session_started(): void
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
}

function csrf_token(): string
{
	ensure_session_started();
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	return (string)$_SESSION['csrf_token'];
}

function csrf_validate(string $token): bool
{
	ensure_session_started();
	$sessionToken = $_SESSION['csrf_token'] ?? '';
	if (!is_string($sessionToken) || $sessionToken === '') {
		return false;
	}

	return hash_equals($sessionToken, $token);
}

function admin_current_user(): ?array
{
	ensure_session_started();
	$user = $_SESSION['admin_user'] ?? null;
	return is_array($user) ? $user : null;
}

function admin_is_logged_in(): bool
{
	return admin_current_user() !== null;
}

function admin_login_user(array $user): void
{
	ensure_session_started();
	$_SESSION['admin_user'] = [
		'id' => (int)($user['id'] ?? 0),
		'username' => (string)($user['username'] ?? ''),
		'full_name' => (string)($user['full_name'] ?? ''),
		'role' => (string)($user['role'] ?? 'user'),
	];
}

function admin_logout_user(): void
{
	ensure_session_started();
	unset($_SESSION['admin_user']);
}

function admin_require_login(string $loginUrl): void
{
	if (!admin_is_logged_in()) {
		$current = (string)($_SERVER['REQUEST_URI'] ?? '');
		$query = (string)($_SERVER['QUERY_STRING'] ?? '');

		// If the current request already targets the login page, do not redirect (avoid loops).
		if (stripos($query, 'page=admin_login') !== false) {
			return;
		}

		header('Location: ' . $loginUrl);
		exit;
	}
}

function admin_require_roles(array $allowedRoles, string $fallbackUrl): void
{
	$user = admin_current_user();
	$role = (string)($user['role'] ?? '');
	if (!in_array($role, $allowedRoles, true)) {
		$query = (string)($_SERVER['QUERY_STRING'] ?? '');
		if (stripos($query, 'page=') !== false && stripos($query, 'page=') === 0 && stripos($query, 'page=admin') !== false) {
			// If already on an admin page, avoid redirecting repeatedly.
			return;
		}

		header('Location: ' . $fallbackUrl);
		exit;
	}
}
