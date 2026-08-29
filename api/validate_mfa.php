<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crypto/KeyDerivation.php';

header('Content-Type: application/json');

session_name(SESSION_NAME);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$code   = preg_replace('/\D/', '', $input['code'] ?? '');
$secret = $_SESSION['pre_mfa_secret'] ?? '';

if (!$secret || strlen($code) !== 6) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Invalid input']);
    exit;
}

$valid = TOTPHelper::verify($secret, $code, TOTP_WINDOW);
echo json_encode(['valid' => $valid]);
