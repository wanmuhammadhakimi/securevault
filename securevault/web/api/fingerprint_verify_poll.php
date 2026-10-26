<?php
// Step 2 of the step-up check: browser polls this (every ~1.5s) after
// fingerprint_verify_start.php. On a match, grants a short-lived,
// SINGLE-USE flag bound to the request's purpose (e.g. "download:42").
// download.php consumes it with fpConsumeActionGrant("download:<id>").
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // logActivity(), raiseIntrusionAlert()
require_once __DIR__ . '/fingerprint_state_functions.php';
require_once __DIR__ . '/intrusion_alert_functions.php'; // triggerAlert() — physical buzzer

const FP_VERIFIED_GRACE_SECONDS = 30; // how long the grant survives before it must be re-earned

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$expectedToken = $_SESSION['fp_verify_token'] ?? null;
if (!$expectedToken) {
    echo json_encode(['success' => false, 'status' => 'none', 'message' => 'No verification in progress']);
    exit;
}

$state = fpReadVerifyState();

if (!$state || !hash_equals($state['token'], $expectedToken)) {
    unset($_SESSION['fp_verify_token']);
    echo json_encode(['success' => false, 'status' => 'expired', 'message' => 'Verification timed out — please try again']);
    exit;
}

if ($state['status'] === 'waiting') {
    echo json_encode(['success' => true, 'status' => 'waiting', 'message' => 'Waiting for fingerprint scan…']);
    exit;
}

// Every other status is final — clear it so the sensor is free again.
fpClearVerifyState();
unset($_SESSION['fp_verify_token']);

$userId  = (int)$state['user_id'];
$purpose = (string)$state['purpose'];

if ($state['status'] === 'matched') {
    $_SESSION['fp_action_verified_until'] = time() + FP_VERIFIED_GRACE_SECONDS;
    $_SESSION['fp_action_purpose']        = $purpose;

    logActivity($pdo, $userId, 'FINGERPRINT_STEP_UP_OK', "Step-up fingerprint verification matched (score {$state['score']}, purpose: $purpose)", $ip);
    echo json_encode(['success' => true, 'status' => 'matched', 'message' => 'Verified']);
    exit;
}

if ($state['status'] === 'mismatch') {
    logActivity($pdo, $userId, 'FINGERPRINT_STEP_UP_FAIL', "Step-up fingerprint verification did not match this account (purpose: $purpose)", $ip);
    raiseIntrusionAlert($pdo, $userId, 'BIOMETRIC_MISMATCH', 'medium',
        "Step-up fingerprint check (purpose: $purpose) did not match the logged-in account's enrolled fingerprint", 'ACCESS_DENIED');

    // Brute-force protection: whoever is scanning already passed the PASSWORD
    // check, so repeated fingerprint mismatches suggest a stolen password.
    // Treat it like the password lockout: lock the whole account until an
    // administrator unlocks it, end this session, and sound the alarm.
    $fails = fpRecentMismatches($pdo, $userId);
    if ($fails >= FP_MAX_FAILS) {
        $pdo->prepare("UPDATE users SET locked_until = NOW() WHERE user_id = ?")->execute([$userId]);
        raiseIntrusionAlert($pdo, $userId, 'BIOMETRIC_BRUTE_FORCE', 'high',
            "$fails failed fingerprint scans within " . FP_FAIL_WINDOW_MIN . " minutes — account locked until an administrator unlocks it", 'ACCOUNT_LOCKED');
        logActivity($pdo, $userId, 'ACCOUNT_LOCKED', "Account locked after $fails failed fingerprint scans", $ip);
        triggerAlert("Fingerprint brute force on user_id $userId");

        $_SESSION = [];
        session_destroy();

        echo json_encode(['success' => false, 'status' => 'locked', 'account_locked' => true,
            'message' => 'Too many failed fingerprint scans — your account has been locked. An administrator must unlock it.']);
        exit;
    }

    $left = FP_MAX_FAILS - $fails;
    echo json_encode(['success' => false, 'status' => 'mismatch',
        'message' => "Fingerprint did not match your account ($left attempt(s) left before your account is locked)"]);
    exit;
}

if ($state['status'] === 'expired') {
    echo json_encode(['success' => false, 'status' => 'expired', 'message' => 'Verification timed out — please try again']);
    exit;
}

if ($state['status'] === 'error') {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $state['message'] ?? 'Fingerprint scan failed — please try again']);
    exit;
}

echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unexpected verification state']);
