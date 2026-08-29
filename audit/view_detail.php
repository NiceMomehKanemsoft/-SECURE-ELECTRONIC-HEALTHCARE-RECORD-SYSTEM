<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');
$pdo = db();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM audit_logs WHERE id = ?');
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) { set_flash('error', 'Log entry not found.'); redirect('/audit/logs.php'); }

// Get previous entry for chain verification
$prev = $pdo->prepare('SELECT chain_hash FROM audit_logs WHERE id < ? ORDER BY id DESC LIMIT 1');
$prev->execute([$id]);
$prevHash = $prev->fetchColumn() ?: str_repeat('0', 64);

$expected = hash('sha256', $prevHash . ($log['user_id'] ?? '') . $log['operation'] . $log['status'] . $log['created_at']);
$chainOk  = hash_equals($expected, $log['chain_hash']);

$pageTitle = 'Audit Entry #' . $id;
$extraCss  = ['audit.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Audit Entry #<?= $id ?></h1>
        <a href="<?= BASE_URL ?>/audit/logs.php" class="btn btn-secondary btn-sm">← Back to Logs</a>
    </div>

    <div class="chain-status <?= $chainOk ? 'valid' : 'invalid' ?> mb-16">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <?php if ($chainOk): ?>
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            <?php else: ?>
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            <?php endif; ?>
        </svg>
        Chain Hash: <?= $chainOk ? 'Valid ✓' : 'INVALID — Entry may have been tampered with!' ?>
    </div>

    <div class="card">
        <div class="card-body">
            <table style="width:100%;font-size:15px;">
                <tr><td class="text-muted" style="padding:8px 0;width:30%;">Entry ID</td><td><?= (int)$log['id'] ?></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">User</td><td><?= htmlspecialchars($log['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int)($log['user_id'] ?? 0) ?>)</td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Operation</td><td><span class="audit-op-badge <?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Data Category</td><td><?= htmlspecialchars($log['data_category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Status</td><td><span class="badge badge-<?= $log['status'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Patient Hash</td><td><code style="font-size:14px;"><?= htmlspecialchars($log['patient_id_hash'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">IP Address</td><td><?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Key ID</td><td><?= htmlspecialchars($log['key_id'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Details</td><td><?= htmlspecialchars($log['details'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><td class="text-muted" style="padding:8px 0;">Timestamp</td><td><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            </table>

            <div style="margin-top:20px;">
                <h6>Chain Hash (SHA-256)</h6>
                <div class="hash-display mt-4"><?= htmlspecialchars($log['chain_hash'], ENT_QUOTES, 'UTF-8') ?></div>
                <h6 style="margin-top:12px;">Previous Hash</h6>
                <div class="hash-display mt-4"><?= htmlspecialchars($prevHash, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
