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

require_role('lab_tech');
$user  = current_user();
$pdo   = db();
$error = '';

$patients = $pdo->query('SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $content   = trim($_POST['content'] ?? '');
        $testName  = trim($_POST['test_name'] ?? '');

        if (!$patientId || !$content || !$testName) {
            $error = 'All fields are required.';
        } else {
            $pStmt = $pdo->prepare('SELECT public_uuid FROM patients WHERE id = ?');
            $pStmt->execute([$patientId]);
            $patientUuid = $pStmt->fetchColumn();

            if (!$patientUuid) { $error = 'Patient not found.'; }
            else {
                $fullContent  = "Test: $testName\n\n$content";
                $sessionToken = $_SESSION['session_token'];
                $enc = CryptoEngine::encrypt($fullContent, $sessionToken, $patientUuid);

                $pdo->prepare(
                    'INSERT INTO medical_records (patient_id,record_type,ciphertext,iv,auth_tag,kdf_salt,key_id,access_roles,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $patientId, 'Lab Result',
                    $enc['ciphertext'], $enc['iv'], $enc['tag'], $enc['salt'], $enc['key_id'],
                    json_encode(['doctor','lab_tech']),
                    $user['id']
                ]);

                AuditLogger::log($user['id'], $user['username'], $patientUuid, 'ENCRYPT', 'Lab Result', 'success', $enc['key_id']);
                set_flash('success', 'Lab result uploaded and encrypted.');
                redirect('/records/list.php');
            }
        }
    }
}

$pageTitle = 'Upload Lab Result';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Upload Lab Result</h1>
        <a href="<?= BASE_URL ?>/records/list.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="card" style="max-width:640px;">
        <div class="card-header">
            <h3>Lab Result Entry</h3>
            <span class="enc-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                AES-256-GCM
            </span>
        </div>
        <div class="card-body">
            <form id="recordForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">Select patient…</option>
                        <?php foreach ($patients as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Test Name *</label>
                    <input type="text" name="test_name" class="form-control" placeholder="e.g. Complete Blood Count" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Results / Findings *</label>
                    <textarea name="content" class="form-control" rows="8" placeholder="Enter lab results…" required></textarea>
                </div>

                <div class="alert alert-info" style="margin-bottom:16px;font-size:14px;">
                    Results will be encrypted with AES-256-GCM. Only Doctors and Lab Technicians can decrypt Lab Result records.
                </div>

                <button type="submit" class="btn btn-primary">Encrypt &amp; Upload</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/encryption-status.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
