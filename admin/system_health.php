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

$stats = [
    'users'         => $pdo->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn(),
    'patients'      => $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'records'       => $pdo->query('SELECT COUNT(*) FROM medical_records')->fetchColumn(),
    'audit_entries' => $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
    'failed_24h'    => $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE status='failure' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
    'tamper_events' => $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE operation='TAMPER_DETECTED'")->fetchColumn(),
];

$pageTitle = 'System Health';
$extraCss  = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header"><h1>System Health</h1></div>

    <!-- Audit Chain Status -->
    <div class="chain-status <?= $chain['valid'] ? 'valid' : 'invalid' ?> mb-16">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
            <?php if ($chain['valid']): ?>
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            <?php else: ?>
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            <?php endif; ?>
        </svg>
        Audit Chain: <?= $chain['valid'] ? 'Integrity Verified ✓' : 'INTEGRITY FAILURE at entry #' . $chain['broken_at'] ?>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="stat-value"><?= (int)$stats['users'] ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div class="stat-value"><?= number_format((int)$stats['records']) ?></div>
            <div class="stat-label">Encrypted Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div class="stat-value"><?= number_format((int)$stats['audit_entries']) ?></div>
            <div class="stat-label">Audit Log Entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?= (int)$stats['tamper_events'] > 0 ? 'red' : 'green' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$stats['tamper_events'] ?></div>
            <div class="stat-label">Tamper Events</div>
        </div>
    </div>

    <div class="grid-2 mt-24">
        <div class="card">
            <div class="card-header"><h3>Encryption Configuration</h3></div>
            <div class="card-body">
                <table style="width:100%;font-size:15px;">
                    <tr><td class="text-muted" style="padding:6px 0;width:50%;">Algorithm</td><td><span class="badge badge-success">AES-256-GCM</span></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">IV Length</td><td>96-bit (12 bytes)</td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Auth Tag</td><td>128-bit (16 bytes)</td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">KDF</td><td>PBKDF2-SHA256</td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">KDF Iterations</td><td><?= number_format(PBKDF2_ITERATIONS) ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Key Storage</td><td><span class="badge badge-success">Never stored</span></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Password Hash</td><td>Argon2id</td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">MFA</td><td>TOTP (RFC 6238)</td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Security Events (24h)</h3></div>
            <div class="card-body">
                <div class="activity-item">
                    <span class="activity-dot <?= (int)$stats['failed_24h'] > 0 ? 'failure' : 'success' ?>"></span>
                    <span>Failed operations</span>
                    <span class="activity-time"><?= (int)$stats['failed_24h'] ?></span>
                </div>
                <div class="activity-item">
                    <span class="activity-dot <?= (int)$stats['tamper_events'] > 0 ? 'failure' : 'success' ?>"></span>
                    <span>Tamper detections</span>
                    <span class="activity-time"><?= (int)$stats['tamper_events'] ?></span>
                </div>
                <div class="activity-item">
                    <span class="activity-dot success"></span>
                    <span>Audit chain integrity</span>
                    <span class="activity-time"><?= $chain['valid'] ? '✓ Valid' : '✕ Broken' ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
