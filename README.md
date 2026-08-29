# Secure Electronic Healthcare Record System (EHRS)
The Secure Electronic Healthcare Record System (EHRS) is a web-based application
built with PHP 8.2, MySQL 8.0, and Apache (XAMPP). It allows healthcare staff to
securely store, access, and manage patient medical records. All clinical data is
encrypted and access is strictly controlled based on user roles.
## Quick Start

1. Copy `ehrs-project/` into `htdocs/`
2. Start Apache + MySQL in XAMPP
3. Open `http://localhost/ehrs-project/setup.php?token=CHANGE_ME_BEFORE_RUNNING`
4. Scan the MFA QR codes with Google Authenticator / Authy
5. **Delete `setup.php` immediately after setup**
6. Login at `http://localhost/ehrs-project/auth/login.php`

## Demo Credentials

| Username     | Password     | Role              |
|--------------|--------------|-------------------|
| dr.smith     | Admin@1234   | Doctor            |
| nurse.jones  | Admin@1234   | Nurse             |
| lab.tech1    | Admin@1234   | Lab Technician    |
| admin        | Admin@1234   | Administrator     |

All accounts require TOTP MFA (generated during setup).

## Security Architecture

- **Encryption**: AES-256-GCM with fresh 96-bit IV per record
- **Key Derivation**: PBKDF2-SHA256, 100,000 iterations, 128-bit salt
- **Key Material**: Derived from `session_token | patient_uuid` — never stored
- **Authentication**: Argon2id password hashing + TOTP MFA (RFC 6238)
- **Audit Trail**: SHA-256 cryptographic chaining (append-only, trigger-protected)
- **Role Siloing**: Cryptographic access control via `access_roles` JSON per record

## Role Access Matrix

| Record Type    | Doctor | Nurse | Lab Tech | Admin |
|----------------|--------|-------|----------|-------|
| Diagnosis      | ✓      | ✗     | ✗        | ✗     |
| Prescription   | ✓      | ✓     | ✗        | ✗     |
| Lab Result     | ✓      | ✗     | ✓        | ✗     |
| Psychiatric    | ✓      | ✗     | ✗        | ✗     |
| Vital Signs    | ✓      | ✓     | ✗        | ✗     |

Admin can never decrypt any clinical content.

## File Structure

```
ehrs-project/
├── assets/css/          # Design system stylesheets
├── assets/js/           # Client-side scripts
├── config/              # DB, security, constants
├── crypto/              # CryptoEngine, TOTPHelper, AuditLogger
├── database/            # schema.sql, triggers.sql, seed_data.sql
├── includes/            # Shared PHP includes
├── auth/                # Login, MFA, logout, password reset
├── dashboard/           # Role-specific dashboards
├── patients/            # Patient CRUD
├── records/             # Medical record CRUD + lab upload
├── audit/               # Audit log viewer + export
├── admin/               # User management + system health
├── security/            # Access denied, tamper detected, alerts
├── api/                 # JSON API endpoints
├── setup.php            # One-time setup (DELETE after use)
└── .htaccess            # Security headers + access control
```
