<?php
/**
 * Shared functions for fingerprint ENROLL + STEP-UP VERIFY.
 *
 * Fingerprint templates are stored in the DATABASE (users.fp_template),
 * encrypted with AES-256-GCM. The AS608 sensor no longer stores anything in
 * its own flash slots — it is only used to capture a finger and to do the
 * 1:1 match against the template the server sends it.
 *
 * Hand-off between browser and kiosk still uses small JSON state files
 * (one enroll + one verify at a time — single physical sensor):
 *   browser  -> *_start.php  (creates state 'waiting')
 *   kiosk    -> fingerprint_device_poll.php  (claims the job; for verify it
 *               receives the user's decrypted template)
 *   kiosk    -> fingerprint_enroll_step.php / fingerprint_enroll_report.php
 *               / fingerprint_scan_report.php  (reports progress/result)
 *   browser  -> *_poll.php  (reads the result)
 *
 * Every kiosk-facing endpoint requires the shared device key (header
 * X-Device-Key), because the verify job carries biometric template data.
 */

require_once __DIR__ . '/../config/crypto.php'; // ENCRYPTION_KEY

define('FP_ENROLL_STATE_FILE', __DIR__ . '/fingerprint_enroll_pending.json');
define('FP_VERIFY_STATE_FILE', __DIR__ . '/fingerprint_verify_pending.json');

// Shared secret between the server and kiosk_bridge.py (DEVICE_KEY there).
// Set FP_DEVICE_KEY as an environment variable on the Pi; the fallback is for
// local XAMPP testing only. Generate:  php -r "echo bin2hex(random_bytes(24));"
define('FP_DEVICE_KEY', getenv('FP_DEVICE_KEY') ?: 'CHANGE_ME_DEVICE_KEY');

const FP_ENROLL_TIMEOUT_SECONDS = 60;  // two scans + lift finger, each up to 15 s on the Arduino
const FP_VERIFY_TIMEOUT_SECONDS = 30;  // one scan (15 s) + bridge polling latency
const FP_TEMPLATE_SIZE          = 768; // bytes, this AS608 module (6 packets x 128)
const FP_MIN_SCORE              = 50;  // sensor match score below this is treated as a mismatch

// ───────────────────────── Device authentication ─────────────────────────

function fpRequireDeviceKey(): void {
    $given = $_SERVER['HTTP_X_DEVICE_KEY'] ?? '';
    if (FP_DEVICE_KEY === 'CHANGE_ME_DEVICE_KEY' || !is_string($given) || !hash_equals(FP_DEVICE_KEY, $given)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Invalid device key']);
        exit;
    }
}

// ───────────────────────── Template encryption ─────────────────────────
// AES-256-GCM. The key is derived from the system's ENCRYPTION_KEY so the
// file-encryption key is never used directly for biometrics. The user_id is
// bound in as associated data, so a template copied onto another user's row
// fails to decrypt.

function fpTemplateKey(): string {
    return hash_hmac('sha256', 'securevault-fingerprint-template-v1', ENCRYPTION_KEY, true);
}

function fpEncryptTemplate(int $userId, string $template): array {
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($template, 'aes-256-gcm', fpTemplateKey(), OPENSSL_RAW_DATA, $iv, $tag, 'uid:' . $userId, 16);
    if ($ct === false) {
        throw new RuntimeException('Fingerprint template encryption failed');
    }
    return [$ct, $iv, $tag];
}

function fpDecryptTemplate(int $userId, string $ct, string $iv, string $tag): ?string {
    $pt = openssl_decrypt($ct, 'aes-256-gcm', fpTemplateKey(), OPENSSL_RAW_DATA, $iv, $tag, 'uid:' . $userId);
    return $pt === false ? null : $pt;
}

function fpSaveTemplate(PDO $pdo, int $userId, string $template): void {
    [$ct, $iv, $tag] = fpEncryptTemplate($userId, $template);
    $pdo->prepare("UPDATE users
                      SET fp_template = ?, fp_template_iv = ?, fp_template_tag = ?,
                          fp_enrolled_at = NOW(), biometric_enrolled = 1
                    WHERE user_id = ?")
        ->execute([$ct, $iv, $tag, $userId]);
}

function fpLoadTemplate(PDO $pdo, int $userId): ?string {
    $stmt = $pdo->prepare("SELECT fp_template, fp_template_iv, fp_template_tag FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || $row['fp_template'] === null) return null;
    return fpDecryptTemplate($userId, $row['fp_template'], $row['fp_template_iv'], $row['fp_template_tag']);
}

function fpUserHasTemplate(PDO $pdo, int $userId): bool {
    $stmt = $pdo->prepare("SELECT fp_template IS NOT NULL FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (bool)$stmt->fetchColumn();
}

// ───────────────────────── State file helpers ─────────────────────────

function fpReadState(string $file, int $timeout): ?array {
    if (!file_exists($file)) return null;
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) return null;

    // Auto-expire stale requests so a late scan can never complete a
    // request the browser has already given up on.
    if (($data['status'] ?? '') === 'waiting' && isset($data['created_at'])) {
        if (time() - strtotime($data['created_at']) > $timeout) {
            $data['status'] = 'expired';
        }
    }
    return $data;
}

function fpWriteState(string $file, array $state): void {
    file_put_contents($file, json_encode($state), LOCK_EX);
}

function fpClearState(string $file): void {
    if (file_exists($file)) @unlink($file);
}

// ───────────────────────── Enroll state ─────────────────────────

function fpReadEnrollState(): ?array      { return fpReadState(FP_ENROLL_STATE_FILE, FP_ENROLL_TIMEOUT_SECONDS); }
function fpWriteEnrollState(array $s): void { fpWriteState(FP_ENROLL_STATE_FILE, $s); }
function fpClearEnrollState(): void       { fpClearState(FP_ENROLL_STATE_FILE); }

function fpStartEnroll(string $token, int $userId): void {
    fpWriteEnrollState([
        'token'      => $token,
        'user_id'    => $userId,
        'status'     => 'waiting',
        'step'       => 'waiting_first_scan', // updated live by fingerprint_enroll_step.php
        'claimed'    => false,                // true once the kiosk has picked the job up
        'created_at' => date('c'),
    ]);
}

// ───────────────────────── Verify (step-up) state ─────────────────────────

function fpReadVerifyState(): ?array      { return fpReadState(FP_VERIFY_STATE_FILE, FP_VERIFY_TIMEOUT_SECONDS); }
function fpWriteVerifyState(array $s): void { fpWriteState(FP_VERIFY_STATE_FILE, $s); }
function fpClearVerifyState(): void       { fpClearState(FP_VERIFY_STATE_FILE); }

function fpStartVerify(string $token, int $userId, string $purpose): void {
    fpWriteVerifyState([
        'token'      => $token,
        'user_id'    => $userId,
        'purpose'    => $purpose, // e.g. "download:42" — the grant is bound to this
        'status'     => 'waiting',
        'claimed'    => false,
        'created_at' => date('c'),
    ]);
}

/** True when the sensor is already busy with an enroll or a verify. */
function fpSensorBusy(): ?string {
    $e = fpReadEnrollState();
    if ($e && $e['status'] === 'waiting') return 'The fingerprint sensor is busy enrolling a user right now — please try again shortly';
    $v = fpReadVerifyState();
    if ($v && $v['status'] === 'waiting') return 'Another fingerprint verification is in progress — please wait a moment and try again';
    return null;
}

// ───────────────────────── Step-up grant ─────────────────────────

/**
 * Consumes the single-use grant set by fingerprint_verify_poll.php, but only
 * if it was earned for exactly this purpose (e.g. "download:42"). A scan done
 * for one file can therefore not be reused to download a different file.
 */
function fpConsumeActionGrant(string $purpose): bool {
    $until   = $_SESSION['fp_action_verified_until'] ?? 0;
    $granted = $_SESSION['fp_action_purpose'] ?? null;
    unset($_SESSION['fp_action_verified_until'], $_SESSION['fp_action_purpose']); // single-use either way
    return $until && time() <= $until && is_string($granted) && hash_equals($granted, $purpose);
}

// ───────────────────────── Fingerprint brute-force lockout ─────────────────────────
// After FP_MAX_FAILS mismatches within FP_FAIL_WINDOW_MIN minutes, fingerprint
// actions for that account are locked for FP_LOCKOUT_MIN minutes, a high-severity
// BIOMETRIC_BRUTE_FORCE alert is raised and the physical buzzer is triggered.
// Counts come from intrusion_alerts, so no extra table is needed.

const FP_MAX_FAILS       = 5;
const FP_FAIL_WINDOW_MIN = 10;
const FP_LOCKOUT_MIN     = 15;

/** Seconds left on a fingerprint lockout for this user (0 = not locked). */
function fpLockoutRemaining(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), MAX(`timestamp`) + INTERVAL " . FP_LOCKOUT_MIN . " MINUTE)
                             FROM intrusion_alerts
                            WHERE user_id = ? AND threat_type = 'BIOMETRIC_BRUTE_FORCE'");
    $stmt->execute([$userId]);
    return max(0, (int)$stmt->fetchColumn());
}

/** Fingerprint mismatches for this user in the current window (since the last lockout). */
function fpRecentMismatches(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM intrusion_alerts
                            WHERE user_id = ? AND threat_type = 'BIOMETRIC_MISMATCH'
                              AND `timestamp` >= NOW() - INTERVAL " . FP_FAIL_WINDOW_MIN . " MINUTE
                              AND `timestamp` > COALESCE((SELECT MAX(`timestamp`) FROM intrusion_alerts
                                                          WHERE user_id = ? AND threat_type = 'BIOMETRIC_BRUTE_FORCE'), '1970-01-01')");
    $stmt->execute([$userId, $userId]);
    return (int)$stmt->fetchColumn();
}
