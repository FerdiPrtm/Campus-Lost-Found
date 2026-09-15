<?php
// config/config.php - central configuration

// Minimal .env loader (no dependencies). Skips lines without '=',
// ignores comments, never overrides real environment variables.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'name'     => getenv('DB_NAME') ?: 'campus_lost_found',
        'user'     => getenv('DB_USER') ?: 'root',
        'pass'     => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
    'uploads' => [
        'dir'     => dirname(__DIR__) . '/uploads',
        'url'     => '/uploads',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed' => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
    'session_name' => 'clf_session',
    'cors_origin' => getenv('CORS_ORIGIN') ?: 'http://localhost:5173',
];