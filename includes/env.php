<?php
/**
 * Load environment variables from .env file
 * Priority: .env file < environment variables (env vars always win)
 */

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comments
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Parse KEY=VALUE
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Remove surrounding quotes
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[strlen($value) - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already in environment (env vars take priority)
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

// Auto-load .env from project root
load_env(__DIR__ . '/../.env');

/**
 * Get environment variable with fallback
 */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}
