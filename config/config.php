<?php

declare(strict_types=1);

/**
 * Application Configuration
 * 
 * Central configuration file for the application
 */

// manual env parser
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

return [
    'app' => [
        'name' => 'Royal Beverages API',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'timezone' => 'Asia/Colombo',
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? 6432),
        'name' => $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'royal-liquor',
        'user' => $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'postgres',
        'pass' => $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? '',
        'charset' => 'utf8',
    ],

    'cache' => [
        'enabled' => filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'path' => __DIR__ . '/../storage/cache',
        'default_ttl' => 3600, // 1 hour
    ],

    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug', // debug, info
        'max_files' => 30, 
    ],

    'security' => [
        'csrf_enabled' => true,
        'csrf_secret' => $_ENV['CSRF_SECRET'] ?? '',
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
        'jwt_expiry' => 7200, // 2 hours
        'password_algo' => PASSWORD_BCRYPT,
        'password_cost' => 12,
        'oauth' => [
            'google_client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'google_client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'google_redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
            'frontend_success_url' => $_ENV['FRONTEND_OAUTH_SUCCESS_URL'] ?? '/myaccount',
        ],
    ],

    'rate_limit' => [
        'enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'max_requests' => 100,
        'window_seconds' => 3600, // 1 hour
        'storage_path' => __DIR__ . '/../storage/rate_limits',
    ],

    'session' => [
        'lifetime' => 7200, // 2 hours
        'name' => 'ROYAL_SESSION',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['http://localhost:3000', 'http://localhost:8000'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
        'allow_credentials' => true,
        'max_age' => 3600,
    ],

    'pagination' => [
        'default_limit' => 50,
        'max_limit' => 100,
    ],
    'urls' => [
        'app' => $_ENV['APP_URL'] ?? 'http://localhost',
        'api' => $_ENV['API_URL'] ?? 'http://localhost/api/v1',
    ],
];
