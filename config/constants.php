<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '');  // leave empty if hosted at root domain

define('RECORD_TYPES', ['Diagnosis','Prescription','Lab Result','Psychiatric','Vital Signs']);
define('USER_ROLES', ['doctor','nurse','lab_tech','admin']);

define('ROLE_LABELS', [
    'doctor'   => 'Doctor',
    'nurse'    => 'Nurse',
    'lab_tech' => 'Lab Technician',
    'admin'    => 'Administrator',
]);
