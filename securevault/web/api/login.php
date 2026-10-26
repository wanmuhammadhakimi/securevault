<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php'; // for logActivity()
require_once __DIR__ . '/intrusion_alert_functions.php'; // triggerAlert() — physical buzzer

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    logActivity($pdo, null, 'LOGIN_FAIL', "Unknown username: $username", $ip);
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Self-registered accounts start as 'pending' and cannot log in until an
// admin approves them from the Manage Users dashboard (hospital-staff-only
// system — public self-registration should not grant immediate access).
if (($user['account_status'] ?? 'approved') === 'pending') {
    logActivity($pdo, $user['user_id'], 'LOGIN_BLOCKED', 'Login blocked — account pending admin approval', $ip);
    echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval. Please wait for an administrator to approve it before logging in.']);
    exit;
}

if ($user['locked_until']) {
    logActivity($pdo, $user['user_id'], 'LOGIN_BLOCKED', 'Account locked — awaiting admin unlock', $ip);
    echo json_encode(['success' => false, 'message' => 'Account locked due to suspicious activity. Contact an administrator to unlock your account.']);
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $attempts = $user['failed_attempts'] + 1;

    if ($attempts >= 5) {
        $lockUntil = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE user_id = ?")
            ->execute([$attempts, $lockUntil, $user['user_id']]);

        $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action) 
                        VALUES (?, 'BRUTE_FORCE', 'high', ?, 'ACCOUNT_LOCKED')")
            ->execute([$user['user_id'], "5 failed login attempts from IP $ip"]);

        logActivity($pdo, $user['user_id'], 'ACCOUNT_LOCKED', 'Too many failed attempts', $ip);
        triggerAlert("Password brute force on user '{$user['username']}'");
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked — an administrator must unlock it before you can log in again.']);
        exit;
    }

    $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?")->execute([$attempts, $user['user_id']]);
    logActivity($pdo, $user['user_id'], 'LOGIN_FAIL', "Wrong password (attempt $attempts)", $ip);
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Password correct — this login method (password-only) is now sufficient
// on its own. No forced biometric step afterwards.
$pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?")->execute([$user['user_id']]);

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['password_verified'] = true;
$_SESSION['biometric_verified'] = true; // password-only login = fully authenticated
$_SESSION['must_change_password'] = (bool)$user['must_change_password'];

logActivity($pdo, $user['user_id'], 'LOGIN_SUCCESS', 'Logged in via password only', $ip);

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'username' => $user['username'],
    'requires_biometric' => false,
    'must_change_password' => (bool)$user['must_change_password']
]);