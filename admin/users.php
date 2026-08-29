<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');
$user = current_user();
$pdo  = db();

$users = $pdo->query(
    'SELECT id, username, role, department, is_active, created_at, last_login FROM users ORDER BY created_at DESC'
)->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>User Management</h1>
        <a href="<?= BASE_URL ?>/admin/create_user.php" class="btn btn-primary btn-sm">+ Create User</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Username</th><th>Role</th><th>Department</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="role-badge role-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(role_label($u['role']), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($u['department'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:14px;"><?= $u['last_login'] ? htmlspecialchars(date('M j, Y H:i', strtotime($u['last_login'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td style="display:flex;gap:6px;">
                        <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                        <a href="<?= BASE_URL ?>/admin/deactivate_user.php?id=<?= (int)$u['id'] ?>&csrf=<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
