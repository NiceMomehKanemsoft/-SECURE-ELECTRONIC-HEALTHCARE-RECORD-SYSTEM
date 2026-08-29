<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

require_role('admin');
$user = current_user();
$pdo  = db();

$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    set_flash('error', 'Invalid CSRF token.');
    redirect('/admin/users.php');
}

if ($id === (int)$user['id']) {
    set_flash('error', 'You cannot deactivate your own account.');
    redirect('/admin/users.php');
}

$stmt = $pdo->prepare('SELECT id, username, is_active FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();

if (!$target) {
    set_flash('error', 'User not found.');
    redirect('/admin/users.php');
}

$newStatus = $target['is_active'] ? 0 : 1;
$pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newStatus, $id]);

$op = $newStatus ? 'USER_ACTIVATE' : 'USER_DEACTIVATE';
AuditLogger::log($user['id'], $user['username'], null, $op, null, 'success', null, "Target: {$target['username']}");

set_flash('success', 'User ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully.');
redirect('/admin/users.php');
