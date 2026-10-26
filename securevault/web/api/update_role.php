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
$newRole = strtolower(trim($input['role'] ?? ''));
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$allowedRoles = ['admin', 'user'];
if (!in_array($newRole, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Prevent an admin from accidentally demoting themselves and locking
// everyone out — require at least one other admin to remain, or just
// block self-demotion outright (simpler, safer for a small system).
if ($targetUserId === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
    exit;
}

$check = $pdo->prepare("SELECT user_id, username, role FROM users WHERE user_id = ?");
$check->execute([$targetUserId]);
$target = $check->fetch();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$newRole, $targetUserId]);

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ROLE_CHANGED', ?, ?)")
    ->execute([
        $_SESSION['user_id'],
        "Changed {$target['username']} role from {$target['role']} to $newRole",
        $ip
    ]);

echo json_encode(['success' => true, 'message' => 'Role updated']);