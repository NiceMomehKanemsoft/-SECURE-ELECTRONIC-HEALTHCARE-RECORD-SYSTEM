<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../crypto/CryptoEngine.php';
require_once __DIR__ . '/../crypto/AuditLogger.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed']);
    exit;
}

$user      = current_user();
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$patientId = (int)($input['patient_id'] ?? 0);
$type      = $input['record_type'] ?? '';
$content   = trim($input['content'] ?? '');

if (!$patientId || !$type || !$content || !in_array($type, RECORD_TYPES, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

if (!can_access_record_type($user['role'], $type)) {
    http_response_code(403);
    echo json_encode(['error' => 'Role not permitted for this record type']);
    exit;
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT public_uuid FROM patients WHERE id = ?');
$stmt->execute([$patientId]);
$uuid = $stmt->fetchColumn();

if (!$uuid) {
    http_response_code(404);
    echo json_encode(['error' => 'Patient not found']);
    exit;
}

$enc = CryptoEngine::encrypt($content, $_SESSION['session_token'], $uuid);
$accessRoles = array_keys(array_filter(ROLE_ACCESS_MAP, fn($types) => in_array($type, $types, true)));

$pdo->prepare(
    'INSERT INTO medical_records (patient_id,record_type,ciphertext,iv,auth_tag,kdf_salt,key_id,access_roles,created_by)
     VALUES (?,?,?,?,?,?,?,?,?)'
)->execute([$patientId, $type, $enc['ciphertext'], $enc['iv'], $enc['tag'], $enc['salt'], $enc['key_id'], json_encode($accessRoles), $user['id']]);

AuditLogger::log($user['id'], $user['username'], $uuid, 'ENCRYPT', $type, 'success', $enc['key_id']);

echo json_encode(['success' => true, 'key_id' => $enc['key_id']]);
