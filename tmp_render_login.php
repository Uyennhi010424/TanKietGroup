<?php
require __DIR__ . '/includes/site.php';
require __DIR__ . '/includes/security.php';
ob_start();
include __DIR__ . '/admin/login.php';
$html = ob_get_clean();
if (preg_match('~<img[^>]+src="([^"]+)"[^>]*alt="TanKiet Group"~i', $html, $m)) {
    echo $m[1], "\n";
} else {
    echo $html;
}
