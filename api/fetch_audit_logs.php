<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

header('Content-Type: application/json');
require_role('admin');

$pdo    = db();
$limit  = min((int)($_GET['limit'] ?? 50), 200);
$offset = max((int)($_GET['offset'] ?? 0), 0);

$stmt = $pdo->prepare(
    'SELECT id, username, operation, data_category, status, ip_address, created_at
     FROM audit_logs ORDER BY id DESC LIMIT ? OFFSET ?'
);
$stmt->execute([$limit, $offset]);
$logs = $stmt->fetchAll();

$total = $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

echo json_encode(['total' => (int)$total, 'logs' => $logs]);
