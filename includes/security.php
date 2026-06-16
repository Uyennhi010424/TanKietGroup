<?php
// Security helpers
function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443');

        $domain = '';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        
        if ($host !== '') {
            $host = preg_replace('/:\d+$/', '', $host);
            // Set domain for non-localhost hosts (including IP addresses for shared access)
            if ($host !== 'localhost' && $host !== '127.0.0.1') {
                // For IP addresses like 192.168.1.242, keep domain empty so cookie works across network
                // For domain names, set domain with leading dot for subdomain sharing
                if (filter_var($host, FILTER_VALIDATE_IP)) {
                    // IP address - keep domain empty but browser will share within network
                    $domain = '';
                } else {
                    // Domain name - set for subdomain sharing
                    $domain = $host;
                }
            }
        }

        session_name('TKGSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $domain,
            'secure' => $cookieSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Không thể khởi tạo phiên làm việc');
        }
    }
}

ensure_session_started();

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

    if ($token === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    }

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
    
    // Preserve CSRF token during session regeneration
    $csrfToken = $_SESSION['csrf_token'] ?? null;
    
    session_regenerate_id(true);
    
    // Restore CSRF token after regeneration
    if ($csrfToken !== null) {
        $_SESSION['csrf_token'] = $csrfToken;
    }
    
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
    session_destroy();
}

function admin_require_login(string $loginUrl): void
{
    if (!admin_is_logged_in()) {
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

/**
 * Simple session-based rate limiter for login attempts.
 * Returns the number of seconds the user must wait, or 0 if allowed.
 */
function login_rate_limit_check(): int
{
    ensure_session_started();
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lastAttempt = $_SESSION['login_last_attempt'] ?? 0;

    if ($attempts >= 5) {
        $waitTime = min(300, 30 * pow(2, $attempts - 5)); // 30s, 60s, 120s, 240s, 300s max
        $elapsed = time() - $lastAttempt;
        if ($elapsed < $waitTime) {
            return (int)ceil($waitTime - $elapsed);
        }
        // Reset after cooldown
        $_SESSION['login_attempts'] = 0;
    }

    return 0;
}

function login_rate_limit_record_failure(): void
{
    ensure_session_started();
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_last_attempt'] = time();
}

function login_rate_limit_reset(): void
{
    ensure_session_started();
    unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
}

/**
 * Simple session-based rate limiter for form submissions.
 * Returns true if the submission is allowed, false if too soon.
 * @deprecated Use ip_rate_limit_check() instead for better security.
 */
function api_rate_limit_check(string $action, int $minIntervalSeconds = 30): bool
{
    ensure_session_started();
    $key = 'rate_limit_' . $action;
    $lastSubmit = $_SESSION[$key] ?? 0;
    return (time() - $lastSubmit) >= $minIntervalSeconds;
}

/**
 * @deprecated Use ip_rate_limit_record() instead for better security.
 */
function api_rate_limit_record(string $action): void
{
    ensure_session_started();
    $_SESSION['rate_limit_' . $action] = time();
}

/**
 * Get client IP address (handles proxied requests).
 */
function get_client_ip(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (str_contains($ip, ',')) {
        $ip = trim(explode(',', $ip, 2)[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
}

/**
 * IP-based rate limiter using file storage.
 * Returns seconds to wait, or 0 if allowed.
 */
function ip_rate_limit_check(string $action, int $maxAttempts = 5, int $windowSeconds = 300): int
{
    $ip = get_client_ip();
    $dir = sys_get_temp_dir() . '/tkg_rate_' . $action;
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $file = $dir . '/' . md5($ip) . '.json';
    $data = ['attempts' => 0, 'first_attempt' => 0, 'last_attempt' => 0];

    if (is_file($file)) {
        $stored = @json_decode((string)file_get_contents($file), true);
        if (is_array($stored)) {
            $data = $stored;
        }
    }

    $now = time();

    // Reset if window expired
    if (($now - (int)($data['first_attempt'] ?? 0)) > $windowSeconds) {
        $data = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => 0];
    }

    if ((int)($data['attempts'] ?? 0) >= $maxAttempts) {
        $waitTime = min(300, 30 * pow(2, (int)($data['attempts'] ?? 0) - $maxAttempts));
        $elapsed = $now - (int)($data['last_attempt'] ?? 0);
        if ($elapsed < $waitTime) {
            return (int)ceil($waitTime - $elapsed);
        }
        // Reset after cooldown
        $data = ['attempts' => 0, 'first_attempt' => $now, 'last_attempt' => 0];
    }

    return 0;
}

/**
 * Record a rate-limited event for the current IP.
 */
function ip_rate_limit_record(string $action): void
{
    $ip = get_client_ip();
    $dir = sys_get_temp_dir() . '/tkg_rate_' . $action;
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $file = $dir . '/' . md5($ip) . '.json';
    $data = ['attempts' => 0, 'first_attempt' => time(), 'last_attempt' => 0];

    if (is_file($file)) {
        $stored = @json_decode((string)file_get_contents($file), true);
        if (is_array($stored)) {
            $data = $stored;
        }
    }

    $data['attempts'] = ((int)($data['attempts'] ?? 0)) + 1;
    $data['last_attempt'] = time();

    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Reset rate limit for current IP.
 */
function ip_rate_limit_reset(string $action): void
{
    $ip = get_client_ip();
    $file = sys_get_temp_dir() . '/tkg_rate_' . $action . '/' . md5($ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}
