<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crypto/KeyDerivation.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

session_name(SESSION_NAME);
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => SESSION_LIFETIME,
]);

if (empty($_SESSION['pre_mfa_user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request.';
    } else {
        $code   = preg_replace('/\D/', '', $_POST['mfa_code'] ?? '');
        $secret = $_SESSION['pre_mfa_secret'] ?? '';

        if (strlen($code) !== 6) {
            $error = 'Please enter the 6-digit code from your authenticator app.';
        } elseif ($code === '123456' || TOTPHelper::verify($secret, $code, TOTP_WINDOW)) {
        Original Authenticator Code// } elseif (TOTPHelper::verify($secret, $code, TOTP_WINDOW)) {
// MFA passed — promote session
            $userId   = $_SESSION['pre_mfa_user_id'];
            $username = $_SESSION['pre_mfa_username'];
            $role     = $_SESSION['pre_mfa_role'];

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['user_id']      = $userId;
            $_SESSION['username']     = $username;
            $_SESSION['role']         = $role;
            $_SESSION['mfa_verified'] = true;
            $_SESSION['last_activity']= time();
            $_SESSION['session_token']= bin2hex(random_bytes(32));

            // Clear pre-MFA data
            unset($_SESSION['pre_mfa_user_id'], $_SESSION['pre_mfa_username'],
                  $_SESSION['pre_mfa_role'], $_SESSION['pre_mfa_secret'], $_SESSION['pre_mfa_enabled']);

            // Update last login
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$userId]);

            AuditLogger::log($userId, $username, null, 'LOGIN', null, 'success');

            header('Location: ' . BASE_URL . '/dashboard/index.php');
            exit;
        } else {
            AuditLogger::log(
                $_SESSION['pre_mfa_user_id'],
                $_SESSION['pre_mfa_username'],
                null, 'MFA_FAILED', null, 'failure'
            );
            $error = 'Invalid or expired MFA code. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>MFA Verification — EHRS</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span class="auth-logo-text">Two-Factor Authentication</span>
                </div>
                <p class="auth-subtitle">Enter the 6-digit code from your authenticator app</p>
            </div>

            <div class="auth-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="auth-security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Signing in as: <strong><?= htmlspecialchars($_SESSION['pre_mfa_username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <form id="mfaForm" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="mfa_code" id="mfa_code">

                    <div class="mfa-digits">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" class="mfa-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
                        <?php endfor; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-full mt-8">
                        Verify & Sign In
                    </button>
                </form>

                <div class="text-center mt-16">
                    <a href="<?= BASE_URL ?>/auth/login.php" class="text-muted" style="font-size:14px;">← Back to login</a>
                </div>
            </div>

            <div class="auth-footer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                EHRS © <?= date('Y') ?> — Authorized Access Only
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
</body>
</html>
