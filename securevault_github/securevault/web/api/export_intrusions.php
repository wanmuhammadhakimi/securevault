<?php
// Exports the Threat Watch / Intrusion Logs table as a CSV file, honoring
// the same filters as the on-screen table (severity, threat type, date
// range, search). Admin-only, same as viewing the full threat log.
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Not logged in';
    exit;
}

$role = $_SESSION['role'] ?? 'user';

$severity   = trim($_GET['severity'] ?? '');
$threatType = trim($_GET['threat_type'] ?? '');
$from       = trim($_GET['from'] ?? '');
$to         = trim($_GET['to'] ?? '');
$search     = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

// Non-admins only ever see their own alerts — same restriction as
// list_intrusions.php.
if ($role !== 'admin') {
    $where[] = 'ia.user_id = ?';
    $params[] = $_SESSION['user_id'];
}

if ($severity !== '') {
    $where[] = 'ia.severity = ?';
    $params[] = $severity;
}

if ($threatType !== '') {
    $where[] = 'ia.threat_type = ?';
    $params[] = $threatType;
}

if ($from !== '') {
    $where[] = 'ia.timestamp >= ?';
    $params[] = $from . ' 00:00:00';
}

if ($to !== '') {
    $where[] = 'ia.timestamp <= ?';
    $params[] = $to . ' 23:59:59';
}

if ($search !== '') {
    $where[] = '(u.username LIKE ? OR ia.response_action LIKE ? OR ia.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $stmt = $pdo->prepare("SELECT ia.timestamp, ia.threat_type, u.username, ia.severity, ia.description, ia.response_action
                            FROM intrusion_alerts ia
                            LEFT JOIN users u ON u.user_id = ia.user_id
                            $whereSql
                            ORDER BY ia.alert_id DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'DB error: ' . $e->getMessage();
    exit;
}

$filename = 'securevault_threat_log_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM so Excel on Windows reads UTF-8 correctly instead of mangling it.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Time', 'Threat Type', 'User', 'Severity', 'Reason', 'Response']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['timestamp'],
        str_replace('_', ' ', $r['threat_type']),
        $r['username'] ?? '',
        $r['severity'],
        $r['description'],
        $r['response_action']
    ]);
}

fclose($out);