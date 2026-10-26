<?php
// kiosk_bridge.py POSTs enroll progress here so the browser can show the
// right instruction. Body: {"token": "...", "step": "remove_finger"}
// Allowed steps: waiting_first_scan, remove_finger, waiting_second_scan
header('Content-Type: application/json');
require_once __DIR__ . '/fingerprint_state_functions.php';

fpRequireDeviceKey();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token = (string)($input['token'] ?? '');
$step  = (string)($input['step'] ?? '');

$allowed = ['waiting_first_scan', 'remove_finger', 'waiting_second_scan'];

$state = fpReadEnrollState();
if (!$state || $state['status'] !== 'waiting' || $token === '' || !hash_equals($state['token'], $token) || !in_array($step, $allowed, true)) {
    echo json_encode(['ok' => false, 'message' => 'Ignored']);
    exit;
}

$state['step'] = $step;
fpWriteEnrollState($state);
echo json_encode(['ok' => true]);
