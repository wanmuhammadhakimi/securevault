<?php
// Admin-only: approves a pending password reset request by generating a
// random temporary password. The admin must relay this to the user
// through a separately verified channel (in person, internal messaging
// with identity already confirmed, etc.) — never store or email it in
// plaintext from this system. The user is forced to set their own new
// password on next login (must_change_password flag).
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // for logActivity()

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
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

$stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
$stmt->execute([$targetUserId]);
$target = $stmt->fetch();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Generate a random temporary password — readable enough to relay verbally
// or via a secure channel, but not guessable.
$tempPassword = bin2hex(random_bytes(5)); // 10 hex characters
$hash = password_hash($tempPassword, PASSWORD_DEFAULT);

$pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1, reset_requested = NULL, failed_attempts = 0, locked_until = NULL WHERE user_id = ?")
    ->execute([$hash, $targetUserId]);

logActivity($pdo, $_SESSION['user_id'], 'PASSWORD_RESET_APPROVED', "Admin reset password for '{$target['username']}' (user must change on next login)", $ip);

echo json_encode([
    'success' => true,
    'message' => 'Temporary password generated. Relay it to the user through a verified channel — it will not be shown again.',
    'temp_password' => $tempPassword,
    'username' => $target['username']
]);