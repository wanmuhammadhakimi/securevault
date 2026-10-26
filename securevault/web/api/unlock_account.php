<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = (int)($input['user_id'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$check = $pdo->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
$check->execute([$targetUserId]);
$target = $check->fetch();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?")
    ->execute([$targetUserId]);

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ACCOUNT_UNLOCKED', ?, ?)")
    ->execute([
        $_SESSION['user_id'],
        "Admin unlocked account: {$target['username']}",
        $ip
    ]);

echo json_encode(['success' => true, 'message' => 'Account unlocked']);