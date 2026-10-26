<?php
// Public endpoint — a user who forgot their password submits their
// username here. This does NOT reset anything by itself; it just flags
// the account as "reset requested" so an admin can review and approve it
// via Access Control. No email/SMS infrastructure required, and no
// account existence is confirmed either way (avoids username enumeration).
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // for logActivity()

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($username === '') {
    echo json_encode(['success' => false, 'message' => 'Enter your username']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Same response whether the account exists or not — don't reveal which
// usernames are registered.
$genericResponse = ['success' => true, 'message' => 'If that account exists, a reset request has been logged for an administrator to review.'];

if (!$user) {
    echo json_encode($genericResponse);
    exit;
}

$pdo->prepare("UPDATE users SET reset_requested = NOW() WHERE user_id = ?")->execute([$user['user_id']]);
logActivity($pdo, $user['user_id'], 'PASSWORD_RESET_REQUESTED', "Password reset requested from IP $ip", $ip);

echo json_encode($genericResponse);