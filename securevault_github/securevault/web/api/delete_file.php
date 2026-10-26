<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/crypto.php';
require_once __DIR__ . '/fingerprint_state_functions.php'; // fpConsumeActionGrant()
require_once __DIR__ . '/fp_policy.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$fileId = (int)($input['file_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'user';
$ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// FP_POLICY_V8: no deletes during the cooling-off period
if ($cdUntil = fpCooldownUntil($pdo, $userId)) {
    http_response_code(423);
    echo json_encode(['success' => false, 'message' => fpCooldownMessage($cdUntil)]);
    exit;
}

if (!$fileId) {
    echo json_encode(['success' => false, 'message' => 'Missing file_id']);
    exit;
}

// Fingerprint step-up — single-use, 30-second grant from fingerprint_verify_poll.php,
// bound to this exact file ("delete:<file_id>"), so a scan for one file
// cannot be reused to delete another.
if (!fpConsumeActionGrant('delete:' . $fileId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Fingerprint verification required before deleting']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE file_id = ?");
$stmt->execute([$fileId]);
$file = $stmt->fetch();

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

$isOwner = ((int)$file['user_id'] === $userId);

// Only the OWNER can delete a file. Admins manage accounts, not data: they
// cannot open, download or delete other users' files (a compromised admin
// account cannot wipe user data file by file).
if (!$isOwner) {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'UNAUTHORIZED_ACCESS', 'high', ?, 'ACCESS_DENIED')")
        ->execute([$userId, "Attempted to delete file_id $fileId owned by another user (role: $role)"]);
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ACCESS_DENIED', ?, ?)")
        ->execute([$userId, "Blocked delete of file_id $fileId (not owner, role: $role)", $ip]);

    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied: only the owner of this file can delete it']);
    exit;
}

$storedPath = ENCRYPTED_DIR . $file['stored_filename'];
if (file_exists($storedPath)) {
    unlink($storedPath);
}

$pdo->prepare("DELETE FROM files WHERE file_id = ?")->execute([$fileId]);

$details = "Deleted (fingerprint-verified): " . $file['original_filename'];
$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'FILE_DELETE', ?, ?)")
    ->execute([$userId, $details, $ip]);

echo json_encode(['success' => true, 'message' => 'File deleted']);
