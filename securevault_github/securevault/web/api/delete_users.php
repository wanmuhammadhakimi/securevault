<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = (int)($input['user_id'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

if ($targetUserId === (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit;
}

$check = $pdo->prepare("SELECT user_id, username, role FROM users WHERE user_id = ?");
$check->execute([$targetUserId]);
$target = $check->fetch();

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($target['role'] === 'admin') {
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin account']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Log first, inside the transaction, so the record names the account
    // before it disappears.
    $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'USER_DELETED', ?, ?)")
        ->execute([
            $_SESSION['user_id'],
            "Admin deleted account: {$target['username']} (user_id {$targetUserId}, role: {$target['role']}). Activity and intrusion logs kept.",
            $ip
        ]);

    // The user's encrypted files go with the account.
    require_once __DIR__ . '/../config/crypto.php';
    $filesStmt = $pdo->prepare("SELECT stored_filename FROM files WHERE user_id = ?");
    $filesStmt->execute([$targetUserId]);
    $paths = [];
    foreach ($filesStmt->fetchAll() as $f) {
        $paths[] = ENCRYPTED_DIR . $f['stored_filename'];
    }
    $pdo->prepare("DELETE FROM files WHERE user_id = ?")->execute([$targetUserId]);

    // activity_logs and intrusion_alerts are append-only (database triggers).
    // They are NOT deleted: the foreign keys set user_id to NULL instead, so
    // the history survives even if an admin account is misused.
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$targetUserId]);

    $pdo->commit();

    foreach ($paths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    echo json_encode(['success' => true, 'message' => 'User deleted. Their logs were kept for auditing.']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
}
