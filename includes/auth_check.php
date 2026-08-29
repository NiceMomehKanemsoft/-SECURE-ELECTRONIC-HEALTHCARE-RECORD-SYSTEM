<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';

function require_auth(): void {
    session_name(SESSION_NAME);
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => false, // set true in production with HTTPS
            'cookie_samesite' => 'Strict',
            'gc_maxlifetime'  => SESSION_LIFETIME,
        ]);
    }

    if (empty($_SESSION['user_id']) || empty($_SESSION['mfa_verified'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    // Session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function current_user(): array {
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ];
}
