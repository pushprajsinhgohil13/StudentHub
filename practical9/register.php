<?php
/**
 * StudentHub - Secure User Registration
 * Features:
 * - Frontend and backend validation
 * - Duplicate username and email check
 * - Password hashing via password_hash() (BCRYPT)
 * - MySQLi Prepared Statements
 * - CSRF Protection & Security Audit Logging
 */

require_once 'db.php';
$conn = get_mysqli();

$feedback = null;
$formData = [
    'name'     => '',
    'username' => '',
    'email'    => '',
    'phone'    => '',
    'course'   => '',
    'semester' => '1'
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // 1. Verify CSRF Token (Advanced Extension)
    if (!verify_csrf_token($csrfToken)) {
        $feedback = [
            'type' => 'danger',
            'msg'  => 'Security validation failed (Invalid or expired CSRF token). Please try again.'
        ];
        if ($conn) {
            log_registration_audit($conn, $_POST['username'] ?? '', $_POST['email'] ?? '', 'CSRF_FAILURE', 'Invalid or missing CSRF token.');
        }
    } else {
        // Retrieve and trim inputs
        $name            = trim($_POST['name'] ?? '');
        $username        = trim($_POST['username'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $course          = trim($_POST['course'] ?? '');
        $semester        = (int)($_POST['semester'] ?? 1);
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $formData = [
            'name'     => $name,
            'username' => $username,
            'email'    => $email,
            'phone'    => $phone,
            'course'   => $course,
            'semester' => (string)$semester
        ];

        $errors = [];

        // 2. Consistent Backend Validation
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Full Name must be at least 2 characters.";
        }
        if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email address.";
        }
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = "Phone number must be exactly 10 digits.";
        }
        if (empty($course)) {
            $errors[] = "Please select your degree course.";
        }
        if ($semester < 1 || $semester > 8) {
            $errors[] = "Semester must be between 1 and 8.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
        }
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }

        if (!empty($errors)) {
            $feedback = ['type' => 'danger', 'msg' => implode("<br>", $errors)];
            if ($conn) {
                log_registration_audit($conn, $username, $email, 'VALIDATION_ERROR', implode("; ", $errors));
            }
        } elseif (!$conn) {
            $feedback = ['type' => 'danger', 'msg' => "Database connection error: " . ($dbError ?? 'MySQL unreachable.')];
        } else {
            // 3. Duplicate Checks (Prepared Statements)
            // A. Check duplicate username
            $stmtUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmtUser->bind_param("s", $username);
            $stmtUser->execute();
            $stmtUser->store_result();

            if ($stmtUser->num_rows > 0) {
                $feedback = [
                    'type' => 'warning',
                    'msg'  => "Username '<strong>" . htmlspecialchars($username) . "</strong>' is already registered. Please choose another username."
                ];
                log_registration_audit($conn, $username, $email, 'DUPLICATE_USERNAME', 'Attempted registration with existing username.');
                $stmtUser->close();
            } else {
                $stmtUser->close();

                // B. Check duplicate email
                $stmtEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtEmail->bind_param("s", $email);
                $stmtEmail->execute();
                $stmtEmail->store_result();

                if ($stmtEmail->num_rows > 0) {
                    $feedback = [
                        'type' => 'warning',
                        'msg'  => "Email '<strong>" . htmlspecialchars($email) . "</strong>' is already registered to an existing account."
                    ];
                    log_registration_audit($conn, $username, $email, 'DUPLICATE_EMAIL', 'Attempted registration with existing email.');
                    $stmtEmail->close();
                } else {
                    $stmtEmail->close();

                    // 4. Secure Password Hashing
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

                    // 5. Insert Record using MySQLi Prepared Statement
                    $insertStmt = $conn->prepare("INSERT INTO users (name, username, email, phone, course, semester, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->bind_param("sssssis", $name, $username, $email, $phone, $course, $semester, $passwordHash);

                    if ($insertStmt->execute()) {
                        $newUserId = $conn->insert_id;
                        $feedback = [
                            'type' => 'success',
                            'msg'  => "🎉 Account registered successfully! Welcome, <strong>" . htmlspecialchars($name) . "</strong> (Account ID: #" . $newUserId . "). Your password has been securely hashed using BCRYPT."
                        ];
                        // 6. Audit Log Entry
                        log_registration_audit($conn, $username, $email, 'SUCCESS', "User ID #$newUserId registered successfully.");

                        // Reset form fields
                        $formData = ['name' => '', 'username' => '', 'email' => '', 'phone' => '', 'course' => '', 'semester' => '1'];
                    } else {
                        $feedback = ['type' => 'danger', 'msg' => "Registration failed: " . $insertStmt->error];
                    }
                    $insertStmt->close();
                }
            }
        }
    }
}

// Generate active CSRF token for the form
$token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Create Account</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reg-container {
            max-width: 650px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        body.dark-mode .reg-container {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }
        body.dark-mode .form-group label {
            color: #cbd5e1;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        body.dark-mode .form-input {
            background: #0f172a;
            border-color: #475569;
            color: #f1f5f9;
        }
        .form-input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
        }
        .strength-meter {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        .strength-text {
            font-size: 12px;
            margin-top: 4px;
            font-weight: 600;
        }
        .alert-box {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-submit {
            width: 100%;
            background: #0d6efd;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #0b5ed7; }
    </style>
</head>

<body>

    <!-- ================= HEADER ================= -->
    <header>
        <div class="header-container">
            <div class="logo">
                <h2>🎓 StudentHub</h2>
            </div>

            <!-- Hamburger Button -->
            <button class="hamburger" id="hamburgerBtn" type="button">☰</button>

            <!-- Navigation -->
            <nav id="mainNav">
                <a href="index.html">Home</a>
                <a href="register.php" style="color: yellow; text-decoration: underline;">Create Account</a>
                <a href="login.php">Sign In</a>
                <a href="users.php">Registered Users</a>
                <a href="audit_logs.php">Security Audit Logs</a>
                <a href="test_report.php">Test Suite</a>
                <a href="dashboard.html">Dashboard</a>
            </nav>

            <!-- Theme Toggle -->
            <button class="theme-btn" id="themeToggle" type="button">🌙 Dark Mode</button>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="hero">
        <h1>🔒 Secure User Registration</h1>
        <p>
            Create your StudentHub account with encrypted password storage, duplicate detection, and security auditing.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="padding: 0 15px;">

        <div class="reg-container">
            <h2 style="margin-top: 0; font-size: 22px; margin-bottom: 8px;">Create an Account</h2>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                All credentials are securely protected. Passwords are encrypted with industry-standard BCRYPT hashing.
            </p>

            <?php if ($feedback): ?>
                <div class="alert-box alert-<?php echo htmlspecialchars($feedback['type']); ?>">
                    <?php echo $feedback['msg']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registrationForm" onsubmit="return validateFrontend();">
                <!-- CSRF Token (Advanced Extension) -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Aarav Patel" required value="<?php echo htmlspecialchars($formData['name']); ?>">
                </div>

                <div class="form-row">
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username (Unique) *</label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="e.g. aarav_patel" pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 characters: letters, numbers, underscores" required value="<?php echo htmlspecialchars($formData['username']); ?>">
                        <small style="color: #64748b; font-size: 11px;">Alphanumeric &amp; underscores (3-20 chars)</small>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address (Unique) *</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="e.g. aarav@example.com" required value="<?php echo htmlspecialchars($formData['email']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" class="form-input" placeholder="9876543210" pattern="^[0-9]{10}$" maxlength="10" required value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>

                    <!-- Degree Course -->
                    <div class="form-group">
                        <label for="course">Degree Course *</label>
                        <select id="course" name="course" class="form-input" required>
                            <option value="">-- Select Course --</option>
                            <option value="B.Tech CSE" <?php if ($formData['course'] === 'B.Tech CSE') echo 'selected'; ?>>B.Tech CSE</option>
                            <option value="B.Tech IT"  <?php if ($formData['course'] === 'B.Tech IT') echo 'selected'; ?>>B.Tech IT</option>
                            <option value="MCA"        <?php if ($formData['course'] === 'MCA') echo 'selected'; ?>>MCA</option>
                            <option value="BCA"        <?php if ($formData['course'] === 'BCA') echo 'selected'; ?>>BCA</option>
                        </select>
                    </div>
                </div>

                <!-- Semester -->
                <div class="form-group">
                    <label for="semester">Semester (1 - 8) *</label>
                    <select id="semester" name="semester" class="form-input" required>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ((int)$formData['semester'] === $i) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-row">
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="At least 8 chars" required oninput="checkPasswordStrength(this.value)">
                        <div class="strength-meter"><div id="strengthBar" class="strength-bar"></div></div>
                        <div id="strengthText" class="strength-text" style="color: #64748b;">Strength: None</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="margin-top: 10px;">
                    Register Secure Account
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #64748b;">
                Already have an account? <a href="login.php" style="color: #0d6efd; font-weight: 600;">Sign In Here</a>
            </div>
        </div>

    </main>

    <!-- ================= FOOTER ================= -->
    <footer style="margin-top: 50px; text-align: center; padding: 20px; font-size: 14px; border-top: 1px solid #ddd; background: white;">
        <p style="margin: 0; color: #666;">
            StudentHub &bull; Academic Portal &bull; All Rights Reserved
        </p>
    </footer>

    <script src="script.js"></script>
    <script>
        // Password strength meter
        function checkPasswordStrength(pw) {
            let score = 0;
            if (pw.length >= 8) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[a-z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^a-zA-Z0-9]/.test(pw)) score++;

            const bar = document.getElementById('strengthBar');
            const txt = document.getElementById('strengthText');

            if (pw.length === 0) {
                bar.style.width = '0%';
                txt.textContent = 'Strength: None';
                txt.style.color = '#64748b';
            } else if (score < 3) {
                bar.style.width = '30%';
                bar.style.backgroundColor = '#ef4444';
                txt.textContent = 'Strength: Weak (Needs uppercase, number, symbol)';
                txt.style.color = '#ef4444';
            } else if (score < 5) {
                bar.style.width = '70%';
                bar.style.backgroundColor = '#f59e0b';
                txt.textContent = 'Strength: Medium';
                txt.style.color = '#f59e0b';
            } else {
                bar.style.width = '100%';
                bar.style.backgroundColor = '#10b981';
                txt.textContent = 'Strength: Strong (Secure)';
                txt.style.color = '#10b981';
            }
        }

        // Frontend validation
        function validateFrontend() {
            const pw = document.getElementById('password').value;
            const cpw = document.getElementById('confirm_password').value;
            if (pw !== cpw) {
                alert('Passwords do not match. Please re-enter your password.');
                return false;
            }
            if (pw.length < 8) {
                alert('Password must be at least 8 characters long.');
                return false;
            }
            return true;
        }
    </script>

</body>
</html>
