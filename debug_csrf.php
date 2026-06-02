<?php
require_once __DIR__ . '/includes/security.php';

// Debug session and token behavior
echo '<h1>Debug CSRF Token</h1>';
echo '<pre>';

echo "1. Session Status: " . session_status() . " (1=NONE, 2=ACTIVE)\n";
echo "2. Session ID: " . session_id() . "\n";
echo "3. Session Name: " . session_name() . "\n";
echo "4. \$_SESSION contents:\n";
var_dump($_SESSION);

echo "\n5. Generating token...\n";
$token1 = csrf_token();
echo "Token 1: " . $token1 . "\n";

echo "\n6. \$_SESSION after token generation:\n";
var_dump($_SESSION);

echo "\n7. Validating token...\n";
$result = csrf_validate($token1);
echo "Validate same token: " . ($result ? 'TRUE' : 'FALSE') . "\n";

echo "\n8. Test with wrong token:\n";
$result = csrf_validate('wrong_token_here');
echo "Validate wrong token: " . ($result ? 'TRUE' : 'FALSE') . "\n";

echo "\n9. Session Cookie params:\n";
var_dump(session_get_cookie_params());

echo "\n10. Headers already sent?\n";
echo "headers_sent(): " . (headers_sent() ? 'TRUE' : 'FALSE') . "\n";

echo "</pre>";

// If POST, test validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h2>POST Test Result</h2>';
    echo '<pre>';
    echo "POST csrf_token: " . ($_POST['csrf_token'] ?? 'NOT SET') . "\n";
    
    $postToken = (string)($_POST['csrf_token'] ?? '');
    $isValid = csrf_validate($postToken);
    echo "Is valid: " . ($isValid ? 'TRUE' : 'FALSE') . "\n";
    
    echo "\nSession token during validation:\n";
    var_dump($_SESSION['csrf_token'] ?? 'NOT SET');
    
    if ($isValid) {
        echo "\n✓ TOKEN VALID - CSRF protection working!\n";
    } else {
        echo "\n✗ TOKEN INVALID - Session token and post token mismatch\n";
        echo "Session token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
        echo "Post token: " . $postToken . "\n";
    }
    
    echo "</pre>";
}
?>

<hr>
<h2>Test Form</h2>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Test POST (same session)</button>
</form>

<hr>
<h2>Simulating Login/Logout</h2>
<form method="post">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Simulate Login</button>
</form>

<?php
if ($_POST['action'] ?? '' === 'login') {
    echo '<p style="color:green">Login simulated. Now token should persist after session regenerate.</p>';
    admin_login_user([
        'id' => 1,
        'username' => 'test',
        'full_name' => 'Test User',
        'role' => 'admin'
    ]);
    echo '<p>New token after login: ' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>

