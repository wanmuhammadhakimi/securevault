<?php
// Step 1 of a fingerprint STEP-UP check: the browser calls this right
// before a sensitive action for a user who is already fully logged in.
// It always checks the CURRENT session's own account.
//
// Body: {"purpose": "download:42"}  (or "delete:28", …)
// The grant earned by a successful scan is bound to this exact purpose, so
// download.php only accepts it for that same file.
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fingerprint_state_functions.php';
require_once __DIR__ . '/fp_policy.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$purpose = trim((string)($input['purpose'] ?? 'sensitive_action'));
if (!preg_match('/^[a-z_]{1,20}(:[0-9]{1,10})?$/', $purpose)) {
    echo json_encode(['success' => false, 'message' => 'Invalid purpose']);
    exit;
}

// An account locked by the password or fingerprint brute-force protection
// must not continue in an existing session either.
$lk = $pdo->prepare("SELECT locked_until FROM users WHERE user_id = ?");
$lk->execute([$userId]);
if ($lk->fetchColumn()) {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success' => false, 'account_locked' => true,
        'message' => 'Your account is locked due to suspicious activity. Contact an administrator to unlock it.']);
    exit;
}

// FP_POLICY_V8: cooling-off after an admin-assisted fingerprint change
if (preg_match('/^(download|delete):/', $purpose) && ($cdUntil = fpCooldownUntil($pdo, $userId))) {
    echo json_encode(['success' => false, 'cooling_off' => true, 'message' => fpCooldownMessage($cdUntil)]);
    exit;
}

if (!fpUserHasTemplate($pdo, $userId)) {
    echo json_encode(['success' => false, 'message' => 'No fingerprint enrolled on this account — an administrator must enroll one before this action is available']);
    exit;
}

// Single physical sensor — one enroll/verify at a time.
if ($busy = fpSensorBusy()) {
    echo json_encode(['success' => false, 'message' => $busy]);
    exit;
}

$token = bin2hex(random_bytes(16));
fpStartVerify($token, $userId, $purpose);
$_SESSION['fp_verify_token'] = $token;

echo json_encode(['success' => true, 'message' => 'Place your finger on the sensor to confirm', 'token' => $token]);
