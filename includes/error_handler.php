<?php
/**
 * Global Error Handler & Logger
 * Include file này ở đầu index.php để bắt tất cả lỗi.
 *
 * Chức năng:
 * - Chuyển PHP errors/warnings thành exceptions
 * - Log lỗi vào file thay vì hiển thị cho user
 * - Hiển thị trang lỗi thân thiện cho user
 */

/**
 * Ghi lỗi vào log file
 */
function app_log(string $level, string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Chuyển PHP error thành exception
 */
function app_error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    // Chỉ log, không throw cho các lỗi không nghiêm trọng
    $levels = [
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_STRICT => 'STRICT',
        E_DEPRECATED => 'DEPRECATED',
    ];

    $level = $levels[$errno] ?? 'ERROR';
    app_log($level, "{$errstr} in {$errfile}:{$errline}");

    // Throw exception cho lỗi nghiêm trọng
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    return true; // Đã xử lý
}

/**
 * Bắt uncaught exceptions
 */
function app_exception_handler(Throwable $e): void
{
    app_log('CRITICAL', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    // Hiển thị trang lỗi thân thiện
    $isProduction = (env('APP_ENV', 'local') === 'production');
    if (!$isProduction) {
        // Development: hiển thị chi tiết lỗi
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<h1 style="color:#c00;">' . htmlspecialchars($e->getMessage()) . '</h1>';
        echo '<p style="color:#666;">' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre style="background:#f5f5f5;padding:16px;overflow:auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        // Production: trang lỗi chung
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="vi"><head><title>Lỗi hệ thống</title>';
        echo '<style>body{font-family:sans-serif;background:#0f1718;color:#e5f1f2;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}</style>';
        echo '</head><body>';
        echo '<div style="text-align:center;padding:40px;">';
        echo '<h1 style="font-size:3rem;margin-bottom:16px;">500</h1>';
        echo '<p style="font-size:1.2rem;color:#a9b9bc;">Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.</p>';
        echo '<a href="/" style="color:#86d7df;margin-top:24px;display:inline-block;">Quay về trang chủ</a>';
        echo '</div></body></html>';
    }
}

/**
 * Bắt fatal errors (shutdown handler)
 */
function app_shutdown_handler(): void
{
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_log('FATAL', $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
        ]);

        // Chỉ hiển thị nếu chưa có output
        if (env('APP_ENV', 'local') !== 'production' && !headers_sent()) {
            http_response_code(500);
            echo '<h1 style="color:#c00;">Fatal Error</h1>';
            echo '<p>' . htmlspecialchars($error['message']) . '</p>';
        }
    }
}

// Đăng ký handlers
set_error_handler('app_error_handler');
set_exception_handler('app_exception_handler');
register_shutdown_function('app_shutdown_handler');
