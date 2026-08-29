<?php
/**
 * EHRS One-Time Setup Script
 * Run once: http://localhost/dashboard/ehrs-project/setup.php
 * DELETE this file after setup is complete!
 */
declare(strict_types=1);

// Simple protection — change this token before running
define('SETUP_TOKEN', 'CHANGE_ME_BEFORE_RUNNING');
if (($_GET['token'] ?? '') !== SETUP_TOKEN) {
    http_response_code(403);
    die('<h2>Forbidden</h2><p>Provide ?token=CHANGE_ME_BEFORE_RUNNING to run setup.</p>');
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/crypto/KeyDerivation.php';

$errors = [];
$done   = [];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $done[] = 'Database created/verified.';

    // Run schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    // Remove USE statement since we already selected DB
    $schema = preg_replace('/^USE\s+\S+;\s*/mi', '', $schema);
    $schema = preg_replace('/^CREATE DATABASE.*?;\s*/mi', '', $schema);
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
        if ($sql) $pdo->exec($sql);
    }
    $done[] = 'Schema applied.';

    // Run triggers — split on $$ delimiter, not ;
    $triggers = file_get_contents(__DIR__ . '/database/triggers.sql');
    $triggers = preg_replace('/^USE\s+\S+;\s*/mi', '', $triggers);
    $triggers = preg_replace('/^DELIMITER\s+\S+\s*/mi', '', $triggers);
    $blocks = array_filter(array_map('trim', explode('$$', $triggers)));
    foreach ($blocks as $sql) {
        if ($sql) {
            try { $pdo->exec($sql); } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) $errors[] = 'Trigger: ' . $e->getMessage();
            }
        }
    }
    $done[] = 'Triggers applied.';

    // Seed users with real hashes
    $users = [
        ['dr.smith',    'Admin@1234', 'doctor',   'General Medicine'],
        ['nurse.jones', 'Admin@1234', 'nurse',    'Ward A'],
        ['lab.tech1',   'Admin@1234', 'lab_tech', 'Laboratory'],
        ['admin',       'Admin@1234', 'admin',    'IT Administration'],
    ];

    $mfaInfo = [];
    foreach ($users as [$username, $password, $role, $dept]) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $check->execute([$username]);
        if ($check->fetch()) { $done[] = "User '$username' already exists, skipped."; continue; }

        $hash   = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => ARGON_MEMORY,
            'time_cost'   => ARGON_TIME,
            'threads'     => ARGON_THREADS,
        ]);
        $secret = TOTPHelper::generateSecret();
        $uri    = TOTPHelper::getUri($secret, $username);

        $pdo->prepare('INSERT INTO users (username,password_hash,role,department,mfa_secret_enc) VALUES (?,?,?,?,?)')
            ->execute([$username, $hash, $role, $dept, $secret]);

        $mfaInfo[$username] = ['secret' => $secret, 'uri' => $uri];
        $done[] = "User '$username' ($role) created.";
    }

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EHRS Setup</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .ok  { color: #1e8e3e; } .err { color: #d93025; }
        .qr  { text-align: center; padding: 16px; background: #f8f9fa; border-radius: 8px; margin: 12px 0; }
        code { background: #f1f3f4; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
        .warn { background: #fef7e0; border: 1px solid #f29900; padding: 16px; border-radius: 8px; margin-top: 24px; }
    </style>
</head>
<body>
<h1>EHRS Setup</h1>

<?php foreach ($done as $msg): ?>
<p class="ok">✓ <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<?php foreach ($errors as $err): ?>
<p class="err">✕ <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<?php if (!empty($mfaInfo)): ?>
<h2>MFA Setup — Share with each user</h2>
<p>Default password for all demo users: <code>Admin@1234</code></p>
<?php foreach ($mfaInfo as $username => $info): ?>
<div class="qr">
    <h3><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h3>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($info['uri']) ?>" alt="QR">
    <p>Manual key: <code><?= htmlspecialchars($info['secret'], ENT_QUOTES, 'UTF-8') ?></code></p>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="warn">
    <strong>⚠ Security Warning:</strong> Delete <code>setup.php</code> immediately after setup is complete!<br>
    <a href="/ehrs-project/auth/login.php">→ Go to Login</a>
</div>
</body>
</html>
