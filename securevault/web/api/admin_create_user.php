<?php
// Admin-provisioned account creation (Manage Users → Add user).
//
// For hospital / banking deployments, staff accounts are normally created by
// IT/admin rather than self-registered. This endpoint:
//   - is admin-only,
//   - creates the account already APPROVED,
//   - generates a random temporary password that is shown to the admin ONCE,
//   - sets must_change_password = 1, so the user is forced to choose their own
//     password on first login (the existing auth_guard.php enforcement).
//
// Body (JSON): {"username": "...", "full_name": "...", "email": "...", "role": "user|admin", "request_id": 3 (optional)}
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}
// JSON-only: a cross-site HTML form cannot send application/json without a
// CORS preflight, so this blocks CSRF from other websites.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim((string)($input['username'] ?? ''));
$fullName = trim((string)($input['full_name'] ?? ''));
$email    = trim((string)($input['email'] ?? ''));
$role     = (string)($input['role'] ?? 'user');
$requestId = (int)($input['request_id'] ?? 0); // set when created from an access request

if ($username === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Username and email are required']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9_. -]{3,50}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3–50 characters (letters, numbers, space, . _ -)']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address']);
    exit;
}
if (!in_array($role, ['user', 'admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$check->execute([$username]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}
$checkEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$checkEmail->execute([$email]);
if ($checkEmail->fetch()) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists']);
    exit;
}

// 12-character temporary password without look-alike characters (0/O, 1/l/I).
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$tempPassword = '';
for ($i = 0; $i < 12; $i++) {
    $tempPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$hash = password_hash($tempPassword, PASSWORD_BCRYPT);

$adminId = (int)$_SESSION['user_id'];

$pdo->beginTransaction();
$pdo->prepare("INSERT INTO users (username, full_name, email, password_hash, role, account_status, must_change_password)
               VALUES (?, ?, ?, ?, ?, 'approved', 1)")
    ->execute([$username, $fullName, $email, $hash, $role]);
$newUserId = (int)$pdo->lastInsertId();
if ($requestId > 0) {
    $pdo->prepare("UPDATE access_requests SET status = 'created', handled_by = ?, handled_at = NOW()
                    WHERE request_id = ? AND status = 'pending'")
        ->execute([$adminId, $requestId]);
}
$pdo->commit();

$pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'ADMIN_CREATED_USER', ?, ?)")
    ->execute([$adminId, "Created account '$username' (user_id $newUserId, role $role) — must change password on first login", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

echo json_encode([
    'success'        => true,
    'message'        => 'Account created. Give the temporary password to the user — it will not be shown again.',
    'user_id'        => $newUserId,
    'username'       => $username,
    'temp_password'  => $tempPassword,
]);
