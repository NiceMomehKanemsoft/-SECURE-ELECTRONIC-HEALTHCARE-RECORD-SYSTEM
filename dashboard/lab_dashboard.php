<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('lab_tech');
$user = current_user();
$pdo  = db();

$myUploads = $pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE record_type='Lab Result' AND created_by=?");
$myUploads->execute([$user['id']]);
$count = $myUploads->fetchColumn();

$pageTitle = 'Lab Dashboard';
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
            <p>Laboratory Technician — Upload and manage lab results.</p>
        </div>
        <div class="welcome-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v11m0 0H5a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-4m-6 0h6"/>
            </svg>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4"/>
                </svg>
            </div>
            <div class="stat-value"><?= (int)$count ?></div>
            <div class="stat-label">My Lab Uploads</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Quick Actions</h3></div>
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/records/lab_upload.php" class="btn btn-primary">Upload Lab Result</a>
            <a href="<?= BASE_URL ?>/records/list.php" class="btn btn-secondary">View My Submissions</a>
        </div>
    </div>

    <div class="alert alert-info mt-16">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        You can only access Lab Result records. Prescriptions, diagnoses, and psychiatric records are cryptographically restricted.
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
