<?php
// General config
// base_url ưu tiên từ env, fallback về localhost:8000
return [
    'base_url' => env('APP_BASE_URL', 'http://localhost:8000'),
    'app_env' => env('APP_ENV', 'local'),
    'app_debug' => env('APP_DEBUG', 'true') === 'true',
];
