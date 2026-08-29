<?php
declare(strict_types=1);
$user = current_user();
?>
<nav class="navbar" id="mainNav">
    <div class="nav-brand">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <a href="<?= BASE_URL ?>/dashboard/index.php" class="brand-link">
            <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>EHRS</span>
        </a>
    </div>

    <div class="nav-links" id="navLinks">
        <a href="<?= BASE_URL ?>/dashboard/index.php" class="nav-link">Dashboard</a>

        <?php if (in_array($user['role'], ['doctor','nurse','admin'])): ?>
        <a href="<?= BASE_URL ?>/patients/list.php" class="nav-link">Patients</a>
        <?php endif; ?>

        <div class="nav-dropdown">
            <button class="nav-link dropdown-toggle">Records ▾</button>
            <div class="dropdown-menu">
                <?php if ($user['role'] === 'doctor'): ?>
                <a href="<?= BASE_URL ?>/records/create.php" class="dropdown-item">New Record</a>
                <?php endif; ?>
                <?php if ($user['role'] === 'lab_tech'): ?>
                <a href="<?= BASE_URL ?>/records/lab_upload.php" class="dropdown-item">Upload Lab Result</a>
                <?php endif; ?>
                <?php if (in_array($user['role'], ['doctor','nurse'])): ?>
                <a href="<?= BASE_URL ?>/records/vital_signs.php" class="dropdown-item">Vital Signs</a>
                <?php endif; ?>
                <?php if (in_array($user['role'], ['doctor','nurse'])): ?>
                <a href="<?= BASE_URL ?>/records/prescription.php" class="dropdown-item">Prescriptions</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/records/list.php" class="dropdown-item">View Records</a>
            </div>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
        <div class="nav-dropdown">
            <button class="nav-link dropdown-toggle">Administration ▾</button>
            <div class="dropdown-menu">
                <a href="<?= BASE_URL ?>/admin/users.php" class="dropdown-item">Manage Users</a>
                <a href="<?= BASE_URL ?>/admin/create_user.php" class="dropdown-item">Create User</a>
                <a href="<?= BASE_URL ?>/admin/system_health.php" class="dropdown-item">System Health</a>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/audit/logs.php" class="nav-link">Audit Logs</a>
        <?php endif; ?>

        <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/security/alerts.php" class="nav-link">Security</a>
        <?php endif; ?>
    </div>

    <div class="nav-user">
        <span class="tls-badge" title="Connection secured with TLS 1.3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            TLS 1.3
        </span>
        <span class="role-badge role-<?= h($user['role']) ?>"><?= h(role_label($user['role'])) ?></span>
        <div class="nav-dropdown">
            <button class="nav-link user-menu-btn">
                <span class="avatar-circle"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                <?= h($user['username']) ?> ▾
            </button>
            <div class="dropdown-menu dropdown-right">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item text-danger">Sign Out</a>
            </div>
        </div>
    </div>
</nav>
