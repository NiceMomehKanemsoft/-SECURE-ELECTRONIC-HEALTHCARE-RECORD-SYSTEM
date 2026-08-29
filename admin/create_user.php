<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/KeyDerivation.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('admin');
$user  = current_user();
$pdo   = db();
$error = '';
$newSecret = null;
$newUri    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $username   = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $role       = $_POST['role'] ?? '';
        $department = trim($_POST['department'] ?? '');

        if (!$username || !$password || !in_array($role, USER_ROLES, true)) {
            $error = 'Username, password, and role are required.';
        } elseif (strlen($password) < 12) {
            $error = 'Password must be at least 12 characters.';
        } else {
            // Check duplicate
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'Username already exists.';
            } else {
                $hash   = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => ARGON_MEMORY,
                    'time_cost'   => ARGON_TIME,
                    'threads'     => ARGON_THREADS,
                ]);
                $secret = TOTPHelper::generateSecret();
                $newUri = TOTPHelper::getUri($secret, $username);
                $newSecret = $secret;

                $pdo->prepare(
                    'INSERT INTO users (username, password_hash, role, department, mfa_secret_enc) VALUES (?,?,?,?,?)'
                )->execute([$username, $hash, $role, $department, $secret]);

                AuditLogger::log($user['id'], $user['username'], null, 'USER_CREATE', null, 'success', null, "Created: $username ($role)");
                set_flash('success', "User '$username' created. Share the QR code below with them.");
            }
        }
    }
}

$pageTitle = 'Create User';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Create New User</h1>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <?php if ($newSecret): ?>
    <div class="alert alert-success">User created successfully. Provide the MFA setup below to the new user.</div>
    <div class="card mb-16" style="max-width:400px;">
        <div class="card-header"><h3>MFA Setup</h3></div>
        <div class="card-body text-center">
            <p class="text-muted" style="font-size:14px;margin-bottom:12px;">Scan this QR code with an authenticator app (Google Authenticator, Authy, etc.)</p>
            <div class="qr-container">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($newUri ?? '') ?>" alt="MFA QR Code">
            </div>
            <p style="font-size:14px;margin-top:8px;">Manual key: <code style="background:var(--gray-100);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($newSecret, ENT_QUOTES, 'UTF-8') ?></code></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:520px;">
        <div class="card-body">
            <form method="POST" action="" data-spinner="Creating user…">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" data-validate="required" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password * (min 12 chars)</label>
                    <input type="password" name="password" class="form-control" data-validate="required|min:12" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select role…</option>
                            <?php foreach (USER_ROLES as $r): ?>
                            <option value="<?= $r ?>"><?= htmlspecialchars(role_label($r), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
