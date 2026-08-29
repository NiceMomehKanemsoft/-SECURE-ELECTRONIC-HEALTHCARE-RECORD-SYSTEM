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

$chain = AuditLogger::verifyChain();

// Filters
$filterOp     = $_GET['op']     ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterUser   = trim($_GET['user'] ?? '');
$filterDate   = $_GET['date']   ?? '';

$where  = [];
$params = [];

if ($filterOp)     { $where[] = 'operation = ?';  $params[] = $filterOp; }
if ($filterStatus) { $where[] = 'status = ?';     $params[] = $filterStatus; }
if ($filterUser)   { $where[] = 'username LIKE ?'; $params[] = '%' . $filterUser . '%'; }
if ($filterDate)   { $where[] = 'DATE(created_at) = ?'; $params[] = $filterDate; }

$sql  = 'SELECT * FROM audit_logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$operations = $pdo->query('SELECT DISTINCT operation FROM audit_logs ORDER BY operation')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Audit Logs';
$extraCss  = ['audit.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Audit Trail</h1>
        <a href="<?= BASE_URL ?>/audit/export.php?<?= htmlspecialchars(http_build_query($_GET), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>

    <div class="chain-status <?= $chain['valid'] ? 'valid' : 'invalid' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <?php if ($chain['valid']): ?>
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            <?php else: ?>
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            <?php endif; ?>
        </svg>
        Chain Integrity: <?= $chain['valid'] ? 'Verified — All hashes valid' : 'BROKEN at entry #' . $chain['broken_at'] . ' — Possible tampering detected!' ?>
    </div>

    <form id="auditFilterForm" method="GET" action="" class="audit-filters">
        <div>
            <label class="form-label">Operation</label>
            <select name="op" class="form-control">
                <option value="">All</option>
                <?php foreach ($operations as $op): ?>
                <option value="<?= htmlspecialchars($op, ENT_QUOTES, 'UTF-8') ?>" <?= $filterOp === $op ? 'selected' : '' ?>><?= htmlspecialchars($op, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="success" <?= $filterStatus === 'success' ? 'selected' : '' ?>>Success</option>
                <option value="failure" <?= $filterStatus === 'failure' ? 'selected' : '' ?>>Failure</option>
                <option value="warning" <?= $filterStatus === 'warning' ? 'selected' : '' ?>>Warning</option>
            </select>
        </div>
        <div>
            <label class="form-label">Username</label>
            <input type="text" name="user" class="form-control" value="<?= htmlspecialchars($filterUser, ENT_QUOTES, 'UTF-8') ?>" placeholder="Filter by user…">
        </div>
        <div>
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="?" class="btn btn-secondary btn-sm">Clear</a>
        </div>
    </form>

    <div style="margin-bottom:12px;">
        <input type="text" id="auditSearch" class="form-control" placeholder="Live search in results…" style="max-width:320px;">
    </div>

    <div class="card">
        <div class="table-wrap">
            <table id="auditTable">
                <thead>
                    <tr><th>#</th><th>User</th><th>Operation</th><th>Category</th><th>Status</th><th>IP</th><th>Time</th><th>Detail</th></tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:32px;">No log entries found.</td></tr>
                <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td style="font-size:14px;color:var(--gray-400);"><?= (int)$log['id'] ?></td>
                    <td><?= htmlspecialchars($log['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="audit-op-badge <?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:14px;"><?= htmlspecialchars($log['data_category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-<?= $log['status'] === 'success' ? 'success' : ($log['status'] === 'warning' ? 'warning' : 'danger') ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:14px;"><?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:14px;white-space:nowrap;"><?= htmlspecialchars(date('M j H:i:s', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="<?= BASE_URL ?>/audit/view_detail.php?id=<?= (int)$log['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/audit-filter.js"></script>
