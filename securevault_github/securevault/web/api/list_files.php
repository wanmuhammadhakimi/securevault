<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';

if ($role === 'admin') {
    // Admins see every file's METADATA (name, size, owner, date) so they can
    // manage storage and remove files — but they cannot decrypt/download
    // files owned by other users (enforced in download.php). is_owner tells
    // the dashboard whether to show the Download button.
    $stmt = $pdo->prepare("SELECT f.file_id, f.original_filename, f.file_size, f.uploaded_at,
                                  u.username AS owner, (f.user_id = ?) AS is_owner
                             FROM files f
                             JOIN users u ON u.user_id = f.user_id
                            ORDER BY f.uploaded_at DESC");
    $stmt->execute([$userId]);
} else {
    // Regular users only see their own files
    $stmt = $pdo->prepare("SELECT file_id, original_filename, file_size, uploaded_at, 1 AS is_owner
                             FROM files
                            WHERE user_id = ?
                            ORDER BY uploaded_at DESC");
    $stmt->execute([$userId]);
}

echo json_encode(['success' => true, 'files' => $stmt->fetchAll()]);
