<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/crypto.php';
require_once __DIR__ . '/fingerprint_state_functions.php';
require_once __DIR__ . '/fp_policy.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    die('Not logged in');
}

$fileId = $_GET['file_id'] ?? null;

// Fingerprint is required as a STEP-UP check right before a download — the
// browser must have just completed fingerprint_verify_start.php (purpose
// "download:<file_id>") + fingerprint_verify_poll.php within the last 30
// seconds. The grant is single-use AND bound to this exact file, so a scan
// for one file cannot be reused to download another.
if (!fpConsumeActionGrant('download:' . (int)$fileId)) {
    http_response_code(403);
    die('Fingerprint verification required before downloading');
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// FP_POLICY_V8: no downloads during the cooling-off period
if ($cdUntil = fpCooldownUntil($pdo, (int)$userId)) {
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ACCESS_DENIED', ?, ?)")
        ->execute([$userId, "Download of file_id $fileId blocked: fingerprint cooling-off until $cdUntil", $ip]);
    http_response_code(423);
    die(fpCooldownMessage($cdUntil));
}

if (!$fileId) {
    http_response_code(400);
    die('Missing file_id');
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE file_id = ?");
$stmt->execute([$fileId]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('File not found');
}

// Ownership check — only the OWNER can decrypt and download a file.
// Admins manage accounts and can see file metadata (list_files.php), but
// cannot read other users' files: least privilege for hospital/bank data.
$isOwner = ((int)$file['user_id'] === (int)$userId);

if (!$isOwner) {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'UNAUTHORIZED_ACCESS', 'high', ?, 'ACCESS_DENIED')")
        ->execute([$userId, "Attempted to download file_id $fileId owned by another user (role: $role)"]);
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ACCESS_DENIED', ?, ?)")
        ->execute([$userId, "Blocked download of file_id $fileId (not owner, role: $role)", $ip]);

    http_response_code(403);
    die('Access denied — only the owner of this file can download it');
}

$storedPath = ENCRYPTED_DIR . $file['stored_filename'];
if (!file_exists($storedPath)) {
    http_response_code(404);
    die('Encrypted file missing from disk');
}

$cipherText = file_get_contents($storedPath);
$plainData = decryptData($cipherText, $file['iv']);

if ($plainData === false) {
    if (($GLOBALS['SV_CRYPTO_ERROR'] ?? '') === 'tampered') {
        // The stored file no longer matches its HMAC tag: it was modified on disk.
        $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                        VALUES (?, 'FILE_TAMPERED', 'high', ?, 'DOWNLOAD_BLOCKED')")
            ->execute([$userId, "Integrity check failed for file_id $fileId ({$file['original_filename']}): the encrypted file was modified on disk"]);
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'FILE_TAMPERED', ?, ?)")
            ->execute([$userId, "Download blocked, integrity check failed: " . $file['original_filename'], $ip]);
        @include_once __DIR__ . '/intrusion_alert_functions.php';
        if (function_exists('triggerAlert')) triggerAlert("File integrity check failed (file_id $fileId)");
        http_response_code(409);
        die('This file failed its integrity check and may have been tampered with. The download was blocked and an administrator has been alerted.');
    }
    http_response_code(500);
    die('Decryption failed');
}

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'FILE_DOWNLOAD', ?, ?)")
    ->execute([$userId, "Decrypted and served (fingerprint-verified): " . $file['original_filename'], $ip]);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . strlen($plainData));
echo $plainData;