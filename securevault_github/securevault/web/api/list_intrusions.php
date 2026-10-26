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

// Filters — applied in SQL (server-side) instead of fetching up to 100 rows
// and filtering in the browser, so search/filter can reach every alert, not
// just whatever happened to be in the last LIMIT 100 batch.
$severity = trim($_GET['severity'] ?? '');
$threatType = trim($_GET['threat_type'] ?? '');
$fromDate = trim($_GET['from'] ?? '');
$toDate = trim($_GET['to'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($role !== 'admin') {
    $where[] = 'ia.user_id = ?';
    $params[] = $userId;
}
if ($severity !== '') {
    $where[] = 'ia.severity = ?';
    $params[] = $severity;
}
if ($threatType !== '') {
    $where[] = 'ia.threat_type = ?';
    $params[] = $threatType;
}
if ($fromDate !== '') {
    $where[] = 'DATE(ia.timestamp) >= ?';
    $params[] = $fromDate;
}
if ($toDate !== '') {
    $where[] = 'DATE(ia.timestamp) <= ?';
    $params[] = $toDate;
}
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR ia.response_action LIKE ? OR ia.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $stmt = $pdo->prepare("SELECT ia.*, u.username
                            FROM intrusion_alerts ia
                            LEFT JOIN users u ON u.user_id = ia.user_id
                            $whereSql
                            ORDER BY ia.alert_id DESC
                            LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $alerts = $stmt->fetchAll();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM intrusion_alerts ia
                                  LEFT JOIN users u ON u.user_id = ia.user_id
                                  $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Distinct threat types for the filter dropdown — scoped by role but not
    // narrowed by the other filters, so every type the user can see is
    // always offered, regardless of the current filter/page.
    if ($role === 'admin') {
        $typesStmt = $pdo->query("SELECT DISTINCT threat_type FROM intrusion_alerts ORDER BY threat_type");
    } else {
        $typesStmt = $pdo->prepare("SELECT DISTINCT threat_type FROM intrusion_alerts WHERE user_id = ? ORDER BY threat_type");
        $typesStmt->execute([$userId]);
    }
    $threatTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success'      => true,
        'alerts'       => $alerts,
        'total'        => $total,
        'limit'        => $limit,
        'offset'       => $offset,
        'has_more'     => ($offset + $limit) < $total,
        'threat_types' => $threatTypes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}