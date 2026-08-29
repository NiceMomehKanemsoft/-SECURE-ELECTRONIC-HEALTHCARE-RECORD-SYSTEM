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

$alerts = $pdo->query(
    "SELECT * FROM audit_logs
     WHERE status='failure' OR operation IN ('TAMPER_DETECTED','LOGIN_BLOCKED','DECRYPT_DENIED')
     ORDER BY id DESC LIMIT 200"
)->fetchAll();

$pageTitle = 'Security Alerts';
$extraCss  = ['audit.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Security Alerts</h1>
        <span class="badge badge-danger"><?= count($alerts) ?> alerts</span>
    </div>

    <?php if (empty($alerts)): ?>
    <div class="alert alert-success">No security alerts. System is operating normally.</div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Severity</th><th>User</th><th>Event</th><th>IP</th><th>Details</th><th>Time</th></tr>
                </thead>
                <tbody>
                <?php foreach ($alerts as $a):
                    $isCritical = in_array($a['operation'], ['TAMPER_DETECTED','LOGIN_BLOCKED'], true);
                ?>
                <tr>
                    <td>
                        <?php if ($isCritical): ?>
                        <span class="badge badge-danger">Critical</span>
                        <?php else: ?>
                        <span class="badge badge-warning">Warning</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="audit-op-badge TAMPER"><?= htmlspecialchars($a['operation'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:14px;"><?= htmlspecialchars($a['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:14px;"><?= htmlspecialchars($a['details'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:14px;white-space:nowrap;"><?= htmlspecialchars(date('M j H:i', strtotime($a['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
