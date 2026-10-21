<?php
/**
 * StudentHub - Registration Audit Logs
 * Advanced Extension Requirement:
 * "Add CSRF token and audit log entry for registration."
 */

require_once 'db.php';
$conn = get_mysqli();

$logs = [];
if ($conn) {
    $result = $conn->query("SELECT id, ip_address, user_agent, attempted_username, attempted_email, status, details, created_at FROM registration_audit_logs ORDER BY id DESC LIMIT 50");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Security Audit Logs</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .audit-card {
            background: white;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        body.dark-mode .audit-card {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }
        .data-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        body.dark-mode .data-table th {
            background: #0f172a;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        body.dark-mode .data-table td {
            border-bottom-color: #334155;
        }
        .status-badge {
            font-size: 11px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .status-SUCCESS            { background: #d1fae5; color: #065f46; }
        .status-DUPLICATE_USERNAME { background: #fef3c7; color: #92400e; }
        .status-DUPLICATE_EMAIL    { background: #fed7aa; color: #9a3412; }
        .status-VALIDATION_ERROR   { background: #fee2e2; color: #991b1b; }
        .status-CSRF_FAILURE       { background: #ede9fe; color: #5b21b6; }
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
                <a href="login.php">Sign In</a>
                <a href="users.php">Registered Users</a>
                <a href="audit_logs.php" style="color: yellow; text-decoration: underline;">Security Audit Logs</a>
                <a href="test_report.php">Test Suite</a>
                <a href="dashboard.html">Dashboard</a>
            </nav>

            <!-- Theme Toggle -->
            <button class="theme-btn" id="themeToggle" type="button">🌙 Dark Mode</button>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="hero">
        <h1>🛡️ Registration Security Audit Logs</h1>
        <p>
            Real-time security telemetry recording all registration attempts, duplicate blocks, and validation failures.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="max-width: 1150px; margin: 30px auto; padding: 0 15px;">

        <div class="audit-card">
            <h2 style="margin-top: 0; font-size: 20px; margin-bottom: 8px;">Registration Telemetry &amp; Access Log</h2>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 16px;">
                Every account creation attempt is audited with client IP address, user agent, target username/email, and outcome code.
            </p>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status Code</th>
                            <th>Target Username</th>
                            <th>Target Email</th>
                            <th>IP Address</th>
                            <th>Details &amp; Reason</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><strong>#<?php echo $l['id']; ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($l['status']); ?>">
                                            <?php echo htmlspecialchars($l['status']); ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($l['attempted_username'] ?? '—'); ?></code></td>
                                    <td><?php echo htmlspecialchars($l['attempted_email'] ?? '—'); ?></td>
                                    <td><code><?php echo htmlspecialchars($l['ip_address']); ?></code></td>
                                    <td><?php echo htmlspecialchars($l['details']); ?></td>
                                    <td style="color: #64748b; font-size: 12px;"><?php echo htmlspecialchars($l['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b; padding: 24px;">No audit records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
