<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('doctor', 'nurse');
$user  = current_user();
$pdo   = db();
$id    = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) { set_flash('error', 'Patient not found.'); redirect('/patients/list.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $pdo->prepare(
            'UPDATE patients SET first_name=?,last_name=?,date_of_birth=?,gender=?,phone=?,email=?,address=?,blood_type=? WHERE id=?'
        )->execute([
            trim($_POST['first_name'] ?? ''), trim($_POST['last_name'] ?? ''),
            $_POST['date_of_birth'] ?? '', $_POST['gender'] ?? '',
            trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''),
            trim($_POST['address'] ?? ''), $_POST['blood_type'] ?? 'Unknown', $id
        ]);
        AuditLogger::log($user['id'], $user['username'], $patient['public_uuid'], 'PATIENT_UPDATE', null, 'success');
        set_flash('success', 'Patient updated.');
        redirect('/patients/view.php?id=' . $id);
    }
}

$pageTitle = 'Edit Patient';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Edit Patient</h1>
        <a href="<?= BASE_URL ?>/patients/view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">← Back</a>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="" data-spinner="Saving…">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($patient['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($patient['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($patient['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option <?= $patient['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Blood Type</label>
                        <select name="blood_type" class="form-control">
                            <?php foreach (['Unknown','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                            <option <?= $patient['blood_type'] === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"><?= htmlspecialchars($patient['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?= BASE_URL ?>/patients/view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
