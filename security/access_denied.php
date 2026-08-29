<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_auth();
$pageTitle = 'Access Denied';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
    <div style="text-align:center;max-width:440px;">
        <div style="width:80px;height:80px;background:var(--red-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2" width="40" height="40">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h1 style="color:var(--red);margin-bottom:12px;">Access Denied</h1>
        <p>Your role does not have cryptographic access to this record type. This restriction is enforced at the encryption level.</p>
        <p class="text-muted mt-8" style="font-size:14px;">This access attempt has been logged in the audit trail.</p>
        <div style="margin-top:24px;">
            <a href="<?= BASE_URL ?>/dashboard/index.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
