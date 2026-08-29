<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('nurse');
$user = current_user();
$pdo  = db();

$vitalCount = $pdo->query("SELECT COUNT(*) FROM medical_records WHERE record_type='Vital Signs'")->fetchColumn();
$rxCount    = $pdo->query("SELECT COUNT(*) FROM medical_records WHERE record_type='Prescription'")->fetchColumn();

$pageTitle = 'Nurse Dashboard';
$extraCss  = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="dashboard-welcome">
        <div class="welcome-text">
            <h1>Welcome, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Nurse access: Vital Signs &amp; Medication Administration records.</p>
        </div>
        <div class="welcome-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$vitalCount ?></div>
            <div class="stat-label">Vital Signs Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v11"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$rxCount ?></div>
            <div class="stat-label">Prescriptions</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Quick Actions</h3></div>
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/records/vital_signs.php" class="btn btn-primary">View Vital Signs</a>
            <a href="<?= BASE_URL ?>/records/prescription.php" class="btn btn-outline">View Prescriptions</a>
            <a href="<?= BASE_URL ?>/patients/list.php" class="btn btn-secondary">Patient List</a>
        </div>
    </div>

    <div class="alert alert-info mt-16">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Psychiatric records and detailed lab results are cryptographically restricted from your role.
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
