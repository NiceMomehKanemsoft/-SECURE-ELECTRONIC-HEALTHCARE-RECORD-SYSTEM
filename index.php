<?php
declare(strict_types=1);
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/constants.php';

session_name(SESSION_NAME);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);

if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_verified'])) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
