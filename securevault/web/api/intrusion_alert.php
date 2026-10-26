<?php
/**
 * SecureVault — Intrusion Alert Endpoint (buzzer flag)
 *
 * Callers:
 *   - ESP32 buzzer: sends header  X-Device-Key: <BUZZER_DEVICE_KEY>
 *   - Admin dashboard (logged-in admin session): banner + "Silence Alarm"
 * Anyone else gets 401. The alert itself is raised server-side by calling
 * triggerAlert() (login lockout, malware upload, fingerprint brute force).
 *
 * Actions (?action=):
 *   status  -> current flag (ESP32 polls this; dashboard banner)
 *   ack     -> clear the flag (ESP32 stop button; dashboard Silence Alarm)
 *   trigger -> admin only, manual test: ?action=trigger&reason=test
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/intrusion_alert_functions.php';

// Separate key from the fingerprint kiosk key: it is stored in the ESP32
// firmware, so it must not unlock anything except the buzzer flag.
$buzzerKey = getenv('BUZZER_DEVICE_KEY') ?: '';
$givenKey  = $_SERVER['HTTP_X_DEVICE_KEY'] ?? '';
$isDevice  = ($buzzerKey !== '' && is_string($givenKey) && hash_equals($buzzerKey, $givenKey));
$isAdmin   = (!empty($_SESSION['password_verified']) && ($_SESSION['role'] ?? '') === 'admin');

if (!$isDevice && !$isAdmin) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'success' => false, 'message' => 'Not authorised']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'status':
        echo json_encode(readAlertState());
        break;

    case 'ack':
        writeAlertState(['active' => false, 'reason' => null, 'triggered_at' => null]);
        echo json_encode(['ok' => true, 'success' => true, 'message' => 'Alert acknowledged & cleared']);
        break;

    case 'trigger':
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'success' => false, 'message' => 'Admin only']);
            break;
        }
        $reason = substr((string)($_GET['reason'] ?? 'manual test'), 0, 100);
        triggerAlert($reason);
        echo json_encode(['ok' => true, 'success' => true, 'message' => 'Alert flag set']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'success' => false, 'message' => 'Unknown action. Use ?action=status|ack|trigger']);
}
