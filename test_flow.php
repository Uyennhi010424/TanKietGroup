<?php
require_once __DIR__ . '/includes/security.php';

echo '<h1>Test Complete Flow</h1>';

// Step 1: Simulate being on the form page (GET request)
echo '<h2>Step 1: Load form page (GET)</h2>';
echo '<pre>';
echo "Session ID: " . session_id() . "\n";
$token_on_form = csrf_token();
echo "Token generated: " . $token_on_form . "\n";
echo "Session contents: " . json_encode($_SESSION) . "\n";
echo "</pre>";

// Simulate storing token in session to simulate multiple requests
session_write_close();

// Step 2: User submits form with the token (POST request simulation)
echo '<h2>Step 2: User submits form (POST)</h2>';

// Simulate POST data
$_POST['csrf_token'] = $token_on_form;
$_POST['action'] = 'test';

// Re-open session with same ID
session_id(session_id());
ensure_session_started();

echo '<pre>';
echo "Session ID: " . session_id() . "\n";
echo "Session from POST: " . json_encode($_SESSION) . "\n";
echo "POST csrf_token: " . $_POST['csrf_token'] . "\n";
echo "POST csrf_token (first 20 chars): " . substr($_POST['csrf_token'], 0, 20) . "...\n";
echo "Session token (first 20 chars): " . substr($_SESSION['csrf_token'] ?? '', 0, 20) . "...\n";

$is_valid = csrf_validate((string)($_POST['csrf_token'] ?? ''));
echo "csrf_validate result: " . ($is_valid ? 'TRUE' : 'FALSE') . "\n";

if (!$is_valid) {
    echo "\n✗ CSRF Token validation FAILED\n";
    echo "Expected: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
    echo "Got: " . ($_POST['csrf_token'] ?? 'NOT SET') . "\n";
} else {
    echo "\n✓ CSRF Token validation PASSED\n";
}
echo "</pre>";

// Step 3: Test after login (session regenerate)
echo '<h2>Step 3: Simulate Login (session_regenerate_id)</h2>';

$old_session_id = session_id();
echo '<pre>';
echo "Old Session ID: " . $old_session_id . "\n";
echo "Token before regenerate: " . $_SESSION['csrf_token'] . "\n";

admin_login_user([
    'id' => 1,
    'username' => 'admin',
    'full_name' => 'Admin',
    'role' => 'admin'
]);

echo "New Session ID: " . session_id() . "\n";
echo "Token after regenerate: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
echo "Session contents: " . json_encode($_SESSION) . "\n";
echo "</pre>";
?>
