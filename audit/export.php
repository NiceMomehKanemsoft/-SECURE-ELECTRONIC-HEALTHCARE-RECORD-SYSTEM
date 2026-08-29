<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('admin');
$user = current_user();
$pdo  = db();

$where  = [];
$params = [];
if (!empty($_GET['op']))     { $where[] = 'operation = ?';  $params[] = $_GET['op']; }
if (!empty($_GET['status'])) { $where[] = 'status = ?';     $params[] = $_GET['status']; }
if (!empty($_GET['user']))   { $where[] = 'username LIKE ?'; $params[] = '%' . $_GET['user'] . '%'; }
if (!empty($_GET['date']))   { $where[] = 'DATE(created_at) = ?'; $params[] = $_GET['date']; }

$sql  = 'SELECT id,username,operation,data_category,status,ip_address,key_id,created_at FROM audit_logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 10000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

AuditLogger::log($user['id'], $user['username'], null, 'AUDIT_EXPORT', null, 'success');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ehrs_audit_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Username','Operation','Data Category','Status','IP Address','Key ID','Timestamp']);
foreach ($logs as $row) {
    fputcsv($out, [
        $row['id'], $row['username'], $row['operation'],
        $row['data_category'], $row['status'], $row['ip_address'],
        $row['key_id'], $row['created_at']
    ]);
}
fclose($out);
exit;
