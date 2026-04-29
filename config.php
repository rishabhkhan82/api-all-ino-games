<?php

// Database configuration constants (old local settings)
// define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
// define('DB_PORT', getenv('DB_PORT') ?: 3307);
// define('DB_NAME', getenv('DB_NAME') ?: 'orange_city_games');
// define('DB_USER', getenv('DB_USER') ?: 'root');
// define('DB_PASS', getenv('DB_PASS') ?: '');

// InfinityFree database credentials
define('DB_HOST', 'sql304.infinityfree.com');
define('DB_PORT', 3306);
define('DB_NAME', 'if0_41776473_all_ino_games_db');
define('DB_USER', 'if0_41776473');
define('DB_PASS', 'GweoD2kaHwrG');
define('DB_CHARSET', 'utf8mb4');

// JWT configuration
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-jwt-secret-key-here');

// Application configuration
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ?: true);

/**
 * Get database connection
 *
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    return $pdo;
}

/**
 * Get configuration value
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getConfig(string $key, $default = null) {
    static $config;

    if ($config === null) {
        $config = [
            'app' => [
                'name' => 'Orange City Games API',
                'version' => '1.0.0',
                'env' => getenv('APP_ENV') ?: 'development',
                'debug' => getenv('APP_DEBUG') ?: true,
                'timezone' => 'Asia/Kolkata',
                'url' => getenv('APP_URL') ?: 'http://localhost',
                'key' => getenv('APP_KEY') ?: 'base64:your-app-key-here',
            ],

            'database' => [
                'driver' => 'mysql',
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => getenv('DB_PORT') ?: 3307,
                'database' => getenv('DB_NAME') ?: 'orange_city_games',
                'username' => getenv('DB_USER') ?: 'root',
                'password' => getenv('DB_PASS') ?: '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],

            'jwt' => [
                'secret' => getenv('JWT_SECRET') ?: 'your-jwt-secret-key-here',
                'ttl' => 3600, // 1 hour
                'refresh_ttl' => 604800, // 7 days
                'algorithm' => 'HS256',
            ],

            'cors' => [
                'allowed_origins' => ['http://localhost:4200', 'http://localhost:3000', 'http://localhost'],
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'exposed_headers' => ['Authorization'],
                'max_age' => 86400,
            ],

            'rate_limiting' => [
                'enabled' => true,
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],

            'logging' => [
                'default' => 'stack',
                'channels' => [
                    'stack' => [
                        'driver' => 'stack',
                        'channels' => ['single'],
                    ],
                    'single' => [
                        'driver' => 'single',
                        'path' => __DIR__ . '/logs/app.log',
                        'level' => 'debug',
                    ],
                    'daily' => [
                        'driver' => 'daily',
                        'path' => __DIR__ . '/logs/app.log',
                        'level' => 'debug',
                        'days' => 14,
                    ],
                ],
            ],

            'cache' => [
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => __DIR__ . '/../storage/cache',
                    ],
                    'redis' => [
                        'driver' => 'redis',
                        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                        'port' => getenv('REDIS_PORT') ?: 6379,
                        'password' => getenv('REDIS_PASSWORD'),
                        'database' => getenv('REDIS_DB') ?: 0,
                    ],
                ],
                'ttl' => 3600, // 1 hour
            ],
        ];
    }

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }

    return $value;
}