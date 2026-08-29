USE if0_42674235_ehrs_db;

-- Demo users (passwords are 'Admin@1234' hashed with Argon2id)
-- Run this AFTER schema.sql
-- MFA secrets are placeholder encrypted values; real secrets generated on first login setup

INSERT INTO users (username, password_hash, role, department, mfa_secret_enc, mfa_enabled) VALUES
('dr.smith', '$argon2id$v=19$m=65536,t=4,p=1$c2FsdHNhbHRzYWx0c2Fs$placeholder_hash_replace_via_setup', 'doctor', 'General Medicine', 'PLACEHOLDER_ENC_SECRET', 1),
('nurse.jones', '$argon2id$v=19$m=65536,t=4,p=1$c2FsdHNhbHRzYWx0c2Fs$placeholder_hash_replace_via_setup', 'nurse', 'Ward A', 'PLACEHOLDER_ENC_SECRET', 1),
('lab.tech1', '$argon2id$v=19$m=65536,t=4,p=1$c2FsdHNhbHRzYWx0c2Fs$placeholder_hash_replace_via_setup', 'lab_tech', 'Laboratory', 'PLACEHOLDER_ENC_SECRET', 1),
('admin', '$argon2id$v=19$m=65536,t=4,p=1$c2FsdHNhbHRzYWx0c2Fs$placeholder_hash_replace_via_setup', 'admin', 'IT Administration', 'PLACEHOLDER_ENC_SECRET', 1);

-- Note: Run setup.php to properly hash passwords and generate real MFA secrets
