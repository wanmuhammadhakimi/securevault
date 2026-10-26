<?php
// Starts a fingerprint enrollment. Bank-style rules (option A):
//
//  1. FIRST enrollment of an account is done by an ADMIN at the kiosk, after
//     checking the person's identity in person. No extra scan is needed:
//     a brand-new fingerprint on an account has no files to steal yet.
//  2. CHANGING an existing fingerprint always needs a fingerprint scan first:
//     - a user changing their own: scan the CURRENT fingerprint (proves ownership)
//     - an admin replacing someone else's: the ADMIN scans their own fingerprint,
//       and the account then enters a cooling-off period (downloads and deletes
//       paused) in case the admin was tricked.
//  So a stolen admin session alone can never swap the fingerprint on an
//  account that already has one.
//
// Body: {"target_user_id": 7}   (admins only; defaults to the caller)
// If the caller must scan first, the reply is
//   {"success": false, "need_verify": true, "purpose": "enroll:7"}
// and the browser runs the normal step-up scan for that purpose, then retries.
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

$actorId = (int)$_SESSION['user_id'];
$isAdmin = (($_SESSION['role'] ?? 'user') === 'admin');

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$targetId = (int)($input['target_user_id'] ?? 0);
if ($targetId <= 0) $targetId = $actorId;

if (!$isAdmin && $targetId !== $actorId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only change your own fingerprint']);
    exit;
}

$check = $pdo->prepare("SELECT user_id, locked_until FROM users WHERE user_id = ?");
$check->execute([$targetId]);
$target = $check->fetch();
if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
if (!empty($target['locked_until'])) {
    echo json_encode(['success' => false, 'message' => 'This account is locked. Unlock it before changing its fingerprint.']);
    exit;
}

$targetHas = fpUserHasTemplate($pdo, $targetId);
$actorHas  = fpUserHasTemplate($pdo, $actorId);

// Rule 1: a regular user can't create their first fingerprint on their own.
if (!$isAdmin && !$targetHas) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your first fingerprint is registered with an administrator at the SecureVault kiosk. Please see your administrator.']);
    exit;
}

// Changing an EXISTING fingerprint needs a scan from someone who has one.
$purpose = 'enroll:' . $targetId;
if ($targetHas) {
    if (!$actorHas) {
        echo json_encode(['success' => false, 'message' => 'Enroll your own fingerprint first. Replacing someone else\'s fingerprint needs your fingerprint as authorisation.']);
        exit;
    }
    if (!fpConsumeActionGrant($purpose)) {
        echo json_encode([
            'success'     => false,
            'need_verify' => true,
            'purpose'     => $purpose,
            'message'     => $targetId === $actorId
                ? 'Scan your current fingerprint to confirm it is you'
                : 'Scan YOUR fingerprint to authorise replacing this fingerprint',
        ]);
        exit;
    }
}

if ($busy = fpSensorBusy()) {
    echo json_encode(['success' => false, 'message' => $busy]);
    exit;
}

$token = bin2hex(random_bytes(16));
fpStartEnroll($token, $targetId);
$state = fpReadEnrollState();
$state['actor_id']     = $actorId;
$state['had_template'] = $targetHas;
// Admin replacing someone else's existing fingerprint → cooling-off afterwards.
$state['assisted']     = ($targetId !== $actorId) && $targetHas;
fpWriteEnrollState($state);
$_SESSION['fp_enroll_token'] = $token;

echo json_encode([
    'success' => true,
    'message' => 'Place the finger on the sensor twice when prompted',
    'token'   => $token,
    'cooling_off_after' => $state['assisted'] ? FP_REENROLL_COOLDOWN_MINUTES : 0,
]);
