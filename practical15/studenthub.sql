-- ============================================================
-- StudentHub - Unified Complete Database Schema
-- Practicals 8, 9, 10, 11, 12, 13, 14, 15
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP VIEW IF EXISTS v_registrations;
DROP PROCEDURE IF EXISTS GetStudentRegistrations;

SET FOREIGN_KEY_CHECKS = 1;


-- ------------------------------------------------------------
-- 1. TABLE: users
--    Used for Authentication, RBAC, Sessions, Remember-Me
--    Practicals: 9, 10, 14, 15
-- ------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)    NOT NULL,
    username        VARCHAR(50)     NOT NULL UNIQUE,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    phone           CHAR(10)        NOT NULL,
    role            ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    course          VARCHAR(50)     DEFAULT NULL,
    semester        TINYINT         DEFAULT 1,
    password_hash   VARCHAR(255)    NOT NULL,
    remember_token  VARCHAR(64)     DEFAULT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login      DATETIME        DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_user_role (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- 2. TABLE: students
--    Used for Student Management CRUD, Search, Filter, Soft Delete
--    Practicals: 8, 11, 13, 14, 15
-- ------------------------------------------------------------
CREATE TABLE students (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    phone       CHAR(10)        NOT NULL,
    course      VARCHAR(50)     NOT NULL,
    semester    TINYINT         NOT NULL DEFAULT 1 CHECK (semester BETWEEN 1 AND 8),
    status      ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_student_course (course),
    INDEX idx_student_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- 3. TABLE: events
--    Used for Event Management CRUD, Poster Uploads, Open/Closed Status
--    Practicals: 8, 12, 13, 14, 15
-- ------------------------------------------------------------
CREATE TABLE events (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title         VARCHAR(200)    NOT NULL,
    category      ENUM('workshop', 'seminar', 'hackathon', 'cultural', 'sports', 'other') NOT NULL DEFAULT 'other',
    description   TEXT            NOT NULL,
    event_date    DATE            NOT NULL,
    venue         VARCHAR(200)    NOT NULL,
    max_seats     SMALLINT        NOT NULL DEFAULT 50 CHECK (max_seats > 0),
    poster_image  VARCHAR(255)    DEFAULT NULL,
    status        ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_event_date (event_date),
    INDEX idx_event_category (category),
    INDEX idx_event_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- 4. TABLE: event_registrations
--    M:N Junction Linking Students to Events
--    Practicals: 8, 14, 15
-- ------------------------------------------------------------
CREATE TABLE event_registrations (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    student_id      INT UNSIGNED    NOT NULL,
    event_id        INT UNSIGNED    NOT NULL,
    status          ENUM('confirmed', 'waitlisted', 'cancelled') NOT NULL DEFAULT 'confirmed',
    registered_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_student_event (student_id, event_id),
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(id)   ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reg_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- 5. TABLE: audit_logs
--    Telemetry for Logins, Role Access, CRUD Actions & Errors
--    Practicals: 9, 10, 12, 14, 15
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    DEFAULT NULL,
    username    VARCHAR(50)     DEFAULT NULL,
    action      VARCHAR(100)    NOT NULL,
    ip_address  VARCHAR(45)     NOT NULL,
    user_agent  VARCHAR(255)    NOT NULL,
    details     TEXT            DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- 6. VIEW: v_registrations (Practical 8)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW v_registrations AS
SELECT
    er.id               AS registration_id,
    s.id                AS student_id,
    s.name              AS student_name,
    s.email             AS student_email,
    s.course            AS student_course,
    e.id                AS event_id,
    e.title             AS event_title,
    e.category          AS event_category,
    e.event_date        AS event_date,
    e.venue             AS event_venue,
    er.status           AS registration_status,
    er.registered_at    AS registered_at
FROM event_registrations er
JOIN students s ON er.student_id = s.id
JOIN events   e ON er.event_id   = e.id;


-- ------------------------------------------------------------
-- 7. STORED PROCEDURE: GetStudentRegistrations (Practical 8)
-- ------------------------------------------------------------
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS GetStudentRegistrations(
    IN p_student_id INT UNSIGNED
)
BEGIN
    SELECT
        e.title       AS event_title,
        e.category,
        e.event_date,
        e.venue,
        er.status,
        er.registered_at
    FROM event_registrations er
    JOIN events e ON er.event_id = e.id
    WHERE er.student_id = p_student_id
    ORDER BY e.event_date ASC;
END$$
DELIMITER ;


-- ------------------------------------------------------------
-- 8. SEED DATA: users (Password for all: 'Password@123')
--    BCRYPT hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ------------------------------------------------------------
INSERT INTO users (name, username, email, phone, role, course, semester, password_hash, status) VALUES
('Administrator', 'admin',         'admin@studenthub.local',  '9999988888', 'admin',   NULL,         1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Aarav Shah',    'aarav_shah',    'aarav.shah@example.com',  '9876543210', 'student', 'B.Tech CSE', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Priya Patel',   'priya_patel',   'priya.patel@example.com', '9123456780', 'student', 'B.Tech IT',  4, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Rohan Mehta',   'rohan_mehta',   'rohan.mehta@example.com', '8765432190', 'student', 'MCA',        2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Sneha Kapoor',  'sneha_kapoor',  'sneha.k@example.com',     '9988776655', 'student', 'BCA',        3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');


-- ------------------------------------------------------------
-- 9. SEED DATA: students (Minimum 20 records for Practical 11)
-- ------------------------------------------------------------
INSERT INTO students (name, email, phone, course, semester, status) VALUES
('Aarav Shah',      'aarav.shah@example.com',     '9876543210', 'B.Tech CSE', 3, 'active'),
('Priya Patel',     'priya.patel@example.com',    '9123456780', 'B.Tech IT',  4, 'active'),
('Rohan Mehta',     'rohan.mehta@example.com',    '8765432190', 'MCA',        2, 'active'),
('Sneha Kapoor',    'sneha.kapoor@example.com',   '9988776655', 'BCA',        3, 'active'),
('Karan Verma',     'karan.verma@example.com',    '9812345670', 'B.Tech CSE', 5, 'active'),
('Ananya Gupta',    'ananya.gupta@example.com',   '9701234560', 'B.Tech IT',  6, 'active'),
('Vikram Singh',    'vikram.singh@example.com',   '9601122334', 'MCA',        1, 'active'),
('Divya Nair',      'divya.nair@example.com',     '9555667788', 'BCA',        2, 'active'),
('Manish Joshi',    'manish.j@example.com',       '9444332211', 'B.Tech CSE', 4, 'active'),
('Tanvi Sharma',    'tanvi.s@example.com',        '9333221100', 'B.Tech IT',  2, 'active'),
('Aditya Rao',      'aditya.rao@example.com',     '9222110099', 'MCA',        3, 'active'),
('Pooja Kulkarni',  'pooja.k@example.com',        '9111009988', 'BCA',        4, 'active'),
('Harsh Vardhan',   'harsh.v@example.com',        '9000998877', 'B.Tech CSE', 6, 'active'),
('Meera Deshmukh',  'meera.d@example.com',        '9898989898', 'B.Tech IT',  3, 'active'),
('Kunal Rawat',     'kunal.r@example.com',        '9797979797', 'MCA',        4, 'active'),
('Neha Bhatia',     'neha.b@example.com',         '9696969696', 'BCA',        1, 'active'),
('Siddharth Bose',  'siddharth.b@example.com',    '9595959595', 'B.Tech CSE', 2, 'active'),
('Ritu Sen',        'ritu.sen@example.com',       '9494949494', 'B.Tech IT',  5, 'active'),
('Deepak Chawla',   'deepak.c@example.com',       '9393939393', 'MCA',        1, 'active'),
('Kavita Mishra',   'kavita.m@example.com',       '9292929292', 'BCA',        3, 'active');


-- ------------------------------------------------------------
-- 10. SEED DATA: events (Practical 12)
-- ------------------------------------------------------------
INSERT INTO events (title, category, description, event_date, venue, max_seats, status) VALUES
('Full-Stack Web Development Bootcamp', 'workshop', 'Hands-on training covering HTML5, CSS3, JS Fetch API, PHP, and MySQL.', '2026-10-25', 'Lab Block A - Room 101', 45, 'open'),
('AI & Deep Learning Seminar',          'seminar',  'Industry keynote on Transformers, Computer Vision, and generative AI models.', '2026-11-02', 'Main Auditorium', 150, 'open'),
('CodeStorm 2026 Hackathon',            'hackathon','24-hour inter-college coding sprint for smart campus problem solving.', '2026-11-12', 'Innovation Incubation Center', 80, 'open'),
('Ethical Hacking & Cyber Defense',     'workshop', 'Vulnerability assessment, penetration testing, and network security.', '2026-11-20', 'Cyber Lab Room 402', 40, 'open'),
('Annual Cultural Fest',                'cultural', 'Campus celebration featuring music, dance, theatrical acts, and arts.', '2026-11-28', 'University Amphitheatre', 400, 'open'),
('Campus Esports League',               'sports',   'Algorithmic gaming tournament and real-time strategy competition.', '2026-12-05', 'Student Activity Center', 60, 'closed');


-- ------------------------------------------------------------
-- 11. SEED DATA: event_registrations (Practical 8 & 14)
-- ------------------------------------------------------------
INSERT INTO event_registrations (student_id, event_id, status) VALUES
(1, 1, 'confirmed'),
(1, 3, 'confirmed'),
(2, 1, 'confirmed'),
(2, 2, 'confirmed'),
(3, 2, 'confirmed'),
(3, 3, 'confirmed'),
(4, 4, 'confirmed'),
(4, 5, 'confirmed'),
(5, 1, 'confirmed'),
(6, 2, 'confirmed'),
(7, 3, 'confirmed'),
(8, 5, 'confirmed'),
(9, 1, 'waitlisted');


-- ------------------------------------------------------------
-- 12. SEED DATA: audit_logs (Practical 9, 10, 14)
-- ------------------------------------------------------------
INSERT INTO audit_logs (user_id, username, action, ip_address, user_agent, details) VALUES
(1, 'admin',      'ADMIN_LOGIN',     '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Admin logged in successfully via PHP Session.'),
(2, 'aarav_shah', 'STUDENT_LOGIN',   '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Student logged in successfully.'),
(1, 'admin',      'STUDENT_CREATE',  '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Added new student record: Aarav Shah.'),
(1, 'admin',      'EVENT_POSTER_UP', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Uploaded event poster for Web Bootcamp.');
