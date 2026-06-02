<?php
require_once __DIR__ . '/includes/security.php';

echo '<h1>Session Debug for IP-based Access</h1>';
echo '<pre>';

echo "=== Server Info ===\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'NOT SET') . "\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'NOT SET') . "\n";

echo "\n=== Session Configuration ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Status: " . session_status() . " (1=NONE, 2=ACTIVE)\n";

$params = session_get_cookie_params();
echo "\nCookie Parameters:\n";
echo "  lifetime: " . $params['lifetime'] . "\n";
echo "  path: " . $params['path'] . "\n";
echo "  domain: '" . $params['domain'] . "' (empty = host-only)\n";
echo "  secure: " . ($params['secure'] ? 'true' : 'false') . "\n";
echo "  httponly: " . ($params['httponly'] ? 'true' : 'false') . "\n";
echo "  samesite: " . ($params['samesite'] ?? 'NOT SET') . "\n";

echo "\n=== Session Storage ===\n";
echo "save_handler: " . ini_get('session.save_handler') . "\n";
echo "save_path: " . (ini_get('session.save_path') ?: 'default') . "\n";
$tmpdir = sys_get_temp_dir();
echo "sys_get_temp_dir(): " . $tmpdir . "\n";

$session_files = glob($tmpdir . '/sess_*');
echo "Session files in temp: " . count($session_files) . "\n";

echo "\n=== Current Session Data ===\n";
echo "Session contents:\n";
var_dump($_SESSION);

echo "\n=== CSRF Token ===\n";
$token = csrf_token();
echo "Current token: " . substr($token, 0, 20) . "...\n";
echo "Token length: " . strlen($token) . "\n";

echo "\n=== Instructions for 2-Machine Setup ===\n";
echo "1. Machine A: Copy this Session ID: " . session_id() . "\n";
echo "2. Machine B: Open browser DevTools (F12) → Application → Cookies\n";
echo "3. Check if TKGSESSID cookie equals Machine A's session ID\n";
echo "4. If different → Cookie is NOT shared (check step 5)\n";
echo "5. If cookie missing → Session not initialized properly\n";
echo "6. Test: Both machines should have SAME session ID to share CSRF token\n";

echo "</pre>";

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<hr><h2>POST Test Result</h2>';
    echo '<pre>';
    $post_token = (string)($_POST['csrf_token'] ?? '');
    $is_valid = csrf_validate($post_token);
    
    echo "POST token submitted: " . substr($post_token, 0, 20) . "...\n";
    echo "Session token stored: " . substr($_SESSION['csrf_token'] ?? '', 0, 20) . "...\n";
    echo "Validation result: " . ($is_valid ? '✓ VALID' : '✗ INVALID') . "\n";
    
    if (!$is_valid) {
        echo "\nDEBUG: Token mismatch\n";
        echo "Expected: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
        echo "Got: " . $post_token . "\n";
    }
    echo "</pre>";
}
?>

<hr>
<h2>Test Form</h2>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Submit Test</button>
</form>

<hr>
<h2>Cookie Check</h2>
<p>Open DevTools (F12) → Application → Cookies and look for:</p>
<ul>
    <li><strong>TKGSESSID</strong> - Session cookie (should match Session ID above)</li>
    <li><strong>Domain</strong> - Should be blank (host-only) for IP-based access</li>
    <li><strong>Path</strong> - Should be /</li>
</ul>
