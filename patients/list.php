<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('doctor', 'nurse', 'admin');
$user = current_user();
$pdo  = db();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare(
        'SELECT id, public_uuid, first_name, last_name, date_of_birth, gender, blood_type, registered_at
         FROM patients WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?
         ORDER BY last_name, first_name LIMIT 100'
    );
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT id, public_uuid, first_name, last_name, date_of_birth, gender, blood_type, registered_at FROM patients ORDER BY registered_at DESC LIMIT 100');
}
$patients = $stmt->fetchAll();

$pageTitle = 'Patients';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Patients</h1>
        <?php if ($user['role'] !== 'admin'): ?>
        <a href="<?= BASE_URL ?>/patients/register.php" class="btn btn-primary btn-sm">+ Register Patient</a>
        <?php endif; ?>
    </div>

    <div class="card mb-16">
        <div class="card-body" style="padding:12px 16px;">
            <form method="GET" action="" style="display:flex;gap:10px;align-items:center;">
                <input type="text" name="q" class="form-control" placeholder="Search by name or phone…"
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="max-width:320px;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if ($search): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>DOB</th><th>Gender</th><th>Blood Type</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($patients)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px;">No patients found.</td></tr>
                <?php else: foreach ($patients as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($p['date_of_birth'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($p['gender'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['blood_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:14px;"><?= htmlspecialchars(date('M j, Y', strtotime($p['registered_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/patients/view.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline">View</a>
                        <?php if (in_array($user['role'], ['doctor','nurse'])): ?>
                        <a href="<?= BASE_URL ?>/patients/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
