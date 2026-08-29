<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_auth();
$role = $_SESSION['role'];

$map = [
    'doctor'   => '/dashboard/doctor_dashboard.php',
    'nurse'    => '/dashboard/nurse_dashboard.php',
    'lab_tech' => '/dashboard/lab_dashboard.php',
    'admin'    => '/dashboard/admin_dashboard.php',
];

header('Location: ' . BASE_URL . ($map[$role] ?? '/auth/login.php'));
exit;
