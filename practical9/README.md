# StudentHub — Practical 9: Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing

**Course Outcome (CO) Mapping:** CO1, CO3, CO5  
**Domain:** Web Application Security & Account Creation  
**Tools & Technologies:** PHP, MySQLi, Apache (XAMPP)  
**Total Hours:** Implementation: 4 hours | Total Engagement: 6 hours  

---

## 1. Problem Definition
Implement secure user registration by inserting user details into MySQL using **MySQLi**. Validate input on both frontend and backend, check for duplicate email/username, hash passwords using `password_hash()`, and display appropriate status messages while keeping the website interface clean and natural.

---

## 2. Key Questions & Analytical Breakdown

### Q1: Is `password_hash()` used before storing passwords?
**Yes.**
- The application uses `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])`.
- Passwords are never stored in plain text. Stored hashes are 60-character strings prefixed with `$2y$10$`.
- Password verification during sign-in uses `password_verify($password, $user['password_hash'])`.

### Q2: Is duplicate email/username checked?
**Yes.**
- **Duplicate Username Check:**  
  `SELECT id FROM users WHERE username = ? LIMIT 1` (Prepared statement). If found, blocks registration with a warning and records a `DUPLICATE_USERNAME` audit log.
- **Duplicate Email Check:**  
  `SELECT id FROM users WHERE email = ? LIMIT 1` (Prepared statement). If found, blocks registration with a warning and records a `DUPLICATE_EMAIL` audit log.
- **Database Engine Constraints:**  
  Tables enforce `CONSTRAINT uq_users_username UNIQUE (username)` and `CONSTRAINT uq_users_email UNIQUE (email)`.

### Q3: Are frontend and backend validations consistent?
**Yes.**
- **Full Name:** Minimum 2 characters.
- **Username:** 3–20 characters, alphanumeric and underscore (`/^[a-zA-Z0-9_]{3,20}$/`).
- **Email:** Valid email format (`FILTER_VALIDATE_EMAIL` on backend, `type="email"` on frontend).
- **Phone:** Exactly 10 digits (`/^[0-9]{10}$/`).
- **Password Strength:** Minimum 8 characters with at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol.
- **Confirm Password:** Both passwords must match identically.

---

## 3. Extensions Implemented

### Intermediate Extension: MySQLi Prepared Statements
- Handled in [`register.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/register.php), [`login.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/login.php), and [`users.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/users.php).
- Uses `$stmt = $mysqli->prepare(...)`, `$stmt->bind_param(...)`, and `$stmt->execute()` to prevent SQL injection.

### Advanced Extension: CSRF Token & Security Audit Logging
1. **CSRF Token:**  
   - Generated with `bin2hex(random_bytes(32))` stored in PHP `$_SESSION['csrf_token']`.  
   - Validated on POST using timing-attack safe `hash_equals()`.
2. **Audit Logging:**  
   - Table `registration_audit_logs` records every attempt with client IP address, user agent, attempted username/email, status code (`SUCCESS`, `DUPLICATE_USERNAME`, `DUPLICATE_EMAIL`, `VALIDATION_ERROR`, `CSRF_FAILURE`), and details.

---

## 4. Post-Laboratory Deliverables Checklist

1. **Database Schema:** [`schema.sql`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/schema.sql) (`users` table, `registration_audit_logs`, unique constraints, and seed data).
2. **Registration Code:** [`register.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/register.php) and [`db.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/db.php).
3. **Registration Test Report Matrix:** Generated live via [`test_report.php`](file:///c:/xampp/htdocs/studenthub_practical7/practical9/test_report.php) showing:
   - Valid registration test case
   - Invalid input test case
   - Duplicate username test case
   - Duplicate email test case
   - CSRF security defense test case

---

## 5. File Structure in `practical9/`

```
practical9/
├── index.html            # StudentHub Homepage with Updated Navigation
├── index.php             # PHP Entry Point serving index.html
├── register.php          # Secure Registration Form & Backend Processor
├── login.php             # User Authentication via password_verify()
├── users.php             # Directory of Registered Accounts (BCRYPT hash verification)
├── audit_logs.php        # Security Audit Logs Viewer (Advanced Extension)
├── test_report.php       # Automated Test Suite Runner & Verification Matrix
├── db.php                # MySQLi Database Connection, CSRF & Audit Logger
├── schema.sql            # MySQL Database DDL with Constraints & Audit Table
├── style.css             # StudentHub Theme & Dark Mode CSS
└── script.js             # StudentHub Client Scripts & Interactivity
```

---

## 6. How to Run Locally

1. Start **Apache** and **MySQL** in the **XAMPP Control Panel**.
2. Open your web browser:
   - **Create Account:** `http://localhost/studenthub_practical7/practical9/register.php`
   - **Sign In:** `http://localhost/studenthub_practical7/practical9/login.php`
   - **Registered Users:** `http://localhost/studenthub_practical7/practical9/users.php`
   - **Audit Logs:** `http://localhost/studenthub_practical7/practical9/audit_logs.php`
   - **Test Suite Runner:** `http://localhost/studenthub_practical7/practical9/test_report.php`
