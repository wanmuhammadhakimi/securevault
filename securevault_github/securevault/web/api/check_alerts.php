<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$role = $_SESSION['role'] ?? 'user';

try {
    if ($role === 'admin') {
        $count = $pdo->query("SELECT COUNT(*) FROM intrusion_alerts")->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM intrusion_alerts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $count = $stmt->fetchColumn();
    }

    echo json_encode(['success' => true, 'count' => (int)$count]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}