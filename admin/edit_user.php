<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('admin');
$user  = current_user();
$pdo   = db();
$id    = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $pdo->prepare('SELECT id, username, role, department, is_active FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) { set_flash('error', 'User not found.'); redirect('/admin/users.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $role       = $_POST['role'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $newPwd     = $_POST['new_password'] ?? '';

        if (!in_array($role, USER_ROLES, true)) { $error = 'Invalid role.'; }
        else {
            $pdo->prepare('UPDATE users SET role=?, department=? WHERE id=?')->execute([$role, $department, $id]);

            if ($newPwd !== '') {
                if (strlen($newPwd) < 12) { $error = 'Password must be at least 12 characters.'; }
                else {
                    $hash = password_hash($newPwd, PASSWORD_ARGON2ID, [
                        'memory_cost' => ARGON_MEMORY, 'time_cost' => ARGON_TIME, 'threads' => ARGON_THREADS,
                    ]);
                    $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
                }
            }

            if (!$error) {
                AuditLogger::log($user['id'], $user['username'], null, 'USER_UPDATE', null, 'success', null, "Updated user ID $id");
                set_flash('success', 'User updated.');
                redirect('/admin/users.php');
            }
        }
    }
}

$pageTitle = 'Edit User';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Edit User: <?= htmlspecialchars($target['username'], ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="card" style="max-width:480px;">
        <div class="card-body">
            <form method="POST" action="" data-spinner="Saving…">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($target['username'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <?php foreach (USER_ROLES as $r): ?>
                            <option value="<?= $r ?>" <?= $target['role'] === $r ? 'selected' : '' ?>><?= htmlspecialchars(role_label($r), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($target['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min 12 characters">
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
