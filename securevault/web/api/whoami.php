<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare("SELECT username, full_name, email, profile_picture, role, biometric_enrolled, fingerprint_slot_id FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode([
    'success'            => true,
    'user_id'            => $_SESSION['user_id'],
    'username'           => $user['username'],
    'full_name'          => $user['full_name'],
    'email'              => $user['email'],
    'profile_picture'    => $user['profile_picture'] ? 'uploads/avatars/' . $user['profile_picture'] : null,
    'role'               => $user['role'],
    // Derived from fingerprint_slot_id, NOT the raw biometric_enrolled column —
    // that column is a leftover flag from the old webcam-face era and can be
    // stuck at 1 for accounts that never actually enrolled a real AS608
    // fingerprint template. fingerprint_slot_id is the authoritative signal.
    'biometric_enrolled' => $user['fingerprint_slot_id'] !== null,
]);