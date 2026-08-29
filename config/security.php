<?php
declare(strict_types=1);

// Session
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_NAME', 'EHRS_SESS');

// Argon2id parameters
define('ARGON_MEMORY', 65536);
define('ARGON_TIME', 4);
define('ARGON_THREADS', 1);

// PBKDF2 parameters
define('PBKDF2_ALGO', 'sha256');
define('PBKDF2_ITERATIONS', 100000);
define('PBKDF2_KEY_LENGTH', 32); // 256-bit key

// AES-GCM
define('GCM_IV_LENGTH', 12);   // 96-bit
define('GCM_TAG_LENGTH', 16);  // 128-bit

// Login throttle
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// TOTP
define('TOTP_WINDOW', 1); // ±1 step tolerance

// Role access map: which roles can decrypt which record types
define('ROLE_ACCESS_MAP', [
    'doctor'   => ['Diagnosis','Prescription','Lab Result','Psychiatric','Vital Signs'],
    'nurse'    => ['Vital Signs','Prescription'],
    'lab_tech' => ['Lab Result'],
    'admin'    => [], // admin never decrypts clinical data
]);

define('APP_NAME', 'EHRS');
define('APP_VERSION', '1.0.0');
