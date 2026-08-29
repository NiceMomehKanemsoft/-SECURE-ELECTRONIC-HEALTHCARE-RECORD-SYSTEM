<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

function require_role(string ...$roles): void {
    require_auth();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . BASE_URL . '/security/access_denied.php');
        exit;
    }
}

function require_not_role(string ...$roles): void {
    require_auth();
    if (in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . BASE_URL . '/security/access_denied.php');
        exit;
    }
}
