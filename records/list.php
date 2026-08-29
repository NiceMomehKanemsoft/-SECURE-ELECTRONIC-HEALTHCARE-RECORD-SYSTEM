<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_auth();
$user = current_user();
$pdo  = db();

$allowedTypes = ROLE_ACCESS_MAP[$user['role']] ?? [];

if (empty($allowedTypes)) {
    // Admin sees metadata only
    $stmt = $pdo->query(
        'SELECT r.id, r.record_type, r.key_id, r.created_at, p.first_name, p.last_name, u.username as creator
         FROM medical_records r JOIN patients p ON r.patient_id=p.id JOIN users u ON r.created_by=u.id
         ORDER BY r.created_at DESC LIMIT 200'
    );
} else {
    $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
    $stmt = $pdo->prepare(
        "SELECT r.id, r.record_type, r.key_id, r.created_at, p.first_name, p.last_name, u.username as creator
         FROM medical_records r JOIN patients p ON r.patient_id=p.id JOIN users u ON r.created_by=u.id
         WHERE r.record_type IN ($placeholders)
         ORDER BY r.created_at DESC LIMIT 200"
    );
    $stmt->execute($allowedTypes);
}
$records = $stmt->fetchAll();

$pageTitle = 'Medical Records';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Medical Records</h1>
        <?php if ($user['role'] === 'doctor'): ?>
        <a href="<?= BASE_URL ?>/records/create.php" class="btn btn-primary btn-sm">+ New Record</a>
        <?php endif; ?>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <div class="alert alert-warning mb-16">Administrator role cannot decrypt clinical content. Metadata only.</div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Patient</th><th>Type</th><th>Created By</th><th>Date</th><th>Encryption</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px;">No accessible records found.</td></tr>
                <?php else: foreach ($records as $rec): ?>
                <tr>
                    <td><?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($rec['record_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($rec['creator'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:14px;"><?= htmlspecialchars(date('M j, Y', strtotime($rec['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="enc-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            AES-256-GCM
                        </span>
                    </td>
                    <td>
                        <?php if ($user['role'] !== 'admin'): ?>
                        <a href="<?= BASE_URL ?>/records/view.php?id=<?= (int)$rec['id'] ?>" class="btn btn-sm btn-outline">Decrypt &amp; View</a>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:14px;">No access</span>
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
