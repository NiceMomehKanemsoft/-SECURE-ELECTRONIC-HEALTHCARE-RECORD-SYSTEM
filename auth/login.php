<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

session_name(SESSION_NAME);
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime'  => SESSION_LIFETIME,
]);

// Already logged in
if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_verified'])) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip       = get_client_ip();

        if (is_rate_limited($username, $ip)) {
            $error = 'Too many failed attempts. Please wait ' . LOCKOUT_MINUTES . ' minutes.';
            AuditLogger::log(null, $username, null, 'LOGIN_BLOCKED', null, 'failure', null, 'Rate limited');
        } elseif ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $pdo  = db();
            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, mfa_secret_enc, mfa_enabled, is_active FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                log_login_attempt($username, $ip, true);
                // Store partial session for MFA step
                $_SESSION['pre_mfa_user_id']  = $user['id'];
                $_SESSION['pre_mfa_username'] = $user['username'];
                $_SESSION['pre_mfa_role']     = $user['role'];
                $_SESSION['pre_mfa_secret']   = $user['mfa_secret_enc'];
                $_SESSION['pre_mfa_enabled']  = (bool)$user['mfa_enabled'];
                header('Location: ' . BASE_URL . '/auth/verify_mfa.php');
                exit;
            } else {
                log_login_attempt($username, $ip, false);
                AuditLogger::log(null, $username, null, 'LOGIN_FAILED', null, 'failure', null, 'Bad credentials');
                $error = 'Invalid username or password.';
            }
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
    <title>Sign In — EHRS</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                    <span class="auth-logo-text">EHRS</span>
                </div>
                <p class="auth-subtitle">Secure Electronic Healthcare Records</p>
            </div>

            <div class="auth-body">
                <?php if ($timeout): ?>
                <div class="alert alert-warning">Your session expired. Please sign in again.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="auth-security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Connection secured with TLS 1.3 · AES-256-GCM
                </div>

                <form id="loginForm" method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Enter your username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               autocomplete="username" required data-validate="required">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter your password"
                                   autocomplete="current-password" required data-validate="required">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary toggle-password" data-target="password">👁</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full mt-8">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Continue to MFA Verification
                    </button>
                </form>
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
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
</body>
</html>
