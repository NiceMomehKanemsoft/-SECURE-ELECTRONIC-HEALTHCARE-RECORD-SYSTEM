<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');
$user = current_user();
$pdo  = db();

$userCount    = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn();
$patientCount = $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$recordCount  = $pdo->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();
$auditCount   = $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$failedLogins = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE operation='LOGIN_FAILED' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

$recentLogs = $pdo->query(
    "SELECT username, operation, status, ip_address, created_at FROM audit_logs ORDER BY id DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
$extraCss  = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="dashboard-welcome">
        <div class="welcome-text">
            <h1>System Administration</h1>
            <p>Administrator access — No clinical data decryption permitted.</p>
        </div>
        <div class="welcome-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
            </svg>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="stat-value"><?= (int)$userCount ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div class="stat-value"><?= (int)$patientCount ?></div>
            <div class="stat-label">Patients</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div class="stat-value"><?= (int)$recordCount ?></div>
            <div class="stat-label">Encrypted Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?= (int)$failedLogins > 5 ? 'red' : 'green' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$failedLogins ?></div>
            <div class="stat-label">Failed Logins (24h)</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Administration</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-primary">Manage Users</a>
                <a href="<?= BASE_URL ?>/admin/create_user.php" class="btn btn-outline">Create New User</a>
                <a href="<?= BASE_URL ?>/admin/system_health.php" class="btn btn-secondary">System Health</a>
                <a href="<?= BASE_URL ?>/audit/logs.php" class="btn btn-secondary">Audit Logs (<?= number_format((int)$auditCount) ?>)</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Recent Audit Events</h3></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>User</th><th>Operation</th><th>Status</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="audit-op-badge <?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><span class="badge badge-<?= $log['status'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="font-size:14px;"><?= htmlspecialchars(date('M j H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
