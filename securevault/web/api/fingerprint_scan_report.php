<?php
// kiosk_bridge.py POSTs the result of a VERIFY job here. Body:
//   {"token": "...", "result": "MATCH",   "score": 87}
//   {"token": "...", "result": "NOMATCH"}
//   {"token": "...", "result": "ERROR",   "message": "FINGER_TIMEOUT"}
//
// The 1:1 match itself happens inside the AS608 sensor (live finger vs the
// template the server sent). The server only accepts a result for the exact
// job token it handed out, and applies a minimum score.
header('Content-Type: application/json');
require_once __DIR__ . '/fingerprint_state_functions.php';

fpRequireDeviceKey();

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$token  = (string)($input['token'] ?? '');
$result = strtoupper((string)($input['result'] ?? ''));
$score  = (int)($input['score'] ?? 0);

$state = fpReadVerifyState();

if (!$state || $state['status'] !== 'waiting' || $token === '' || !hash_equals($state['token'], $token)) {
    echo json_encode(['ok' => false, 'message' => 'No matching verification pending — report ignored']);
    exit;
}

if ($result === 'MATCH' && $score >= FP_MIN_SCORE) {
    $state['status'] = 'matched';
    $state['score']  = $score;
} elseif ($result === 'MATCH' || $result === 'NOMATCH') {
    $state['status'] = 'mismatch';
    $state['score']  = $score;
} else {
    // Scanner problem (finger timeout, bad image…) — not an intrusion attempt.
    $state['status']  = 'error';
    $state['message'] = 'Fingerprint scan failed (' . substr(preg_replace('/[^A-Z0-9_ ]/', '', strtoupper((string)($input['message'] ?? 'unknown'))), 0, 60) . ') — please try again';
}

fpWriteVerifyState($state);
echo json_encode(['ok' => true, 'result' => $state['status']]);
