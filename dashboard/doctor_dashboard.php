<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('doctor');
$user = current_user();
$pdo  = db();

$totalPatients = $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$totalRecords  = $pdo->query("SELECT COUNT(*) FROM medical_records WHERE created_by = {$user['id']}")->fetchColumn();
$todayRecords  = $pdo->query("SELECT COUNT(*) FROM medical_records WHERE created_by = {$user['id']} AND DATE(created_at)=CURDATE()")->fetchColumn();

$recentLogs = $pdo->prepare(
    "SELECT operation, data_category, status, created_at FROM audit_logs
     WHERE user_id = ? ORDER BY id DESC LIMIT 8"
);
$recentLogs->execute([$user['id']]);
$logs = $recentLogs->fetchAll();

$pageTitle = 'Doctor Dashboard';
$extraCss  = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="dashboard-welcome">
        <div class="welcome-text">
            <h1>Welcome, Dr. <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p>You have full clinical access. All records are AES-256-GCM encrypted.</p>
        </div>
        <div class="welcome-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$totalPatients ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$totalRecords ?></div>
            <div class="stat-label">My Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$todayRecords ?></div>
            <div class="stat-label">Records Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <div class="stat-value">AES-256</div>
            <div class="stat-label">Encryption Active</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Quick Actions</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                <a href="<?= BASE_URL ?>/patients/register.php" class="btn btn-primary">+ Register Patient</a>
                <a href="<?= BASE_URL ?>/records/create.php" class="btn btn-outline">+ New Medical Record</a>
                <a href="<?= BASE_URL ?>/patients/list.php" class="btn btn-secondary">View All Patients</a>
            </div>
        </div>

        <div class="card recent-activity">
            <div class="card-header"><h3>Recent Activity</h3></div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                <p class="text-muted">No recent activity.</p>
                <?php else: foreach ($logs as $log): ?>
                <div class="activity-item">
                    <span class="activity-dot <?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?>"></span>
                    <span><?= htmlspecialchars($log['operation'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($log['data_category']): ?>
                        — <small><?= htmlspecialchars($log['data_category'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </span>
                    <span class="activity-time"><?= htmlspecialchars(date('M j, H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
