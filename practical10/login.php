<?php
/**
 * StudentHub - Practical 10: Secure Login with Sessions, RBAC, Timeout, and Remember-Me
 */

require_once 'db.php';
start_secure_session();

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit;
}

$conn = get_mysqli();
$feedback = null;

// Handle messages from redirects
if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $feedback = ['type' => 'success', 'msg' => '✅ You have been logged out securely. Session destroyed.'];
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'timeout') {
    $feedback = ['type' => 'warning', 'msg' => '⚠️ Session Expired: You were automatically logged out due to 15 minutes of inactivity.'];
} elseif (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $feedback = ['type' => 'danger', 'msg' => '🔒 Unauthorized: Please sign in to access the protected dashboard.'];
} elseif (isset($_GET['error']) && $_GET['error'] === 'forbidden') {
    $feedback = ['type' => 'danger', 'msg' => '⛔ Access Denied: Admin privileges required to view that page.'];
}

// Handle Login Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $feedback = ['type' => 'danger', 'msg' => 'Please enter both your username/email and password.'];
    } elseif (!$conn) {
        $feedback = ['type' => 'danger', 'msg' => 'Database connection failed.'];
    } else {
        // Query user with prepared statement
        $user = null;
        try {
            $stmt = $conn->prepare("SELECT id, name, username, email, role, course, semester, password_hash, status FROM users WHERE username = ? OR email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        } catch (Throwable $e) {
            try {
                $conn->query("ALTER TABLE users ADD COLUMN role ENUM('admin', 'student') NOT NULL DEFAULT 'student' AFTER phone");
                $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
                $stmt = $conn->prepare("SELECT id, name, username, email, role, course, semester, password_hash, status FROM users WHERE username = ? OR email = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("ss", $identifier, $identifier);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            } catch (Throwable $inner) {
                $user = null;
            }
        }

        if ($user && $user['status'] === 'inactive') {
            $feedback = ['type' => 'danger', 'msg' => '⚠️ Account Inactive: Your account has been temporarily disabled by an administrator.'];
            log_audit_action('LOGIN_BLOCKED_INACTIVE', "Account inactive: {$user['username']}", $user['id'], $user['username']);
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            // 1. Regenerate Session ID to prevent session fixation
            session_regenerate_id(true);

            // 2. Set Session Variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['course'] = $user['course'];
            $_SESSION['semester'] = $user['semester'];
            $_SESSION['LAST_ACTIVITY'] = time();

            // 3. Intermediate Extension: Update last_login timestamp
            $now = date('Y-m-d H:i:s');
            $upStmt = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            if ($upStmt) {
                $upStmt->bind_param("si", $now, $user['id']);
                $upStmt->execute();
                $upStmt->close();
            }

            // 4. Advanced Extension: Remember-Me Token
            if ($remember) {
                set_remember_cookie($user['id']);
            }

            // 5. Audit Log
            log_audit_action('LOGIN_SUCCESS', "User logged in with role: {$user['role']}", $user['id'], $user['username']);

            // 6. Role-Based Redirection
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit;
        } else {
            $feedback = ['type' => 'danger', 'msg' => '❌ Invalid credentials. Username/email or password is incorrect.'];
            log_audit_action('LOGIN_FAILED', "Failed login attempt for identifier: $identifier");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Sign In</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            max-width: 480px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        body.dark-mode .auth-container {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
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
        }
        body.dark-mode .form-input {
            background: #0f172a;
            border-color: #475569;
            color: #f1f5f9;
        }
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
        }
        .btn-submit:hover { background: #0b5ed7; }
        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .demo-accounts {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin-top: 20px;
            font-size: 13px;
        }
        body.dark-mode .demo-accounts {
            background: #0f172a;
            border-color: #334155;
        }
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
                <a href="login.php" style="color: yellow; text-decoration: underline;">Sign In</a>
                <a href="registration.html">Register</a>
                <a href="students_crud.php">Student Records</a>
                <a href="events_crud.php">Events</a>
                <a href="ajax_students.html">Live Search</a>
            </nav>

            <!-- Theme Toggle -->
            <button class="theme-btn" id="themeToggle" type="button">🌙 Dark Mode</button>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="hero">
        <h1>🔐 Account Sign In</h1>
        <p>
            Secure session authentication with role-based redirection, timeout tracking, and remember-me security.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="padding: 0 15px;">

        <div class="auth-container">
            <h2 style="margin-top: 0; font-size: 22px; margin-bottom: 8px;">Sign In to StudentHub</h2>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                Enter your registered credentials to access your dashboard.
            </p>

            <?php if ($feedback): ?>
                <div class="alert-box alert-<?php echo htmlspecialchars($feedback['type']); ?>">
                    <?php echo $feedback['msg']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="identifier">Username or Email *</label>
                    <input type="text" id="identifier" name="identifier" class="form-input" placeholder="e.g. admin or aarav_shah" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" required>
                </div>

                <!-- Remember Me (Practical 10 Advanced) -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 13px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: #64748b;">
                        <input type="checkbox" name="remember" value="1"> Remember me for 30 days
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In Securely &rarr;
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #64748b;">
                New to StudentHub? <a href="registration.html" style="color: #0d6efd; font-weight: 600;">Create Account</a>
            </div>

            <!-- Demo Credentials Helper for Evaluation -->
            <div class="demo-accounts">
                <strong style="color: #0d6efd;">Evaluation Demo Credentials:</strong>
                <div style="margin-top: 6px; line-height: 1.6;">
                    <strong>Admin Role:</strong> <code>admin</code> / <code>Password@123</code> &rarr; <em>Redirects to Admin Dashboard</em><br>
                    <strong>Student Role:</strong> <code>aarav_shah</code> / <code>Password@123</code> &rarr; <em>Redirects to Student Dashboard</em>
                </div>
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

</body>
</html>
