<?php
// kiosk_bridge.py POSTs the result of an ENROLL job here. Body:
//   {"token": "...", "ok": true,  "template": "<hex, 768 bytes>"}
//   {"token": "...", "ok": false, "message": "ENROLL_MISMATCH"}
//
// On success the template is encrypted (AES-256-GCM) and saved straight into
// users.fp_template — it is never written to disk in plaintext and never
// stored on the sensor.
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // logActivity()
require_once __DIR__ . '/fingerprint_state_functions.php';
require_once __DIR__ . '/fp_policy.php';

fpRequireDeviceKey();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token = (string)($input['token'] ?? '');

$state = fpReadEnrollState();
if (!$state || $state['status'] !== 'waiting' || $token === '' || !hash_equals($state['token'], $token)) {
    echo json_encode(['ok' => false, 'message' => 'No matching enrollment pending — report ignored']);
    exit;
}

$userId = (int)$state['user_id'];

if (empty($input['ok'])) {
    $reason = strtoupper((string)($input['message'] ?? ''));
    $state['status']  = 'fail';
    $state['message'] = $reason === 'ENROLL_MISMATCH'
        ? 'The two scans did not match — please use the same finger both times'
        : 'Enrollment failed — please try again';
    fpWriteEnrollState($state);
    echo json_encode(['ok' => true, 'result' => 'fail']);
    exit;
}

$hex = (string)($input['template'] ?? '');
$template = (ctype_xdigit($hex) && strlen($hex) % 2 === 0) ? hex2bin($hex) : false;

if ($template === false || strlen($template) !== FP_TEMPLATE_SIZE) {
    $state['status']  = 'fail';
    $state['message'] = 'Sensor returned an invalid fingerprint template — please try again';
    fpWriteEnrollState($state);
    echo json_encode(['ok' => false, 'message' => 'Invalid template']);
    exit;
}

fpSaveTemplate($pdo, $userId, $template);
$actorId  = (int)($state['actor_id'] ?? $userId);
$kioskIp  = $_SERVER['REMOTE_ADDR'] ?? 'kiosk';
if (!empty($state['assisted'])) {
    // FP_POLICY_V8: admin replaced an existing fingerprint → cooling-off period
    $mins = (int)FP_REENROLL_COOLDOWN_MINUTES;
    $pdo->prepare("UPDATE users SET fp_cooldown_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE user_id = ?")->execute([$mins, $userId]);
    logActivity($pdo, $userId, 'FINGERPRINT_REENROLLED', "Fingerprint replaced by admin user_id $actorId at the kiosk. Downloads and deletes paused for $mins minutes (cooling-off).", $kioskIp);
    raiseIntrusionAlert($pdo, $userId, 'FINGERPRINT_CHANGED', 'medium',
        "Fingerprint for user_id $userId was replaced by admin user_id $actorId. Cooling-off of $mins minutes started.", 'COOLING_OFF');
} elseif (!empty($state['had_template'])) {
    logActivity($pdo, $userId, 'FINGERPRINT_REENROLLED', 'Owner changed their own fingerprint after verifying the previous one', $kioskIp);
} else {
    logActivity($pdo, $userId, 'BIOMETRIC_ENROLLED', "First fingerprint enrolled at the kiosk (authorised by user_id $actorId), template stored encrypted", $kioskIp);
}

$state['status'] = 'success';
fpWriteEnrollState($state);
echo json_encode(['ok' => true, 'result' => 'success']);
