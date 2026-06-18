<?php
// API: Course Enrollment
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

try {
    $db = get_db_connection();

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!csrf_validate($csrfToken)) {
        throw new RuntimeException('Token không hợp lệ, vui lòng tải lại trang');
    }

    $courseId = (int)($_POST['course_id'] ?? 0);
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($fullName === '') throw new RuntimeException('Vui lòng nhập họ và tên');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Vui lòng nhập email hợp lệ');
    if ($phone === '' || !preg_match('/^[0-9]{10,11}$/', $phone)) throw new RuntimeException('Vui lòng nhập số điện thoại hợp lệ (10-11 số)');
    if ($courseId <= 0) throw new RuntimeException('Khóa học không hợp lệ');

    // Check course exists
    $course = $db->prepare('SELECT id, title FROM courses WHERE id = :id AND status = 1');
    $course->execute(['id' => $courseId]);
    if (!$course->fetch()) throw new RuntimeException('Khóa học không tồn tại hoặc đã bị ẩn');

    $stmt = $db->prepare('INSERT INTO course_enrollments (course_id, full_name, email, phone, status) VALUES (:course_id, :full_name, :email, :phone, :status)');
    $stmt->execute([
        'course_id' => $courseId,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'status' => 'pending',
    ]);

    echo json_encode(['success' => true, 'message' => 'Đăng ký thành công! Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
