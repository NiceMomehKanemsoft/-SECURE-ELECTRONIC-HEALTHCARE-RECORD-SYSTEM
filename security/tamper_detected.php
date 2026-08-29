<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_auth();
$pageTitle = 'Tamper Detected';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
    <div style="text-align:center;max-width:480px;">
        <div style="width:80px;height:80px;background:var(--red-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2" width="40" height="40">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h1 style="color:var(--red);margin-bottom:12px;">Data Integrity Failure</h1>
        <p>The AES-256-GCM authentication tag validation failed for this record. The data may have been tampered with or corrupted.</p>
        <p class="text-muted mt-8" style="font-size:14px;">This event has been logged in the audit trail with a security alert.</p>
        <div style="margin-top:24px;display:flex;gap:12px;justify-content:center;">
            <a href="<?= BASE_URL ?>/records/list.php" class="btn btn-secondary">Back to Records</a>
            <a href="<?= BASE_URL ?>/security/alerts.php" class="btn btn-danger">View Security Alerts</a>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
