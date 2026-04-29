<?php

require_once __DIR__ . '/../config.php';

// Create database if it doesn't exist
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '" . DB_NAME . "' created successfully!\n";

    // Connect to the database
    $pdo = getDBConnection();

    // Create tables
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }

    echo "✅ Tables created successfully!\n";
    echo "✅ Default admin user created (username: admin, password: admin)\n";
    echo "\n📝 You can now use the application!\n";
    echo "   Login credentials: admin / admin\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in config.php\n";
}