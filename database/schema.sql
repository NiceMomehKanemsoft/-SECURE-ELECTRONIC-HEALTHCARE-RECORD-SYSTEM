-- EHRS Database Schema
-- MySQL 8.0 | AES-256-GCM Encrypted Healthcare Records

CREATE DATABASE IF NOT EXISTS if0_42674235_ehrs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE if0_42674235_ehrs_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Argon2id hash',
    role ENUM('doctor','nurse','lab_tech','admin') NOT NULL,
    department VARCHAR(100),
    mfa_secret_enc VARCHAR(512) NOT NULL COMMENT 'Encrypted TOTP secret',
    mfa_enabled TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_uuid CHAR(36) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') DEFAULT 'Unknown',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registered_by INT UNSIGNED,
    INDEX idx_uuid (public_uuid),
    INDEX idx_name (last_name, first_name),
    FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE medical_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    record_type ENUM('Diagnosis','Prescription','Lab Result','Psychiatric','Vital Signs') NOT NULL,
    ciphertext LONGTEXT NOT NULL COMMENT 'AES-256-GCM encrypted content (base64)',
    iv BINARY(12) NOT NULL COMMENT '96-bit GCM IV',
    auth_tag BINARY(16) NOT NULL COMMENT '128-bit GCM authentication tag',
    kdf_salt BINARY(16) NOT NULL COMMENT 'PBKDF2 salt',
    key_id VARCHAR(64) NOT NULL COMMENT 'Key identifier (non-secret)',
    access_roles JSON NOT NULL COMMENT 'Roles permitted to decrypt',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient (patient_id),
    INDEX idx_type (record_type),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    username VARCHAR(50),
    patient_id_hash VARCHAR(64) COMMENT 'SHA-256 of patient UUID for privacy',
    operation VARCHAR(50) NOT NULL,
    data_category VARCHAR(50),
    status ENUM('success','failure','warning') NOT NULL DEFAULT 'success',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    key_id VARCHAR(64),
    details TEXT,
    chain_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 chaining hash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_operation (operation),
    INDEX idx_created (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_username_ip (username, ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB;
