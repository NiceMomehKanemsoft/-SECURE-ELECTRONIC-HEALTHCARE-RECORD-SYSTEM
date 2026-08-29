<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

header('Content-Type: application/json');
require_role('doctor', 'nurse', 'admin');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo  = db();
$like = '%' . $q . '%';
$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, date_of_birth FROM patients
     WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 10'
);
$stmt->execute([$like, $like]);
$results = $stmt->fetchAll();

echo json_encode(array_map(fn($p) => [
    'id'    => (int)$p['id'],
    'label' => $p['last_name'] . ', ' . $p['first_name'] . ' (' . $p['date_of_birth'] . ')',
], $results));
