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

require_role('doctor');
$user  = current_user();
$pdo   = db();
$error = '';

// Pre-select patient if passed
$prePatientId = (int)($_GET['patient_id'] ?? 0);

// Load patients for dropdown
$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $patientId   = (int)($_POST['patient_id'] ?? 0);
        $recordType  = $_POST['record_type'] ?? '';
        $content     = trim($_POST['content'] ?? '');

        if (!$patientId || !$recordType || !$content) {
            $error = 'Patient, record type, and content are required.';
        } elseif (!in_array($recordType, RECORD_TYPES, true)) {
            $error = 'Invalid record type.';
        } else {
            // Fetch patient UUID for key derivation
            $pStmt = $pdo->prepare('SELECT public_uuid FROM patients WHERE id = ?');
            $pStmt->execute([$patientId]);
            $patientUuid = $pStmt->fetchColumn();

            if (!$patientUuid) { $error = 'Patient not found.'; }
            else {
                $sessionToken = $_SESSION['session_token'];
                $enc = CryptoEngine::encrypt($content, $sessionToken, $patientUuid);

                // Determine access roles for this record type
                $accessRoles = [];
                foreach (ROLE_ACCESS_MAP as $role => $types) {
                    if (in_array($recordType, $types, true)) $accessRoles[] = $role;
                }

                $pdo->prepare(
                    'INSERT INTO medical_records
                     (patient_id, record_type, ciphertext, iv, auth_tag, kdf_salt, key_id, access_roles, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $patientId, $recordType,
                    $enc['ciphertext'],
                    $enc['iv'],
                    $enc['tag'],
                    $enc['salt'],
                    $enc['key_id'],
                    json_encode($accessRoles),
                    $user['id']
                ]);

                AuditLogger::log($user['id'], $user['username'], $patientUuid, 'ENCRYPT', $recordType, 'success', $enc['key_id']);
                set_flash('success', 'Record encrypted and saved successfully.');
                redirect('/patients/view.php?id=' . $patientId);
            }
        }
    }
}

$pageTitle = 'Create Medical Record';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>New Medical Record</h1>
        <a href="<?= BASE_URL ?>/records/list.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Record Details</h3>
            <span id="encStatus" class="enc-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                AES-256-GCM Protected
            </span>
        </div>
        <div class="card-body">
            <form id="recordForm" method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">Select patient…</option>
                        <?php foreach ($patients as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $prePatientId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Record Type *</label>
                    <div class="record-type-grid">
                        <?php foreach (RECORD_TYPES as $type): ?>
                        <button type="button" class="record-type-btn" data-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="record_type" id="record_type_input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Clinical Notes / Content *</label>
                    <textarea name="content" class="form-control" rows="8"
                              placeholder="Enter clinical notes. This content will be encrypted with AES-256-GCM before storage."
                              data-validate="required" required></textarea>
                    <span class="form-hint">Content is encrypted client-side before transmission and stored as ciphertext only.</span>
                </div>

                <div class="alert alert-info" style="margin-bottom:16px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Encryption key is derived from your session token + patient UUID using PBKDF2-SHA256 (100,000 iterations). The key is never stored.
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary">Encrypt &amp; Save Record</button>
                    <a href="<?= BASE_URL ?>/records/list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>/assets/js/encryption-status.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
document.querySelectorAll('.record-type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.record-type-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('record_type_input').value = btn.dataset.type;
    });
});
</script>
