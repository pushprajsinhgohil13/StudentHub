<?php
/**
 * StudentHub - User Sign In
 * Demonstrates password verification with password_verify() against BCRYPT hash.
 */

require_once 'db.php';
$conn = get_mysqli();

$feedback = null;
$loggedInUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $feedback = ['type' => 'danger', 'msg' => 'Please enter your username/email and password.'];
    } elseif (!$conn) {
        $feedback = ['type' => 'danger', 'msg' => 'Database connection failed.'];
    } else {
        // Query user with MySQLi prepared statement
        $stmt = $conn->prepare("SELECT id, name, username, email, course, semester, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $loggedInUser = $user;
            $feedback = [
                'type' => 'success',
                'msg'  => "✅ Authentication Successful! Welcome back, <strong>" . htmlspecialchars($user['name']) . "</strong>."
            ];
        } else {
            $feedback = [
                'type' => 'danger',
                'msg'  => '❌ Invalid credentials. The password does not match or the account was not found.'
            ];
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
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .user-profile-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 15px;
        }
        body.dark-mode .user-profile-box {
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
                <a href="register.php">Create Account</a>
                <a href="login.php" style="color: yellow; text-decoration: underline;">Sign In</a>
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
        <h1>🔑 Account Sign In</h1>
        <p>
            Secure authentication verifying user credentials against stored BCRYPT password hashes.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="padding: 0 15px;">

        <div class="auth-container">
            <h2 style="margin-top: 0; font-size: 22px; margin-bottom: 8px;">Sign In to StudentHub</h2>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                Enter your registered username or email address and password.
            </p>

            <?php if ($feedback): ?>
                <div class="alert-box alert-<?php echo htmlspecialchars($feedback['type']); ?>">
                    <?php echo $feedback['msg']; ?>
                </div>
            <?php endif; ?>

            <?php if ($loggedInUser): ?>
                <div class="user-profile-box">
                    <h3 style="margin-top: 0; font-size: 16px; color: #198754;">User Session Authenticated</h3>
                    <p style="font-size: 14px; margin: 4px 0;"><strong>Name:</strong> <?php echo htmlspecialchars($loggedInUser['name']); ?></p>
                    <p style="font-size: 14px; margin: 4px 0;"><strong>Username:</strong> <code><?php echo htmlspecialchars($loggedInUser['username']); ?></code></p>
                    <p style="font-size: 14px; margin: 4px 0;"><strong>Email:</strong> <?php echo htmlspecialchars($loggedInUser['email']); ?></p>
                    <p style="font-size: 14px; margin: 4px 0;"><strong>Course:</strong> <?php echo htmlspecialchars($loggedInUser['course']); ?> (Semester <?php echo $loggedInUser['semester']; ?>)</p>
                    <p style="font-size: 12px; color: #64748b; margin-top: 10px;">
                        <em>Password verified via <code>password_verify()</code> matching BCRYPT digest.</em>
                    </p>
                    <a href="dashboard.html" class="btn-submit" style="display: block; text-align: center; text-decoration: none; margin-top: 14px;">
                        Proceed to Dashboard &rarr;
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="identifier">Username or Email *</label>
                        <input type="text" id="identifier" name="identifier" class="form-input" placeholder="Username or email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn-submit" style="margin-top: 8px;">
                        Sign In Securely
                    </button>
                </form>

                <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #64748b;">
                    Don't have an account yet? <a href="register.php" style="color: #0d6efd; font-weight: 600;">Create One Here</a>
                </div>
            <?php endif; ?>
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
