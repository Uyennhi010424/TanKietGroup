<?php
header('Content-Type: text/plain; charset=utf-8');
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . "\n";
echo "PWD: " . getcwd() . "\n";
echo "FILES_EXIST: assets/css/style.css => " . (is_file(__DIR__ . '/assets/css/style.css') ? 'yes' : 'no') . "\n";
