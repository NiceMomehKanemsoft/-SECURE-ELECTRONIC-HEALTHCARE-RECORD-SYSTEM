<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('doctor', 'nurse');
$user = current_user();
$pdo  = db();

$stmt = $pdo->query(
    "SELECT r.id, r.key_id, r.created_at, p.first_name, p.last_name, u.username as creator
     FROM medical_records r JOIN patients p ON r.patient_id=p.id JOIN users u ON r.created_by=u.id
     WHERE r.record_type='Vital Signs' ORDER BY r.created_at DESC LIMIT 100"
);
$records = $stmt->fetchAll();

$pageTitle = 'Vital Signs Records';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Vital Signs Records</h1>
        <?php if ($user['role'] === 'doctor'): ?>
        <a href="<?= BASE_URL ?>/records/create.php" class="btn btn-primary btn-sm">+ New Record</a>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Patient</th><th>Created By</th><th>Date</th><th>Encryption</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($records)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:32px;">No vital signs records found.</td></tr>
                <?php else: foreach ($records as $rec): ?>
                <tr>
                    <td><?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($rec['creator'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:14px;"><?= htmlspecialchars(date('M j, Y H:i', strtotime($rec['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="enc-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>AES-256-GCM</span></td>
                    <td><a href="<?= BASE_URL ?>/records/view.php?id=<?= (int)$rec['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
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
