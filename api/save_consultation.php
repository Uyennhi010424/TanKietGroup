<?php
// API endpoint to save consultation requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/config.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Collect form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $service = $_POST['goal'] ?? '';
    $message = $_POST['message'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Yêu cầu tư vấn của bạn đã được gửi thành công']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
