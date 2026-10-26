<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'LOGOUT', 'Session ended', ?)")
        ->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
}

session_unset();
session_destroy();

echo json_encode(['success' => true]);