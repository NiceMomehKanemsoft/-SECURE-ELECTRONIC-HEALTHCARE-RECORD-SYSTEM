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
$user  = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Invalid request.'; }
    else {
        $fields = ['first_name','last_name','date_of_birth','gender','phone','email','address','blood_type'];
        $data   = [];
        foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

        if (!$data['first_name'] || !$data['last_name'] || !$data['date_of_birth'] || !$data['gender']) {
            $error = 'First name, last name, date of birth, and gender are required.';
        } else {
            $pdo  = db();
            $uuid = uuid_v4();
            $pdo->prepare(
                'INSERT INTO patients (public_uuid,first_name,last_name,date_of_birth,gender,phone,email,address,blood_type,registered_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $uuid, $data['first_name'], $data['last_name'], $data['date_of_birth'],
                $data['gender'], $data['phone'], $data['email'], $data['address'],
                $data['blood_type'] ?: 'Unknown', $user['id']
            ]);
            $patientId = $pdo->lastInsertId();
            AuditLogger::log($user['id'], $user['username'], $uuid, 'PATIENT_REGISTER', null, 'success');
            set_flash('success', 'Patient registered successfully.');
            redirect('/patients/view.php?id=' . $patientId);
        }
    }
}

$pageTitle = 'Register Patient';
$extraCss  = ['forms.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h1>Register New Patient</h1>
        <a href="<?= BASE_URL ?>/patients/list.php" class="btn btn-secondary btn-sm">← Back to List</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="" data-spinner="Registering patient…">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-section">
                    <div class="form-section-title">Personal Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" data-validate="required" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" data-validate="required" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" name="date_of_birth" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select gender</option>
                                <option>Male</option><option>Female</option><option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Blood Type</label>
                            <select name="blood_type" class="form-control">
                                <option value="Unknown">Unknown</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                                <option><?= $bt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Contact Details</div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" data-validate="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary">Register Patient</button>
                    <a href="<?= BASE_URL ?>/patients/list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/loading-spinner.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
