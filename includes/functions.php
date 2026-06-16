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

	// Validate actual MIME type via file content
	$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
	if (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo) {
			$detectedMime = finfo_file($finfo, $tmpPath);
			finfo_close($finfo);
			if (!is_string($detectedMime) || !in_array($detectedMime, $allowedMimes, true)) {
				throw new RuntimeException('Noi dung file khong phai anh hop le');
			}
		}
	}

	// Verify file is a valid image
	if (function_exists('getimagesize')) {
		$imageInfo = @getimagesize($tmpPath);
		if ($imageInfo === false) {
			throw new RuntimeException('File khong phai anh hop le');
		}
	}

	$targetDir = rtrim(__DIR__ . '/../' . trim($subDir, '/\\'), '/\\');
	if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
		throw new RuntimeException('Khong tao duoc thu muc upload');
	}

	$filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
	$targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
	if (!move_uploaded_file($tmpPath, $targetPath)) {
		throw new RuntimeException('Khong luu duoc anh upload');
	}

	return trim($subDir, '/\\') . '/' . $filename;
}

/**
 * Sanitize HTML content to prevent XSS while keeping safe formatting tags.
 * Use for content stored in DB that may contain rich-text HTML.
 */
function sanitize_html(?string $html): string
{
    if ($html === null || $html === '') {
        return '';
    }

    // Allow common safe HTML tags (including <font> for rich text editor sizes)
    $allowed = '<p><br><strong><b><em><i><u><s><sub><sup>'
        . '<ul><ol><li><h1><h2><h3><h4><h5><h6>'
        . '<a><img><blockquote><table><thead><tbody><tr><th><td>'
        . '<div><span><hr><pre><code><font>';

    $html = strip_tags($html, $allowed);

    // Remove event handlers (on*) - quoted and unquoted
    $html = preg_replace('#\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)#i', '', $html);

    // Remove javascript:/vbscript:/data: URIs in href/src/action (quoted and unquoted)
    $html = preg_replace('#(href|src|action)\s*=\s*(?:"(?:javascript|vbscript|data):[^"]*"|\'(?:javascript|vbscript|data):[^\']*\')#i', '', $html);
    $html = preg_replace('#(href|src|action)\s*=\s*(?:javascript|vbscript|data):[^\s>]*#i', '', $html);

    // Remove style attributes with dangerous content (expression, url, behavior)
    $html = preg_replace('#\s+style\s*=\s*(?:"[^"]*(?:expression|url|behavior)[^"]*"|\'[^\']*(?:expression|url|behavior)[^\']*\')#i', '', $html);

    return $html;
}
