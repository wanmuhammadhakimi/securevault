<?php
/**
 * EMERGENCY BREAK-GLASS RECOVERY
 * ================================
 * Unlocks ALL admin accounts (clears failed_attempts + locked_until)
 * WITHOUT requiring anyone to be logged in — this is intentional. It is
 * the last resort for when every admin account has been locked (e.g. by
 * a brute-force attack, or a mistake) and normal login is impossible.
 *
 * SECURITY — READ BEFORE DEPLOYING:
 *   - Set EMERGENCY_KEY as an environment variable on the server —
 *     NEVER hardcode it here or commit it to version control.
 *     Generate one with:  php -r "echo bin2hex(random_bytes(24));"
 *   - This endpoint should ideally be deleted or renamed after use, and
 *     re-added only when needed again — don't leave it permanently
 *     reachable at a guessable URL in production.
 *   - Every use is logged to activity_logs with user_id = NULL and the
 *     requesting IP, so there is always an audit trail of when this was
 *     invoked, even though it bypasses normal login.
 *
 * USAGE:
 *   POST { "key": "your-emergency-key" }  to this endpoint.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // for logActivity()

$input = json_decode(file_get_contents('php://input'), true);
$providedKey = $input['key'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$emergencyKey = getenv('EMERGENCY_KEY');

if (!$emergencyKey) {
    // Fail closed: if no key is configured on the server, this endpoint
    // must not do anything, rather than silently accepting any request.
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Emergency recovery is not configured on this server.']);
    exit;
}

// hash_equals() prevents timing attacks that could let someone guess the
// key one character at a time by measuring response speed.
if (!hash_equals($emergencyKey, $providedKey)) {
    logActivity($pdo, null, 'EMERGENCY_UNLOCK_FAIL', "Invalid emergency key attempt from IP $ip", $ip);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid key.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE role = 'admin'");
$stmt->execute();
$unlockedCount = $stmt->rowCount();

logActivity($pdo, null, 'EMERGENCY_UNLOCK_SUCCESS', "Emergency unlock used — cleared lockout on $unlockedCount admin account(s) from IP $ip", $ip);

$pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                VALUES (NULL, 'EMERGENCY_ACCESS', 'high', ?, 'ADMIN_UNLOCKED')")
    ->execute(["Break-glass emergency unlock invoked from IP $ip — $unlockedCount admin account(s) unlocked"]);

echo json_encode([
    'success' => true,
    'message' => "Unlocked $unlockedCount admin account(s).",
    'unlocked_count' => $unlockedCount
]);