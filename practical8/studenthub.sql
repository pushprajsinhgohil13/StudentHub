-- ============================================================
-- StudentHub - Practical 8
-- MySQL Schema Design, ER Model, PDO Connectivity
-- ============================================================
-- Database: studenthub
-- Tables:   students, events, event_registrations
-- ============================================================


-- ============================================================
-- 1. CREATE DATABASE
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;


-- ============================================================
-- 2. TABLE: students
--    Stores all registered student details
-- ============================================================

CREATE TABLE IF NOT EXISTS students (

    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    phone       CHAR(10)        NOT NULL,
    course      VARCHAR(50)     NOT NULL,
    semester    TINYINT         NOT NULL CHECK (semester BETWEEN 1 AND 8),
    message     TEXT            DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. TABLE: events
--    Stores all campus events
-- ============================================================

CREATE TABLE IF NOT EXISTS events (

    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title       VARCHAR(200)    NOT NULL,
    category    ENUM('workshop','seminar','hackathon','cultural','other')
                                NOT NULL DEFAULT 'other',
    description TEXT            NOT NULL,
    event_date  DATE            NOT NULL,
    venue       VARCHAR(200)    NOT NULL,
    max_seats   SMALLINT        NOT NULL DEFAULT 50,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. TABLE: event_registrations
--    Junction table linking students to events (M:N)
-- ============================================================

CREATE TABLE IF NOT EXISTS event_registrations (

    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    student_id      INT UNSIGNED    NOT NULL,
    event_id        INT UNSIGNED    NOT NULL,
    registered_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('confirmed','waitlisted','cancelled')
                                    NOT NULL DEFAULT 'confirmed',

    PRIMARY KEY (id),

    -- Prevent duplicate registrations
    UNIQUE KEY uq_student_event (student_id, event_id),

    -- Foreign key: student must exist
    CONSTRAINT fk_reg_student
        FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Foreign key: event must exist
    CONSTRAINT fk_reg_event
        FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. SEED DATA: students
-- ============================================================

INSERT INTO students (name, email, phone, course, semester, message) VALUES
    ('Aarav Shah',       'aarav.shah@example.com',       '9876543210', 'B.Tech CSE', 3, 'Excited to join!'),
    ('Priya Patel',      'priya.patel@example.com',      '9123456780', 'B.Tech IT',  2, NULL),
    ('Rohan Mehta',      'rohan.mehta@example.com',      '8765432190', 'MCA',        1, 'Looking forward to events.'),
    ('Sneha Kapoor',     'sneha.kapoor@example.com',     '9988776655', 'BCA',        4, NULL),
    ('Karan Verma',      'karan.verma@example.com',      '9812345670', 'B.Tech CSE', 5, 'Hackathon ready!'),
    ('Ananya Gupta',     'ananya.gupta@example.com',     '9701234560', 'B.Tech IT',  6, NULL),
    ('Vikram Singh',     'vikram.singh@example.com',     '9601122334', 'MCA',        2, 'Ready to learn.'),
    ('Divya Nair',       'divya.nair@example.com',       '9555667788', 'BCA',        3, NULL);


-- ============================================================
-- 6. SEED DATA: events
-- ============================================================

INSERT INTO events (title, category, description, event_date, venue, max_seats) VALUES
    ('Python for Beginners',
     'workshop',
     'A hands-on workshop covering Python basics, data types, control flow, and OOP.',
     '2026-10-20', 'Lab Block A - Room 101', 40),

    ('AI & Machine Learning Seminar',
     'seminar',
     'Industry experts discuss the latest trends in AI, ML, and deep learning.',
     '2026-10-25', 'Seminar Hall 1', 100),

    ('Code Storm 2026 Hackathon',
     'hackathon',
     '24-hour hackathon. Build innovative solutions for real-world problems.',
     '2026-11-01', 'Innovation Hub, Block C', 120),

    ('Annual Cultural Fest',
     'cultural',
     'Dance, music, drama, and art competitions. Celebrate campus talent!',
     '2026-11-10', 'Main Auditorium', 500),

    ('Web Development Bootcamp',
     'workshop',
     'Full-stack web dev bootcamp covering HTML, CSS, JavaScript, PHP and MySQL.',
     '2026-11-15', 'Lab Block B - Room 205', 35),

    ('Cybersecurity Awareness Seminar',
     'seminar',
     'Learn about ethical hacking, data privacy, and best security practices.',
     '2026-11-20', 'Seminar Hall 2', 80);


-- ============================================================
-- 7. SEED DATA: event_registrations
-- ============================================================

INSERT INTO event_registrations (student_id, event_id, status) VALUES
    (1, 1, 'confirmed'),
    (1, 3, 'confirmed'),
    (2, 1, 'confirmed'),
    (2, 4, 'confirmed'),
    (3, 2, 'confirmed'),
    (3, 3, 'waitlisted'),
    (4, 4, 'confirmed'),
    (5, 3, 'confirmed'),
    (5, 5, 'confirmed'),
    (6, 2, 'confirmed'),
    (6, 6, 'confirmed'),
    (7, 5, 'confirmed'),
    (8, 4, 'confirmed'),
    (8, 6, 'confirmed');


-- ============================================================
-- 8. VIEW: v_registrations
--    Joins all three tables for easy querying
-- ============================================================

CREATE OR REPLACE VIEW v_registrations AS
    SELECT
        er.id               AS reg_id,
        s.name              AS student_name,
        s.email             AS student_email,
        s.course,
        e.title             AS event_title,
        e.category,
        e.event_date,
        e.venue,
        er.status,
        er.registered_at
    FROM event_registrations er
    JOIN students s ON er.student_id = s.id
    JOIN events   e ON er.event_id   = e.id;


-- ============================================================
-- 9. STORED PROCEDURE (Advanced Extension)
--    Get all registrations for a specific student
-- ============================================================

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
