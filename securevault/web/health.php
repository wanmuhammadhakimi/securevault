<?php
// SecureVault health check — lets the public guest page (securevault.com.my)
// know whether the vault server is up before sending visitors to the login.
// Put this file in the SAME folder as app.php on the Pi.
// It reveals nothing sensitive: only {"status":"ok"} or {"status":"error"}.

$allowedOrigins = [
    'https://securevault.com.my',
    'https://www.securevault.com.my',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Content-Type: application/json');
header('Cache-Control: no-store');

try {
    // Also confirm the database answers, so "online" means users can actually log in.
    require_once __DIR__ . '/config/db.php';
    $pdo->query('SELECT 1');
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error']);
}