<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('doctor', 'nurse', 'admin');
$user = current_user();
$pdo  = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    set_flash('error', 'Patient not found.');
    redirect('/patients/list.php');
}

// Fetch records (metadata only — no decryption here)
$recStmt = $pdo->prepare(
    'SELECT r.id, r.record_type, r.key_id, r.access_roles, r.created_at, u.username as creator
     FROM medical_records r JOIN users u ON r.created_by = u.id
     WHERE r.patient_id = ? ORDER BY r.created_at DESC'
);
$recStmt->execute([$id]);
$records = $recStmt->fetchAll();

AuditLogger::log($user['id'], $user['username'], $patient['public_uuid'], 'VIEW_PATIENT', null, 'success');

$pageTitle = 'Patient: ' . $patient['first_name'] . ' ' . $patient['last_name'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <div style="display:flex;gap:8px;">
            <?php if (in_array($user['role'], ['doctor','nurse'])): ?>
            <a href="<?= BASE_URL ?>/patients/edit.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Edit</a>
            <?php endif; ?>
            <?php if ($user['role'] === 'doctor'): ?>
            <a href="<?= BASE_URL ?>/records/create.php?patient_id=<?= $id ?>" class="btn btn-primary btn-sm">+ New Record</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/patients/list.php" class="btn btn-secondary btn-sm">← Back</a>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Demographics</h3></div>
            <div class="card-body">
                <table style="width:100%;font-size:15px;">
                    <tr><td class="text-muted" style="width:40%;padding:6px 0;">UUID</td><td style="font-size:14px;font-family:monospace;"><?= htmlspecialchars($patient['public_uuid'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Date of Birth</td><td><?= htmlspecialchars($patient['date_of_birth'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Gender</td><td><?= htmlspecialchars($patient['gender'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Blood Type</td><td><span class="badge badge-info"><?= htmlspecialchars($patient['blood_type'], ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Phone</td><td><?= htmlspecialchars($patient['phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Email</td><td><?= htmlspecialchars($patient['email'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Address</td><td><?= htmlspecialchars($patient['address'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Registered</td><td><?= htmlspecialchars(date('M j, Y', strtotime($patient['registered_at'])), ENT_QUOTES, 'UTF-8') ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Medical Records</h3>
                <span class="badge badge-info"><?= count($records) ?> records</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($records)): ?>
                <p class="text-muted" style="padding:20px;">No records yet.</p>
                <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>Created By</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $rec):
                            $roles = json_decode($rec['access_roles'], true) ?? [];
                            $canView = can_access_record_type($user['role'], $rec['record_type']);
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($rec['record_type'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="enc-badge" style="margin-left:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    AES-256-GCM
                                </span>
                            </td>
                            <td><?= htmlspecialchars($rec['creator'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-size:14px;"><?= htmlspecialchars(date('M j, Y', strtotime($rec['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($canView): ?>
                                <a href="<?= BASE_URL ?>/records/view.php?id=<?= (int)$rec['id'] ?>" class="btn btn-sm btn-outline">Decrypt & View</a>
                                <?php else: ?>
                                <span class="badge badge-danger">Access Denied</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
