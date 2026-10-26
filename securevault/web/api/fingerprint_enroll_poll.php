<?php
// Browser polls this (every ~1.5s) after fingerprint_enroll_start.php,
// waiting for the kiosk to finish the 2-scan enroll sequence. The template
// itself is saved by fingerprint_enroll_report.php — this only reports status.
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/fingerprint_state_functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$expectedToken = $_SESSION['fp_enroll_token'] ?? null;
$state = fpReadEnrollState();

if (!$expectedToken || !$state || !hash_equals($state['token'], $expectedToken)) {
    echo json_encode(['success' => false, 'status' => 'none', 'message' => 'No enrollment in progress']);
    exit;
}

if ($state['status'] === 'waiting') {
    $messages = [
        'waiting_first_scan'  => 'Place your finger on the sensor…',
        'remove_finger'       => 'Lift your finger…',
        'waiting_second_scan' => 'Place the SAME finger again…',
    ];
    $step = $state['step'] ?? 'waiting_first_scan';
    echo json_encode([
        'success' => true,
        'status'  => 'waiting',
        'step'    => $step,
        'message' => $messages[$step] ?? 'Waiting for fingerprint scans…',
    ]);
    exit;
}

fpClearEnrollState();
unset($_SESSION['fp_enroll_token']);

if ($state['status'] === 'success') {
    // Enrolling live at the sensor already proves physical presence for
    // this session — no need to immediately re-verify right after enrolling.
    if ((int)$state['user_id'] === (int)$_SESSION['user_id']) {
        $_SESSION['biometric_verified'] = true;
    }
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Fingerprint enrolled']);
    exit;
}

if ($state['status'] === 'expired') {
    echo json_encode(['success' => false, 'status' => 'expired', 'message' => 'Enrollment timed out — please try again']);
    exit;
}

if ($state['status'] === 'fail') {
    echo json_encode(['success' => false, 'status' => 'fail', 'message' => $state['message'] ?? 'Enrollment failed — please try again']);
    exit;
}

echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unexpected enrollment state']);
