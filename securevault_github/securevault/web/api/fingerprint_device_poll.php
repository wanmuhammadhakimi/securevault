<?php
// kiosk_bridge.py GETs this every ~1 second. Returns at most one job:
//   {"job": null}
//   {"job": {"type": "enroll", "token": "..."}}
//   {"job": {"type": "verify", "token": "...", "template": "<hex>"}}
//
// A job is handed out only ONCE (state 'claimed' flag), so a slow scan never
// gets started twice. The verify job carries the user's decrypted template,
// which is why this endpoint requires the device key.
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fingerprint_state_functions.php';

fpRequireDeviceKey();

$enroll = fpReadEnrollState();
if ($enroll && $enroll['status'] === 'waiting' && empty($enroll['claimed'])) {
    $enroll['claimed'] = true;
    fpWriteEnrollState($enroll);
    echo json_encode(['job' => ['type' => 'enroll', 'token' => $enroll['token']]]);
    exit;
}

$verify = fpReadVerifyState();
if ($verify && $verify['status'] === 'waiting' && empty($verify['claimed'])) {
    $template = fpLoadTemplate($pdo, (int)$verify['user_id']);

    if ($template === null) {
        // No template, or it failed to decrypt (wrong key / tampered row).
        $verify['status']  = 'error';
        $verify['message'] = 'Stored fingerprint could not be loaded — please ask an administrator to re-enroll';
        fpWriteVerifyState($verify);
        echo json_encode(['job' => null]);
        exit;
    }

    $verify['claimed'] = true;
    fpWriteVerifyState($verify);
    echo json_encode(['job' => [
        'type'     => 'verify',
        'token'    => $verify['token'],
        'template' => strtoupper(bin2hex($template)),
    ]]);
    exit;
}

echo json_encode(['job' => null]);
