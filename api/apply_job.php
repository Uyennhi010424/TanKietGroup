<?php
// API endpoint for job applications with CV upload
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

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

// Rate limiting (IP-based)
$rateWait = ip_rate_limit_check('apply_job', 3, 30);
if ($rateWait > 0) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Bạn gửi quá nhanh, vui lòng đợi vài giây rồi thử lại']);
    exit;
}

try {
    $pdo = get_db_connection();

    $recruitmentId = (int)($_POST['job_id'] ?? 0);
    $name = trim((string)($_POST['apply_name'] ?? ''));
    $email = trim((string)($_POST['apply_email'] ?? ''));
    $phone = trim((string)($_POST['apply_phone'] ?? ''));
    $position = trim((string)($_POST['apply_position'] ?? ''));
    $message = trim((string)($_POST['apply_message'] ?? ''));

    // Validation
    if ($name === '' || $email === '' || $phone === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ họ tên, email và số điện thoại']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
        exit;
    }

    // Phone validation: at least 10 digits
    $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phoneDigits) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Số điện thoại phải có ít nhất 10 chữ số']);
        exit;
    }

    // Handle CV upload
    $cvFile = null;
    if (!empty($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cv_file'];
        $size = (int)($file['size'] ?? 0);
        if ($size > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File CV phải nhỏ hơn 10MB']);
            exit;
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Định dạng file không hỗ trợ. Vui lòng dùng PDF, DOC, DOCX, JPG, PNG']);
            exit;
        }

        // MIME type validation via file content
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMime = finfo_file($finfo, $file['tmp_name']);
                $allowedMimes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg', 'image/png',
                ];
                if (!is_string($detectedMime) || !in_array($detectedMime, $allowedMimes, true)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nội dung file không hợp lệ']);
                    exit;
                }
            }
        }

        $targetDir = rtrim(__DIR__ . '/../uploads/cv', '/\\');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            throw new RuntimeException('Không tạo được thư mục upload');
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $cvFile = 'cv/' . $filename;
        }
    }

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        recruitment_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        position VARCHAR(255),
        message TEXT,
        cv_file VARCHAR(500),
        status VARCHAR(20) DEFAULT 'new',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recruitment (recruitment_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->prepare('INSERT INTO job_applications (recruitment_id, name, email, phone, position, message, cv_file) VALUES (:rid, :name, :email, :phone, :position, :message, :cv)');
    $stmt->execute([
        ':rid' => $recruitmentId,
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':position' => $position,
        ':message' => $message,
        ':cv' => $cvFile,
    ]);

    ip_rate_limit_record('apply_job');
    echo json_encode(['success' => true, 'message' => 'Đã gửi đơn ứng tuyển thành công! Chúng tôi sẽ liên hệ với bạn sớm.']);

} catch (Exception $e) {
    error_log('Job apply API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi, vui lòng thử lại sau']);
}
