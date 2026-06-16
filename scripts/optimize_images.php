<?php
/**
 * Image Optimization Script - Convert to WebP & Resize
 * Run: php scripts/optimize_images.php
 *
 * This script:
 * 1. Converts JPG/PNG images to WebP format (80% quality)
 * 2. Resizes oversized images (logo max 200px, hero max 1920px, thumbnails max 800px)
 * 3. Saves WebP versions alongside originals
 */

$baseDir = __DIR__ . '/..';

// Image optimization rules
$rules = [
    // Logo files - max 200px wide
    'img/logo.jpg'  => ['maxWidth' => 200, 'quality' => 85],
    'img/logo1.jpg' => ['maxWidth' => 200, 'quality' => 85],
    // Hero image - max 1920px wide
    'img/hero.jpg'  => ['maxWidth' => 1920, 'quality' => 80],
    // Large images - max 1200px wide
    'img/F&B.jpg'   => ['maxWidth' => 1200, 'quality' => 78],
    // Project/service images - max 800px wide
    'img/du_an1.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/du_an3.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/du_an4.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/du_an5.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/chayqc.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/xaykenh.jpg' => ['maxWidth' => 800, 'quality' => 80],
    'img/du_an.jpg'  => ['maxWidth' => 800, 'quality' => 80],
    'img/founder.jpg' => ['maxWidth' => 600, 'quality' => 80],
];

echo "=== TanKiet Group Image Optimizer ===\n\n";

$totalSaved = 0;
$converted = 0;

foreach ($rules as $relPath => $opts) {
    $srcPath = $baseDir . '/' . $relPath;
    if (!is_file($srcPath)) {
        echo "[SKIP] $relPath - not found\n";
        continue;
    }

    $originalSize = filesize($srcPath);
    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $srcPath);

    // Load image
    $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    $img = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($srcPath);
    } elseif ($ext === 'png') {
        $img = @imagecreatefrompng($srcPath);
    }

    if (!$img) {
        echo "[ERR]  $relPath - cannot load image\n";
        continue;
    }

    // Get original dimensions
    $origW = imagesx($img);
    $origH = imagesy($img);

    // Resize if needed
    $maxWidth = $opts['maxWidth'] ?? 1200;
    $newW = $origW;
    $newH = $origH;

    if ($origW > $maxWidth) {
        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
        $newH = (int)round($origH * $ratio);
    }

    // Create resized image
    $resized = imagecreatetruecolor($newW, $newH);
    if ($ext === 'png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);

    // Save as WebP
    $quality = $opts['quality'] ?? 80;
    $success = imagewebp($resized, $webpPath, $quality);
    imagedestroy($resized);

    if ($success && is_file($webpPath)) {
        $webpSize = filesize($webpPath);
        $saved = $originalSize - $webpSize;
        $totalSaved += $saved;
        $converted++;
        $savedKB = round($saved / 1024);
        $origKB = round($originalSize / 1024);
        $webpKB = round($webpSize / 1024);
        echo "[OK]   $relPath: {$origKB}KB -> {$webpKB}KB (WebP, saved {$savedKB}KB, resized to {$newW}x{$newH})\n";
    } else {
        echo "[ERR]  $relPath - WebP conversion failed\n";
    }
}

// Also process uploads/ directory (service, project, blog images)
$uploadDirs = ['uploads/services', 'uploads/projects', 'uploads/courses', 'uploads/clients'];
foreach ($uploadDirs as $dir) {
    $fullDir = $baseDir . '/' . $dir;
    if (!is_dir($fullDir)) continue;

    $files = glob($fullDir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
    foreach ($files as $filePath) {
        $relPath = str_replace($baseDir . '/', '', $filePath);
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filePath);

        // Skip if WebP already exists
        if (is_file($webpPath)) continue;

        $originalSize = filesize($filePath);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $img = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($filePath);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($filePath);
        }
        if (!$img) continue;

        $origW = imagesx($img);
        $origH = imagesy($img);

        // Resize uploads to max 1200px
        $maxW = 1200;
        $newW = $origW;
        $newH = $origH;
        if ($origW > $maxW) {
            $ratio = $maxW / $origW;
            $newW = $maxW;
            $newH = (int)round($origH * $ratio);
        }

        $resized = imagecreatetruecolor($newW, $newH);
        if ($ext === 'png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($img);

        $success = imagewebp($resized, $webpPath, 80);
        imagedestroy($resized);

        if ($success && is_file($webpPath)) {
            $webpSize = filesize($webpPath);
            $saved = $originalSize - $webpSize;
            $totalSaved += $saved;
            $converted++;
            $savedKB = round($saved / 1024);
            $origKB = round($originalSize / 1024);
            $webpKB = round($webpSize / 1024);
            echo "[OK]   $relPath: {$origKB}KB -> {$webpKB}KB (saved {$savedKB}KB)\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Converted: $converted images\n";
echo "Total saved: " . round($totalSaved / 1024 / 1024, 2) . " MB\n";
echo "\nDone! WebP files are saved alongside originals.\n";
echo "Update your code to serve .webp when available.\n";
