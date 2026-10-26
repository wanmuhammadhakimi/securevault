<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fullName = trim($input['full_name'] ?? '');
$email = trim($input['email'] ?? '');
$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($fullName === '') {
    echo json_encode(['success' => false, 'message' => 'Full name cannot be empty']);
    exit;
}

if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'Email cannot be empty']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Make sure no OTHER user already has this email (exclude the current user's own row)
$check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$check->execute([$email, $userId]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This email is already used by another account']);
    exit;
}

// Fetch current email so we can note in the log whether it actually changed
$current = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
$current->execute([$userId]);
$oldEmail = $current->fetchColumn();

$pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?")
    ->execute([$fullName, $email, $userId]);

$details = "Full name changed to: $fullName";
if ($oldEmail !== $email) {
    $details .= " | Email changed to: $email";
}

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'PROFILE_UPDATED', ?, ?)")
    ->execute([$userId, $details, $ip]);

echo json_encode(['success' => true, 'message' => 'Profile updated']);