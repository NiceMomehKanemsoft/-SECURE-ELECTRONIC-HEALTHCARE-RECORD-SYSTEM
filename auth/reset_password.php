<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_auth();
$user  = current_user();
$error = '';
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 12) {
            $error = 'Password must be at least 12 characters.';
        } elseif (!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new) || !preg_match('/[^A-Za-z0-9]/', $new)) {
            $error = 'Password must contain uppercase, number, and special character.';
        } else {
            $pdo  = db();
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row  = $stmt->fetch();

            if (!$row || !password_verify($current, $row['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_ARGON2ID, [
                    'memory_cost' => ARGON_MEMORY,
                    'time_cost'   => ARGON_TIME,
                    'threads'     => ARGON_THREADS,
                ]);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
                AuditLogger::log($user['id'], $user['username'], null, 'PASSWORD_CHANGE', null, 'success');
                $done = true;
            }
        }
    }
}

$pageTitle = 'Change Password';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header"><h1>Change Password</h1></div>

    <?php if ($done): ?>
    <div class="alert alert-success">Password changed successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:480px;">
        <div class="card-body">
            <form method="POST" action="" data-spinner="Updating password…">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="newPwd" class="form-control"
                           data-validate="required|min:12" data-strength="strength" required>
                    <div class="password-strength">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
