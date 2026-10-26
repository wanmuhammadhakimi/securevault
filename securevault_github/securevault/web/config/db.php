<?php
// Database connection (PDO). Credentials come from the web server environment
// (e.g. SetEnv in an Apache conf readable only by root) - never commit them.
$host    = getenv('SV_DB_HOST') ?: 'localhost';
$db      = getenv('SV_DB_NAME') ?: 'secure_file_storage';
$user    = getenv('SV_DB_USER') ?: 'securevault';
$pass    = getenv('SV_DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log('SecureVault DB: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
