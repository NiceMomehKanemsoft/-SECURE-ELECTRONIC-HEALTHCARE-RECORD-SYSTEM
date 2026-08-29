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

$user     = current_user();
$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$recordId = (int)($input['record_id'] ?? 0);

if (!$recordId) {
    http_response_code(400);
    echo json_encode(['error' => 'record_id required']);
    exit;
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT r.*, p.public_uuid FROM medical_records r JOIN patients p ON r.patient_id=p.id WHERE r.id=?'
);
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    http_response_code(404);
    echo json_encode(['error' => 'Record not found']);
    exit;
}

$accessRoles = json_decode($record['access_roles'], true) ?? [];
if (!in_array($user['role'], $accessRoles, true)) {
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'DECRYPT_DENIED', $record['record_type'], 'failure', $record['key_id']);
    http_response_code(403);
    echo json_encode(['error' => 'Access denied for your role']);
    exit;
}

try {
    $plaintext = CryptoEngine::decrypt(
        $record['ciphertext'], $record['iv'], $record['auth_tag'],
        $record['kdf_salt'], $_SESSION['session_token'], $record['public_uuid']
    );
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'DECRYPT', $record['record_type'], 'success', $record['key_id']);
    echo json_encode(['success' => true, 'content' => $plaintext]);
} catch (RuntimeException $e) {
    AuditLogger::log($user['id'], $user['username'], $record['public_uuid'], 'TAMPER_DETECTED', $record['record_type'], 'failure', $record['key_id'], $e->getMessage());
    http_response_code(422);
    echo json_encode(['error' => 'Authentication tag validation failed. Possible tampering.']);
}
