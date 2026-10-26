<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';
$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'PASSWORD_CHANGE_FAILED', 'Incorrect current password provided', ?)")
        ->execute([$userId, $ip]);

    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Prevent "changing" to the exact same password (not a security requirement,
// just avoids a pointless log entry / false sense of having rotated it)
if (password_verify($newPassword, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from the current one']);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
// Clearing must_change_password here is what lifts the forced-reset gate —
// this is the ONLY endpoint that can clear it, since it's the only one
// that proves the person knows the current (possibly temporary) password.
$pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?")
    ->execute([$newHash, $userId]);

// Keep the session flag in sync so requireFullAuth()'s check (used by
// every other protected endpoint) stops blocking this account immediately,
// without waiting for a fresh login.
$_SESSION['must_change_password'] = false;

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'PASSWORD_CHANGED', 'User changed their own password', ?)")
    ->execute([$userId, $ip]);

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);