# StudentHub — Practical 8: MySQL Schema Design, ER Model, PDO Connectivity, and Prepared Statements

**Course Outcome (CO) Mapping:** CO1, CO5  
**Context:** Implemented directly on top of the StudentHub web application (Practical 7).  
**Tools & Technologies:** MySQL, phpMyAdmin, PHP PDO, Apache (XAMPP).  
**Total Hours:** Implementation: 4 hours | Total Engagement: 6 hours  

---

## 1. Problem Definition
Design a normalized relational database schema for **StudentHub** supporting student enrollment, campus events, and event registrations. Establish a secure, high-performance database connection using PHP Data Objects (PDO) with prepared statements to eliminate SQL injection vulnerabilities while preserving the complete StudentHub website UI/UX.

---

## 2. Key Questions & Analytical Breakdown

### Q1: Is the schema normalized properly?
**Yes.** The schema strictly adheres to **Third Normal Form (3NF)**:
- **1NF (Atomic Attributes):** Eliminates repeating groups and multi-valued attributes. No comma-separated strings; each registration is a discrete record.
- **2NF (No Partial Dependencies):** All non-key attributes are fully dependent on the primary key. Student attributes (`name`, `course`, `semester`) and Event attributes (`title`, `venue`, `max_seats`) are decoupled from registration statuses.
- **3NF (No Transitive Dependencies):** Non-prime attributes depend solely and directly on the primary key (`id`), eliminating update, insertion, and deletion anomalies.

### Q2: Are primary keys and foreign keys correctly defined?
**Yes.**
- **Primary Keys:** Every table defines `id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
- **Foreign Keys:** 
  - `fk_reg_student` references `students(id)` with `ON DELETE CASCADE ON UPDATE CASCADE`.
  - `fk_reg_event` references `events(id)` with `ON DELETE CASCADE ON UPDATE CASCADE`.
- **Integrity Constraints:** Composite `UNIQUE (student_id, event_id)` prevents duplicate event signups.

### Q3: Is the database connection handled securely?
**Yes.**
- **PHP Data Objects (PDO):** Configured with `PDO::ATTR_EMULATE_PREPARES => false` for real server-side prepared statements.
- **Error Handling:** Employs `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` inside try/catch blocks without exposing sensitive credentials.
- **Encoding:** Configured with `utf8mb4` character set to prevent multibyte injection attacks.

---

## 3. Post-Laboratory Deliverables Checklist

1. **SQL Dump:** [`studenthub.sql`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/studenthub.sql) (Complete DDL, DML seed data, view `v_registrations`, and stored procedure).
2. **Database Connection File:** [`db.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/db.php) (Configured with PDO options and multi-port fallback).
3. **Connection Test Screenshot:** Access [`test_connection.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/test_connection.php) in your browser and capture the screenshot showing the green **CONNECTED** badge and prepared statement verification.

---

## 4. Advanced & Intermediate Extensions

### Intermediate Extension: PHP PDO and Prepared Statements
- Implemented in [`process.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/process.php), [`view_students.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/view_students.php), and [`view_events.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/view_events.php).
- Uses parameterized named placeholders (e.g. `:name`, `:email`, `:phone`) ensuring user input is treated strictly as data values.

### Advanced Extension: MySQL Stored Procedures
- Implemented in [`stored_procedures.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/stored_procedures.php) and [`studenthub.sql`](file:///c:/xampp/htdocs/studenthub_practical7/practical8/studenthub.sql).
- Procedure `GetStudentRegistrations(IN p_student_id INT UNSIGNED)` encapsulates multi-table join and ordering logic inside the MySQL server catalog.

---

## 5. Website File Structure in `practical8/`

```
practical8/
├── index.html                # StudentHub Homepage with Practical 8 Lab Banner
├── index.php                 # PHP entry point serving index.html
├── test_connection.php       # Diagnostic & Post-Lab Screenshot Tool (StudentHub Theme)
├── er_model.html             # Interactive ER Diagram & 3NF Normalization Breakdown
├── stored_procedures.php     # Advanced Extension: MySQL Stored Procedure Runner
├── registration.html         # StudentHub Registration Form (Practical 7 & 8)
├── process.php               # Form Processing (PDO Prepared Statements + JSON/CSV)
├── view_students.php         # Live Database Student Viewer (Prepared Statements)
├── view_events.php           # Campus Events & Registration Booking (Prepared Statements)
├── db.php                    # PDO Database Connection & Port Auto-Discovery
├── studenthub.sql            # 3NF Normalized Database Schema & Seed Data
├── style.css                 # StudentHub Original Theme & Dark Mode CSS
├── script.js                 # StudentHub Original JavaScript & Interactivity
├── dashboard.html            # StudentHub Dashboard
├── courses.html              # Courses Catalog
├── attendance.html           # Attendance Tracker
├── assignments.html          # Assignments Portal
├── results.html              # Academic Results
├── library.html              # Library Catalog
├── events.html               # Events Information
├── login.html                # Login Page
└── profile.html              # Student Profile
```

---

## 6. How to Run Locally

1. Start **Apache** and **MySQL** in the **XAMPP Control Panel**.
2. Open your web browser and navigate to:
   ```
   http://localhost/studenthub_practical7/practical8/
   ```
3. To take the required laboratory screenshot, open:
   ```
   http://localhost/studenthub_practical7/practical8/test_connection.php
   ```
