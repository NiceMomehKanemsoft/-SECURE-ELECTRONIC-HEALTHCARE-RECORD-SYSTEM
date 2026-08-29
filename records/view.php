<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/CryptoEngine.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_auth();
$user = current_user();
$pdo  = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT r.*, p.public_uuid, p.first_name, p.last_name, u.username as creator
     FROM medical_records r
     JOIN patients p ON r.patient_id = p.id
     JOIN users u ON r.created_by = u.id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'Record not found.');
    redirect('/records/list.php');
}

// Cryptographic role check
$accessRoles = json_decode($record['access_roles'], true) ?? [];
if (!in_array($user['role'], $accessRoles, true)) {
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'DECRYPT_DENIED', $record['record_type'], 'failure', $record['key_id'], 'Role not in access_roles');
    redirect('/security/access_denied.php');
}

$plaintext    = null;
$decryptError = null;

try {
    $sessionToken = $_SESSION['session_token'];
    $plaintext = CryptoEngine::decrypt(
        $record['ciphertext'],
        $record['iv'],
        $record['auth_tag'],
        $record['kdf_salt'],
        $sessionToken,
        $record['public_uuid']
    );
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'DECRYPT', $record['record_type'], 'success', $record['key_id']);
} catch (RuntimeException $e) {
    $decryptError = $e->getMessage();
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'TAMPER_DETECTED', $record['record_type'], 'failure', $record['key_id'], $decryptError);
}

$pageTitle = 'View Record — ' . $record['record_type'];
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1><?= htmlspecialchars($record['record_type'], ENT_QUOTES, 'UTF-8') ?> Record</h1>
        <a href="<?= BASE_URL ?>/patients/view.php?id=<?= (int)$record['patient_id'] ?>" class="btn btn-secondary btn-sm">← Patient</a>
    </div>

    <?php if ($decryptError): ?>
    <div class="alert alert-danger">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        </svg>
        <strong>Authentication Tag Failure:</strong> <?= htmlspecialchars($decryptError, ENT_QUOTES, 'UTF-8') ?>
        — This record may have been tampered with. A security alert has been logged.
    </div>
    <?php else: ?>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <h3>Record Metadata</h3>
                <span class="enc-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Verified AES-256-GCM
                </span>
            </div>
            <div class="card-body">
                <table style="width:100%;font-size:15px;">
                    <tr><td class="text-muted" style="padding:6px 0;width:40%;">Patient</td><td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Record Type</td><td><span class="badge badge-info"><?= htmlspecialchars($record['record_type'], ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Created By</td><td><?= htmlspecialchars($record['creator'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Created At</td><td><?= htmlspecialchars(date('M j, Y H:i', strtotime($record['created_at'])), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Key ID</td><td style="font-family:monospace;font-size:14px;"><?= htmlspecialchars($record['key_id'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted" style="padding:6px 0;">Access Roles</td><td><?= htmlspecialchars(implode(', ', json_decode($record['access_roles'], true) ?? []), ENT_QUOTES, 'UTF-8') ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Decrypted Content</h3>
                <span class="badge badge-success">✓ Auth Tag Valid</span>
            </div>
            <div class="card-body">
                <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px;font-size:15px;line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($plaintext ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <p class="text-muted mt-8" style="font-size:14px;">This content was decrypted in-memory using your session token. It is not stored in plaintext anywhere.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
