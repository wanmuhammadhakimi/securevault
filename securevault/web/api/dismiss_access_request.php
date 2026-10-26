<?php
// Admin-only: dismiss (reject) a pending access request. Body: {"request_id": 3}
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$requestId = (int)($input['request_id'] ?? 0);
$adminId   = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE access_requests SET status = 'dismissed', handled_by = ?, handled_at = NOW()
                        WHERE request_id = ? AND status = 'pending'");
$stmt->execute([$adminId, $requestId]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already handled']); exit;
}

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ACCESS_REQUEST_DISMISSED', ?, ?)")
    ->execute([$adminId, "Dismissed access request #$requestId", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

echo json_encode(['success' => true]);
