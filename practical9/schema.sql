-- ============================================================
-- StudentHub - Practical 9
-- Secure User Registration with Database Insert, Duplicate Checks, and Password Hashing
-- Engine: MySQLi / InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

-- ------------------------------------------------------------
-- 1. TABLE: users
--    Stores securely registered users with hashed passwords
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    username      VARCHAR(50)     NOT NULL,
    email         VARCHAR(150)    NOT NULL,
    phone         CHAR(10)        NOT NULL,
    course        VARCHAR(50)     NOT NULL,
    semester      TINYINT         NOT NULL DEFAULT 1,
    password_hash VARCHAR(255)    NOT NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_users PRIMARY KEY (id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_semester CHECK (semester BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. TABLE: registration_audit_logs (Advanced Extension)
--    Tracks all registration attempts with security telemetry
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS registration_audit_logs (
    id                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ip_address         VARCHAR(45)     NOT NULL,
    user_agent         VARCHAR(255)    NOT NULL,
    attempted_username VARCHAR(50)     DEFAULT NULL,
    attempted_email    VARCHAR(150)    DEFAULT NULL,
    status             ENUM('SUCCESS', 'DUPLICATE_USERNAME', 'DUPLICATE_EMAIL', 'VALIDATION_ERROR', 'CSRF_FAILURE') NOT NULL,
    details            TEXT            DEFAULT NULL,
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_audit_logs PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance indexes
CREATE INDEX idx_users_username ON users (username);
CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_audit_status ON registration_audit_logs (status);
CREATE INDEX idx_audit_created ON registration_audit_logs (created_at);

-- ------------------------------------------------------------
-- 3. SEED DATA: users
--    Pre-populated accounts with BCRYPT hashed passwords
--    Password for all seed users: "Password@123"
-- ------------------------------------------------------------
INSERT INTO users (name, username, email, phone, course, semester, password_hash) VALUES
('Aarav Shah', 'aarav_shah', 'aarav.shah@example.com', '9876543210', 'B.Tech CSE', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Priya Patel', 'priya_patel', 'priya.patel@example.com', '9123456780', 'B.Tech IT', 4, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Rohan Mehta', 'rohan_mehta', 'rohan.mehta@example.com', '8765432190', 'MCA', 2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Sample audit log records
INSERT INTO registration_audit_logs (ip_address, user_agent, attempted_username, attempted_email, status, details) VALUES
('127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'aarav_shah', 'aarav.shah@example.com', 'SUCCESS', 'Account registered successfully with BCRYPT password hashing.'),
('127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'aarav_shah', 'aarav.new@example.com', 'DUPLICATE_USERNAME', 'Attempt blocked: Username aarav_shah already exists.'),
('127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'priya_new', 'priya.patel@example.com', 'DUPLICATE_EMAIL', 'Attempt blocked: Email priya.patel@example.com already exists.');
