<?php
/**
 * Shared functions for the intrusion buzzer flag file.
 * Included directly by login.php (and anywhere else that detects an
 * intrusion) AND by intrusion_alert.php (the endpoint the Arduino/ESP
 * watcher polls). Keeping these here means login.php can set the alert
 * instantly via a function call, instead of making an HTTP request to
 * itself.
 */

define('ALERT_STATE_FILE', __DIR__ . '/alert_state.json');

function readAlertState(): array {
    if (!file_exists(ALERT_STATE_FILE)) {
        return ['active' => false, 'reason' => null, 'triggered_at' => null];
    }
    $raw = file_get_contents(ALERT_STATE_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['active' => false, 'reason' => null, 'triggered_at' => null];
}

function writeAlertState(array $state): void {
    file_put_contents(ALERT_STATE_FILE, json_encode($state), LOCK_EX);
}

function triggerAlert(string $reason = 'unspecified'): void {
    writeAlertState([
        'active' => true,
        'reason' => $reason,
        'triggered_at' => date('c'),
    ]);
}
