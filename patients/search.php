<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('doctor', 'nurse', 'admin');
// Redirect to list with search param
$q = trim($_GET['q'] ?? '');
header('Location: ' . BASE_URL . '/patients/list.php?q=' . urlencode($q));
exit;
