<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/crypto.php';
require_once __DIR__ . '/intrusion_alert_functions.php'; // for triggerAlert()

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (empty($_SESSION['biometric_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Biometric verification required before uploading files']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file received or upload error']);
    exit;
}

$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$originalName = basename($_FILES['file']['name']);
$tmpPath = $_FILES['file']['tmp_name'];
$fileSize = $_FILES['file']['size'];

// 10MB cap for demo purposes — adjust as needed
if ($fileSize > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 10MB for demo)']);
    exit;
}

// ── Malware scan (ClamAV via clamd daemon) ──
// Runs BEFORE the file is ever read into memory or encrypted. clamd must
// already be running — this talks to that running daemon via clamdscan,
// which is fast because the daemon keeps the virus signature database
// preloaded in memory.
//
// Paths auto-switch between Windows (local XAMPP dev) and Linux (production
// server, e.g. a VPS after `sudo apt install clamav-daemon`). On Linux,
// clamd runs as a proper system service under its own clamav user and can
// read any world-readable file, so no copy-before-scan trick is needed
// there — only on Windows (where clamd runs interactively under your own
// user account) does Apache's own tmp upload file sometimes fail to open
// directly, which is why we copy it first in that case.
$isWindows = (DIRECTORY_SEPARATOR === '\\');

if ($isWindows) {
    $clamdscanExe  = 'C:\\Program Files\\ClamAV\\clamdscan.exe';
    $clamdConfFile = 'C:\\Program Files\\ClamAV\\clamd.conf';
} else {
    // Typical Debian/Ubuntu paths after `sudo apt install clamav-daemon`.
    // Adjust if your distro/install puts them elsewhere.
    $clamdscanExe  = '/usr/bin/clamdscan';
    $clamdConfFile = '/etc/clamav/clamd.conf';
}

if ($isWindows) {
    $scanTargetPath = $tmpPath . '.scan';
    // @ suppresses the PHP Warning on failure — an uncaught Warning prints
    // raw HTML into the response BEFORE our json_encode(), which corrupts
    // the JSON and stops the frontend from showing any message at all. We
    // check the failure ourselves right after instead.
    $copyOk = @copy($tmpPath, $scanTargetPath);

    if (!$copyOk) {
        // This almost always means endpoint protection (Windows Defender or
        // similar) already quarantined/locked the file the instant it hit
        // disk — usually because it recognized it as a threat. Since we
        // can't be 100% sure it was a real threat vs. some other I/O issue,
        // we still log it as a security event rather than staying silent,
        // so every blocked upload leaves a trail for the admin to review.
        $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                        VALUES (?, 'ENDPOINT_BLOCKED', 'high', ?, 'UPLOAD_BLOCKED')")
            ->execute([$userId, "Upload of '$originalName' blocked before it could be scanned — the file was likely quarantined or locked by endpoint protection (e.g. Windows Defender) on arrival, which usually means it was flagged as a threat."]);

        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'UPLOAD_BLOCKED', ?, ?)")
            ->execute([$userId, "Upload blocked by endpoint protection before antivirus scan could run: $originalName", $ip]);

        triggerAlert("Upload blocked by endpoint protection: '$originalName' (likely threat)");

        echo json_encode(['success' => false, 'message' => 'File rejected — blocked by endpoint protection before it could be scanned']);
        exit;
    }
} else {
    // Linux: clamd can read the tmp file directly, no copy needed.
    $scanTargetPath = $tmpPath;
}

$scanCmd = '"' . $clamdscanExe . '" --config-file="' . $clamdConfFile . '" ' . escapeshellarg($scanTargetPath) . ' 2>&1';
exec($scanCmd, $scanOutputLines, $scanReturnCode);
$scanOutput = implode(' ', $scanOutputLines);

if ($isWindows) {
    @unlink($scanTargetPath); // scan copy no longer needed either way
}

// clamdscan exit codes: 0 = clean, 1 = virus found, 2 = scan error
if ($scanReturnCode === 1) {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'MALWARE_DETECTED', 'high', ?, 'UPLOAD_BLOCKED')")
        ->execute([$userId, "Upload of '$originalName' blocked — ClamAV detected a threat: $scanOutput"]);

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'UPLOAD_BLOCKED', ?, ?)")
        ->execute([$userId, "Malware detected and blocked in uploaded file: $originalName", $ip]);

    triggerAlert("Malware detected: '$originalName' ($scanOutput)");

    echo json_encode(['success' => false, 'message' => 'File rejected — malware detected by security scan']);
    exit;
}

if ($scanReturnCode !== 0) {
    // Scan couldn't run at all (clamd not running, path wrong, etc.) — fail
    // CLOSED rather than silently skipping the scan. An unscanned upload is
    // treated the same as a risky one for this system's threat model.
    // (Not a confirmed threat, so this one does NOT sound the buzzer —
    // just a service availability issue, logged for the admin.)
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, 'AV_SCAN_UNAVAILABLE', 'medium', ?, 'UPLOAD_BLOCKED')")
        ->execute([$userId, "Upload of '$originalName' blocked — antivirus scan service unreachable (exit code $scanReturnCode): $scanOutput"]);

    echo json_encode(['success' => false, 'message' => 'Security scan service unavailable — please try again shortly, or contact an administrator']);
    exit;
}
// $scanReturnCode === 0 → clean, continue to encrypt + store as normal

$rawData = file_get_contents($tmpPath);
if ($rawData === false) {
    echo json_encode(['success' => false, 'message' => 'Could not read uploaded file']);
    exit;
}

[$cipherText, $ivHex] = encryptData($rawData);

if (!is_dir(ENCRYPTED_DIR)) {
    mkdir(ENCRYPTED_DIR, 0755, true);
}

$storedName = generateStoredFilename();
$storedPath = ENCRYPTED_DIR . $storedName;

if (file_put_contents($storedPath, $cipherText) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to write encrypted file to disk']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO files (user_id, original_filename, stored_filename, file_size, iv) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $originalName, $storedName, $fileSize, $ivHex]);
$fileId = $pdo->lastInsertId();

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'FILE_UPLOAD', ?, ?)")
    ->execute([$userId, "Encrypted and stored: $originalName", $ip]);

echo json_encode([
    'success' => true,
    'message' => 'File encrypted and stored',
    'file_id' => $fileId,
    'filename' => $originalName,
    'size' => $fileSize
]);