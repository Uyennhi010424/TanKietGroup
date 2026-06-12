<?php
// API endpoint to save consultation requests
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF validation
if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang']);
    exit;
}

// Rate limiting — max 1 submission per 30 seconds
if (!api_rate_limit_check('consultation', 30)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Bạn gửi quá nhanh, vui lòng đợi vài giây rồi thử lại']);
    exit;
}

try {
    $pdo = get_db_connection();

    // Collect form data
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $service = trim((string)($_POST['goal'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $created_at = date('Y-m-d H:i:s');

    // Validation — name, phone, message are required; email is optional but must be valid if provided
    if ($name === '' || $phone === '' || $message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
        exit;
    }
    
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO consultations (name, email, phone, service, message, created_at, status) 
        VALUES (:name, :email, :phone, :service, :message, :created_at, 'new')
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':service' => $service,
        ':message' => $message,
        ':created_at' => $created_at
    ]);

    api_rate_limit_record('consultation');
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Yêu cầu tư vấn của bạn đã được gửi thành công']);
} catch (Exception $e) {
    error_log('Consultation API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi, vui lòng thử lại sau']);
}
?>
