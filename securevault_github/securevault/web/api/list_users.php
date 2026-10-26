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

$stmt = $pdo->query("SELECT user_id, username, full_name, email, role, biometric_enrolled, fingerprint_slot_id, failed_attempts, locked_until, reset_requested, account_status
                      FROM users
                      ORDER BY user_id ASC");
$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'users' => $users]);