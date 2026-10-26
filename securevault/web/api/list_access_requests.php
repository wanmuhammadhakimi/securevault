<?php
// Admin-only: pending access requests for the Access Control page.
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']); exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']); exit;
}

$rows = $pdo->query("SELECT request_id, full_name, username, email, note, created_at
                       FROM access_requests WHERE status = 'pending'
                      ORDER BY created_at ASC")->fetchAll();
echo json_encode(['success' => true, 'requests' => $rows]);
