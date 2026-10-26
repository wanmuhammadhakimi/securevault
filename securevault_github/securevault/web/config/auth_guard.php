<?php
// Include after session_start() + db.php in any endpoint that requires a
// fully authenticated (password + biometric) session.

function requireFullAuth(): array {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['password_verified'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    if (empty($_SESSION['biometric_verified'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Biometric verification required']);
        exit;
    }
    // Server-side enforcement of a forced password reset — this blocks
    // EVERY protected endpoint until the user changes their password, not
    // just the dashboard UI. change_password.php itself must NOT call
    // requireFullAuth() (or must check the session directly instead), or
    // the user would be locked out of the one action that clears this flag.
    if (!empty($_SESSION['must_change_password'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You must set a new password before continuing.', 'must_change_password' => true]);
        exit;
    }
    return [
        'user_id' => $_SESSION['user_id'],
        'role'    => $_SESSION['role'] ?? 'user',
    ];
}

function logActivity(PDO $pdo, ?int $userId, string $action, string $details, string $ip): void {
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
        ->execute([$userId, $action, $details, $ip]);
}

function raiseIntrusionAlert(PDO $pdo, ?int $userId, string $threatType, string $severity, string $description, string $responseAction): void {
    $pdo->prepare("INSERT INTO intrusion_alerts (user_id, threat_type, severity, description, response_action)
                    VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $threatType, $severity, $description, $responseAction]);
}