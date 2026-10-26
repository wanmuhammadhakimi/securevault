<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file received or upload error']);
    exit;
}

$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$originalName = basename($_FILES['avatar']['name']);
$tmpPath = $_FILES['avatar']['tmp_name'];
$fileSize = $_FILES['avatar']['size'];

// 2MB cap — plenty for a profile picture
if ($fileSize > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Image too large (max 2MB)']);
    exit;
}

// ── Validate it's actually an image (checks real content, not just the
// filename extension) — getimagesize() reads the file header and fails on
// anything that isn't a genuine image, which also blocks a disguised
// executable renamed to "photo.jpg".
$imageInfo = @getimagesize($tmpPath);
if ($imageInfo === false) {
    echo json_encode(['success' => false, 'message' => 'File is not a valid image']);
    exit;
}

$allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
if (!in_array($imageInfo[2], $allowedTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF or WEBP images are allowed']);
    exit;
}

// ── Malware scan (ClamAV via clamd daemon) ──
// Same scanning approach as the main file vault upload (api/upload.php) —
// every upload entry point in the app goes through the same AV check, so a
// malicious file disguised as a profile picture can't skip the scan that
// vault files get.
$isWindows = (DIRECTORY_SEPARATOR === '\\');

if ($isWindows) {
    $clamdscanExe  = 'C:\\Program Files\\ClamAV\\clamdscan.exe';
    $clamdConfFile = 'C:\\Program Files\\ClamAV\\clamd.conf';
} else {
    $clamdscanExe  = '/usr/bin/clamdscan';
    $clamdConfFile = '/etc/clamav/clamd.conf';
}

if ($isWindows) {
    $scanTargetPath = $tmpPath . '.scan';
    $copyOk = @copy($tmpPath, $scanTargetPath);

    if (!$copyOk) {
        $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                        VALUES (?, 'ENDPOINT_BLOCKED', 'high', ?, 'UPLOAD_BLOCKED')")
            ->execute([$userId, "Avatar upload of '$originalName' blocked before it could be scanned — likely quarantined or locked by endpoint protection on arrival."]);

        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'UPLOAD_BLOCKED', ?, ?)")
            ->execute([$userId, "Avatar upload blocked by endpoint protection before antivirus scan could run: $originalName", $ip]);

        echo json_encode(['success' => false, 'message' => 'File rejected — blocked by endpoint protection before it could be scanned']);
        exit;
    }
} else {
    $scanTargetPath = $tmpPath;
}

$scanCmd = '"' . $clamdscanExe . '" --config-file="' . $clamdConfFile . '" ' . escapeshellarg($scanTargetPath) . ' 2>&1';
exec($scanCmd, $scanOutputLines, $scanReturnCode);
$scanOutput = implode(' ', $scanOutputLines);

if ($isWindows) {
    @unlink($scanTargetPath);
}

if ($scanReturnCode === 1) {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'MALWARE_DETECTED', 'high', ?, 'UPLOAD_BLOCKED')")
        ->execute([$userId, "Avatar upload of '$originalName' blocked — ClamAV detected a threat: $scanOutput"]);

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'UPLOAD_BLOCKED', ?, ?)")
        ->execute([$userId, "Malware detected and blocked in uploaded avatar: $originalName", $ip]);

    echo json_encode(['success' => false, 'message' => 'File rejected — malware detected by security scan']);
    exit;
}

if ($scanReturnCode !== 0) {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'AV_SCAN_UNAVAILABLE', 'medium', ?, 'UPLOAD_BLOCKED')")
        ->execute([$userId, "Avatar upload of '$originalName' blocked — antivirus scan service unreachable (exit code $scanReturnCode): $scanOutput"]);

    echo json_encode(['success' => false, 'message' => 'Security scan service unavailable — please try again shortly']);
    exit;
}
// $scanReturnCode === 0 → clean, continue to store

// ── Store the avatar (outside ENCRYPTED_DIR — not encrypted since it's
// displayed as an <img>, not a vault document; a random filename stops
// anyone guessing another user's avatar path)
$avatarDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

$extMap = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
$ext = $extMap[$imageInfo[2]];
$storedName = bin2hex(random_bytes(16)) . '.' . $ext;
$storedPath = $avatarDir . $storedName;

if (!move_uploaded_file($tmpPath, $storedPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save avatar']);
    exit;
}

// Delete old avatar file (if any) to avoid orphaned files piling up
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$oldAvatar = $stmt->fetchColumn();
if ($oldAvatar && file_exists($avatarDir . $oldAvatar)) {
    @unlink($avatarDir . $oldAvatar);
}

$pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?")
    ->execute([$storedName, $userId]);

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'PROFILE_PICTURE_UPDATED', 'User updated their profile picture', ?)")
    ->execute([$userId, $ip]);

echo json_encode([
    'success' => true,
    'message' => 'Profile picture updated',
    'filename' => $storedName
]);