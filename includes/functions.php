<?php
// Common helper functions

function format_vnd($amount): string
{
	if ($amount === null || $amount === '') {
		return '0 đ';
	}

	return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function store_uploaded_image(string $inputName, string $subDir = 'uploads'): ?string
{
	if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
		return null;
	}

	$file = $_FILES[$inputName];
	$error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($error === UPLOAD_ERR_NO_FILE) {
		return null;
	}
	if ($error !== UPLOAD_ERR_OK) {
		throw new RuntimeException('Upload anh that bai');
	}

	$size = (int)($file['size'] ?? 0);
	if ($size <= 0 || $size > 5 * 1024 * 1024) {
		throw new RuntimeException('Anh phai nho hon 5MB');
	}

	$tmpPath = (string)($file['tmp_name'] ?? '');
	$original = (string)($file['name'] ?? '');
	$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
	$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
	if (!in_array($ext, $allowed, true)) {
		throw new RuntimeException('Dinh dang anh khong hop le');
	}

	$targetDir = rtrim(__DIR__ . '/../' . trim($subDir, '/\\'), '/\\');
	if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
		throw new RuntimeException('Khong tao duoc thu muc upload');
	}

	$filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
	$targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
	if (!move_uploaded_file($tmpPath, $targetPath)) {
		throw new RuntimeException('Khong luu duoc anh upload');
	}

	return trim($subDir, '/\\') . '/' . $filename;
}
