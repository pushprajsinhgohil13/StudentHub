# StudentHub — Practical 15: Final System Integration, Automated Quality Assurance Suite, End-to-End Diagnostics, and Master Deliverable Reference

**Course Outcome (CO) Mapping:** CO1, CO2, CO3, CO4, CO5 (Comprehensive Capstone Integration)  
**Domain:** System Integration, Software Quality Assurance, Production Diagnostics & Deployment Readiness  
**Tools & Technologies:** PHP 8, MySQL InnoDB, Apache, Automated QA Assertions, Modern HTML5/CSS3  
**Total Hours:** Implementation: 4 hours | Total Engagement: 6 hours  

---

## 1. Problem Definition
Conduct a rigorous end-to-end integration and quality assurance evaluation of the complete **StudentHub** web portal across all 8 laboratory practicals (Practicals 8 through 15). Build an automated system diagnostics tool (`system_status.php`) that executes automated assertions across database connectivity, schema integrity, session security policies, upload directory protection, REST API availability, and administrative seed accounts. Compile the complete master implementation reference guide and verify full operational readiness for production deployment.

---

## 2. Key Questions & Analytical Breakdown

### Q1: How does automated integration testing verify cross-practical interoperability?
**By validating data contracts and security boundaries across decoupled modules:**
- In large web applications, modules developed across multiple sprints or laboratory sessions often exhibit subtle integration regressions (e.g., changes to password hashing schemes breaking login, or table column renaming breaking API serializers).
- `system_status.php` and `test_integration.php` execute a unified diagnostic sequence:
  1. **Connectivity Layer:** Tests simultaneous active connections via both **MySQLi** and **PDO**.
  2. **Relational Schema Layer:** Inspects 5 core tables (`users`, `students`, `events`, `event_registrations`, `audit_logs`) and checks row counts and foreign keys.
  3. **Security Pipeline:** Verifies runtime session cookie parameters (`HttpOnly = true`), evaluates 15-minute idle timeout policies, and confirms BCRYPT cost factors.
  4. **Filesystem Defense:** Confirms write permissions in `uploads/posters/` and parses `.htaccess` rules to verify execution denial.
  5. **API & Service Layer:** Confirms file existence and route availability for `api/students.php`.

### Q2: What criteria distinguish between PASS, WARN, and FAIL in system diagnostics?
**A three-tier severity hierarchy:**
- ❌ **FAIL (Critical Blocker):** An essential capability is broken or unavailable (e.g., database connection down, mandatory table missing, admin seed account deleted). The application cannot function safely.
- ⚠️ **WARN (Non-blocking Advisory):** The application functions correctly, but a security hardening or configuration recommendation is not optimal (e.g., upload directory is writable but `.htaccess` execution shield is missing, or session cookies lack secure flags in HTTP development environments).
- ✅ **PASS (Verified Compliant):** The feature, configuration, or security assertion meets or exceeds production standards.

### Q3: How is zero-downtime, idempotent database initialization achieved?
**Through declarative DDL with `IF NOT EXISTS` and relational seed scripts:**
- In `studenthub.sql`, all table definitions employ `CREATE TABLE IF NOT EXISTS`.
- Seed accounts utilize `ON DUPLICATE KEY UPDATE` or conditioned inserts:
  - Default Admin Account: `admin` / `Password@123` (Role: `admin`)
  - Default Student Account: `student1` / `Password@123` (Role: `student`)
- Running `studenthub.sql` multiple times does not corrupt existing data or produce duplicate key errors.

---

## 3. Extensions Implemented

### Intermediate Extension: Automated QA Runner (`system_status.php`)
- Interactive diagnostic dashboard with color-coded status badges:
  - Real-time database table row counts.
  - Runtime session policy auditing.
  - Filesystem permission and Apache configuration parsing.
  - Re-run capability with instantaneous status refresh.

### Advanced Extension: Master Practicals Reference Guide (`practicals_reference_guide.html`)
- Comprehensive, 1,260+ line reference manual covering:
  - Detailed syllabus mapping for Practicals 8, 9, 10, 11, 12, 13, 14, and 15.
  - Learning objectives, file maps, key code snippets, and credential matrices.
  - Master File Index linking every script in the repository to its exact educational purpose.
  - Formatted for both desktop viewing and clean, multi-page print export.

---

## 4. Master Practicals Implementation Sitemap (8 to 15)

| Practical | Educational Objective & Core Focus | Primary Implementation Files | Test Suite / Verification |
| :--- | :--- | :--- | :--- |
| **Prac 8** | MySQL Schema Design, ER Model, PDO Connectivity | `studenthub.sql`, `db.php`, `view_students.php`, `view_events.php` | `practical8/test_connection.php` |
| **Prac 9** | Secure User Registration, BCRYPT, CSRF, Audit Logs | `register.php`, `schema.sql`, `audit_logs.php` | `practical9/test_report.php` |
| **Prac 10** | Secure Login, Session Fixation Defense, RBAC, Remember-Me | `login.php`, `logout.php`, `student_dashboard.php` | `practical10/test_session.php` |
| **Prac 11** | Student Management CRUD, Search/Filter, CSV Export, Soft Delete | `students_crud.php`, `export_students_csv.php` | `practical11/test_crud.php` |
| **Prac 12** | Event Management CRUD, Poster Upload, MIME finfo, .htaccess | `events_crud.php`, `uploads/posters/.htaccess` | `practical12/test_upload.php` |
| **Prac 13** | PHP RESTful JSON API & Asynchronous Debounced Fetch UI | `api/students.php`, `ajax_students.html` | `practical13/test_api.php` |
| **Prac 14** | Admin Dashboard, SQL Aggregations, User Governance Toggle | `admin_dashboard.php`, `db.php` | `practical14/test_admin.php` |
| **Prac 15** | Master Integration, Automated QA Suite, System Diagnostics | `system_status.php`, `practicals_reference_guide.html` | `practical15/test_integration.php` |

---

## 5. Post-Laboratory Deliverables Checklist

1. **System Diagnostics & QA Suite:** [`system_status.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical15/system_status.php) (10+ automated QA checks).
2. **Master Integration Test Suite:** [`test_integration.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical15/test_integration.php) (Cross-practical test runner).
3. **Master Implementation Reference Guide:** [`practicals_reference_guide.html`](file:///c:/xampp/htdocs/studenthub_practical7/practical15/practicals_reference_guide.html) (Complete 1,260+ line guide).
4. **Complete Master Database Schema:** [`studenthub.sql`](file:///c:/xampp/htdocs/studenthub_practical7/practical15/studenthub.sql) (DDL, VIEW, Stored Procedure, and seed data).
5. **Database Connector & RBAC Guard:** [`db.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical15/db.php).

---

## 6. Step-by-Step Execution & Deployment Guide

### Step 1: Start XAMPP Server Environment
1. Launch the **XAMPP Control Panel**.
2. Start the **Apache** web server module.
3. Start the **MySQL** database server module.

### Step 2: Initialize Database
1. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Create or select database `studenthub`.
3. Import `studenthub.sql` from `c:/xampp/htdocs/studenthub_practical7/studenthub.sql`.

### Step 3: Run Master Integration Diagnostics
Open your browser and navigate to:
```
http://localhost/studenthub_practical7/practical15/system_status.php
```
Verify that all system components return green **PASS** badges.

### Step 4: Run Multi-Module Test Suite
Navigate to:
```
http://localhost/studenthub_practical7/practical15/test_integration.php
```
Confirm that all 8 practical modules pass end-to-end integration verification.

---

## 7. Directory File Structure in `practical15/`

```
practical15/
├── README.md                       # This academic report & documentation
├── test_integration.php            # Multi-module cross-practical test runner
├── system_status.php               # Automated QA diagnostics dashboard
├── practicals_reference_guide.html # Complete 1,260+ line reference manual
├── db.php                          # Database connection & security helpers
├── studenthub.sql                  # Master unified database schema
├── index.php                       # Portal entry point
├── index.html                      # StudentHub landing page
├── style.css                       # Portal design system
└── script.js                       # Shared frontend behavior
```
