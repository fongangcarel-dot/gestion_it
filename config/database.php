<?php

declare(strict_types=1);

$settings = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'gestion_it',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'port' => getenv('DB_PORT') ?: '3306',
];

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    $settings = array_replace($settings, require $localConfig);
}

$host = $settings['host'];
$dbname = $settings['dbname'];
$username = $settings['username'];
$password = $settings['password'];
$port = $settings['port'];

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    throw new RuntimeException('Database connection failed.');
}

return $pdo;