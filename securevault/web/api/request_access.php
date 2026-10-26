<?php
// Public "Request access" form (replaces open self-registration).
// Stores a request for an administrator to review. It does NOT create an
// account, set a password, start a session, or allow fingerprint enrollment —
// the admin creates the account (admin_create_user.php) after verifying the
// person's identity, and enrolls their fingerprint at the kiosk.
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$fullName = trim((string)($input['full_name'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$email    = trim((string)($input['email'] ?? ''));
$note     = trim((string)($input['note'] ?? ''));
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($fullName === '' || $username === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Full name, username and email are required']); exit;
}
if (mb_strlen($fullName) > 100) {
    echo json_encode(['success' => false, 'message' => 'Full name is too long']); exit;
}
if (!preg_match('/^[A-Za-z0-9_. -]{3,50}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3–50 characters (letters, numbers, space, . _ -)']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address']); exit;
}
if (mb_strlen($note) > 300) {
    echo json_encode(['success' => false, 'message' => 'Department / reason must be 300 characters or less']); exit;
}

// Basic flood protection. Note: a whole campus/hospital can share one public
// IP, so this is deliberately generous.
$rate = $pdo->prepare("SELECT COUNT(*) FROM access_requests WHERE ip_address = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
$rate->execute([$ip]);
if ((int)$rate->fetchColumn() >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests from this network — please try again later']); exit;
}

$exists = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
$exists->execute([$username, $email]);
if ($exists->fetch()) {
    echo json_encode(['success' => false, 'message' => 'An account with this username or email already exists — use Sign in or Forgot password']); exit;
}
$pending = $pdo->prepare("SELECT 1 FROM access_requests WHERE status = 'pending' AND (username = ? OR email = ?)");
$pending->execute([$username, $email]);
if ($pending->fetch()) {
    echo json_encode(['success' => false, 'message' => 'A request for this username or email is already waiting for an administrator']); exit;
}

$pdo->prepare("INSERT INTO access_requests (full_name, username, email, note, ip_address) VALUES (?, ?, ?, ?, ?)")
    ->execute([$fullName, $username, $email, $note !== '' ? $note : null, $ip]);

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (NULL, 'ACCESS_REQUESTED', ?, ?)")
    ->execute(["Access requested for '$username' <$email>", $ip]);

echo json_encode(['success' => true, 'message' => 'Request sent — an administrator will contact you']);
