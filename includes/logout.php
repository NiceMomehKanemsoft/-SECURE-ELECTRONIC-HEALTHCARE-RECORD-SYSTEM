<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

session_name(SESSION_NAME);
session_start();

if (!empty($_SESSION['user_id'])) {
    AuditLogger::log($_SESSION['user_id'], $_SESSION['username'], null, 'LOGOUT', null, 'success');
}

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
