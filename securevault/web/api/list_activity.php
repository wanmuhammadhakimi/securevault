<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$role = $_SESSION['role'] ?? 'user';
$userId = $_SESSION['user_id'];

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if ($limit < 1 || $limit > 200) $limit = 50;
if ($offset < 0) $offset = 0;

// Filters — now applied in SQL (server-side) instead of fetching every row
// and filtering in the browser, so the query stays fast no matter how big
// activity_logs grows, and search/filter can still reach OLD entries too.
$actionFilter = trim($_GET['action'] ?? '');
$fromDate     = trim($_GET['from'] ?? '');
$toDate       = trim($_GET['to'] ?? '');
$search       = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($role !== 'admin') {
    $where[] = 'al.user_id = ?';
    $params[] = $userId;
}
if ($actionFilter !== '') {
    $where[] = 'al.action = ?';
    $params[] = $actionFilter;
}
if ($fromDate !== '') {
    $where[] = 'DATE(al.timestamp) >= ?';
    $params[] = $fromDate;
}
if ($toDate !== '') {
    $where[] = 'DATE(al.timestamp) <= ?';
    $params[] = $toDate;
}
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR al.details LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $stmt = $pdo->prepare("SELECT al.*, u.username
                            FROM activity_logs al
                            LEFT JOIN users u ON u.user_id = al.user_id
                            $whereSql
                            ORDER BY al.log_id DESC
                            LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $logs = $stmt->fetchAll();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al
                                  LEFT JOIN users u ON u.user_id = al.user_id
                                  $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Distinct actions for the filter dropdown — scoped the same way as the
    // main query (role-based) but NOT narrowed by the other filters, so the
    // dropdown always offers every action type the user is allowed to see,
    // not just the ones in the currently filtered/paged results.
    if ($role === 'admin') {
        $actionsStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
    } else {
        $actionsStmt = $pdo->prepare("SELECT DISTINCT action FROM activity_logs WHERE user_id = ? ORDER BY action");
        $actionsStmt->execute([$userId]);
    }
    $actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success'  => true,
        'logs'     => $logs,
        'total'    => $total,
        'limit'    => $limit,
        'offset'   => $offset,
        'has_more' => ($offset + $limit) < $total,
        'actions'  => $actions
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}