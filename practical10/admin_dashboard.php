<?php
/**
 * StudentHub - Practical 14: Admin Dashboard with RBAC, Dynamic Menu, & Audit Log
 * Protected admin-only dashboard. Displays real-time database aggregations, 
 * user account status toggles, audit trails, and SVG registration analytics.
 */

require_once 'db.php';
require_role('admin'); // Enforce strict RBAC: only 'admin' role allowed

$conn = get_mysqli();
$feedback = null;
$csrfToken = get_csrf_token();

// Handle User Status Toggle (Intermediate Extension)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $feedback = ['type' => 'danger', 'msg' => 'Invalid security token.'];
    } else {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'inactive']) ? $_POST['new_status'] : 'active';

        if ($targetUserId === (int)$_SESSION['user_id']) {
            $feedback = ['type' => 'warning', 'msg' => '⚠️ You cannot toggle your own active admin account.'];
        } elseif ($targetUserId > 0) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $targetUserId);
            if ($stmt->execute()) {
                $feedback = ['type' => 'success', 'msg' => "✅ User account #$targetUserId status changed to '$newStatus'."];
                log_audit_action('USER_STATUS_TOGGLED', "Admin toggled user #$targetUserId status to $newStatus", $_SESSION['user_id'], $_SESSION['username']);
            } else {
                $feedback = ['type' => 'danger', 'msg' => 'Failed to change user status.'];
            }
            $stmt->close();
        }
    }
}

// 1. Fetch Aggregation Metrics (SQL Aggregations)
$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;

if ($conn) {
    $res = $conn->query("SELECT COUNT(*) as c FROM users");
    if ($res) $totalUsers = $res->fetch_assoc()['c'];

    $res = $conn->query("SELECT COUNT(*) as c FROM students WHERE status != 'deleted'");
    if ($res) $totalStudents = $res->fetch_assoc()['c'];

    $res = $conn->query("SELECT COUNT(*) as c FROM events");
    if ($res) $totalEvents = $res->fetch_assoc()['c'];

    $res = $conn->query("SELECT COUNT(*) as c FROM event_registrations");
    if ($res) $totalRegistrations = $res->fetch_assoc()['c'];
}

// 2. Fetch Users List for Status Toggle
$userAccounts = [];
if ($conn) {
    $res = $conn->query("SELECT id, name, username, email, role, status, created_at, last_login FROM users ORDER BY id ASC");
    if ($res) $userAccounts = $res->fetch_all(MYSQLI_ASSOC);
}

// 3. Fetch Recent Audit Logs
$recentAuditLogs = [];
if ($conn) {
    $res = $conn->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 10");
    if ($res) $recentAuditLogs = $res->fetch_all(MYSQLI_ASSOC);
}

// 4. Fetch Registration Analytics by Category (Advanced Extension)
$categoryStats = [];
if ($conn) {
    $res = $conn->query("SELECT e.category, COUNT(er.id) as total_regs 
                         FROM events e 
                         LEFT JOIN event_registrations er ON e.id = er.event_id 
                         GROUP BY e.category 
                         ORDER BY total_regs DESC");
    if ($res) $categoryStats = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Admin Dashboard - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1280px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .admin-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border-left: 6px solid #fd7e14;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }
        .admin-banner h1 { margin: 0 0 0.4rem 0; font-size: 1.8rem; }
        .admin-banner p { margin: 0; opacity: 0.85; }

        .banner-pill {
            background: #fd7e14;
            color: #fff;
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .alert-box {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* Metric KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .kpi-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-2px); }
        .kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }
        .kpi-blue { background: #0d6efd; }
        .kpi-green { background: #198754; }
        .kpi-purple { background: #6f42c1; }
        .kpi-orange { background: #fd7e14; }

        .kpi-details h3 { margin: 0; font-size: 1.8rem; color: var(--text-color, #1e293b); }
        .kpi-details p { margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.9rem; }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 992px) {
            .dashboard-layout { grid-template-columns: 1fr; }
        }

        .card-panel {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .panel-header h2 { margin: 0; font-size: 1.2rem; color: var(--text-color, #1e293b); }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .data-table th, .data-table td {
            padding: 0.75rem 0.9rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .data-table th {
            background: var(--table-th-bg, #f8fafc);
            font-weight: 600;
            color: #475569;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }

        .toggle-btn {
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-size: 0.78rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }
        .toggle-btn:hover { background: #f1f5f9; }

        /* Analytics Bar Chart (SVG) */
        .chart-box {
            padding: 1rem 0;
        }
        .chart-bar-item {
            margin-bottom: 1rem;
        }
        .chart-bar-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            margin-bottom: 0.35rem;
        }
        .chart-bar-bg {
            background: #e2e8f0;
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
        }
        .chart-bar-fill {
            background: linear-gradient(90deg, #0d6efd, #6f42c1);
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }
    </style>
</head>
<body>

    <!-- Header Navigation with Dynamic Menu -->
    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>StudentHub</span>
        </div>
        <nav class="nav-links">
            <a href="index.html">Home</a>
            <a href="admin_dashboard.php" class="active"><i class="fa-solid fa-shield-halved"></i> Admin Control</a>
            <a href="students_crud.php">Student Records</a>
            <a href="events_crud.php">Event Manager</a>
            <a href="ajax_students.html">AJAX Console</a>
            <a href="logout.php" style="color: #dc3545;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            <button id="themeToggle" class="theme-btn" aria-label="Toggle Theme"><i class="fa-solid fa-moon"></i></button>
        </nav>
        <div class="hamburger"><i class="fa-solid fa-bars"></i></div>
    </header>

    <div class="admin-container">
        <!-- Admin Welcome Banner -->
        <div class="admin-banner">
            <div>
                <h1>Executive Admin Dashboard</h1>
                <p>Role-Based Access Control (RBAC) &bull; Telemetry &bull; System Aggregations &bull; User Governance</p>
            </div>
            <div>
                <span class="banner-pill"><i class="fa-solid fa-lock"></i> Strict RBAC: Admin</span>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($feedback): ?>
            <div class="alert-box alert-<?php echo $feedback['type']; ?>">
                <i class="fa-solid <?php echo $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($feedback['msg']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Key Aggregation Metrics (KPIs) -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon kpi-blue"><i class="fa-solid fa-users"></i></div>
                <div class="kpi-details">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Registered User Accounts</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-green"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="kpi-details">
                    <h3><?php echo $totalStudents; ?></h3>
                    <p>Enrolled Students</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-purple"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="kpi-details">
                    <h3><?php echo $totalEvents; ?></h3>
                    <p>Organized Events</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-orange"><i class="fa-solid fa-ticket"></i></div>
                <div class="kpi-details">
                    <h3><?php echo $totalRegistrations; ?></h3>
                    <p>Event Registrations</p>
                </div>
            </div>
        </div>

        <!-- Layout Grid -->
        <div class="dashboard-layout">
            <!-- Left Column: User Governance & Audit Logs -->
            <div>
                <!-- User Status Management -->
                <div class="card-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-users-gear" style="color:#0d6efd;"></i> User Governance &amp; Account Status</h2>
                        <span style="font-size:0.85rem; color:#64748b;">Active / Inactive Toggle</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userAccounts as $u): ?>
                                    <tr>
                                        <td>#<?php echo $u['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                        <td>
                                            <span style="text-transform:uppercase; font-size:0.75rem; font-weight:700; color: <?php echo $u['role'] === 'admin' ? '#fd7e14' : '#0d6efd'; ?>;">
                                                <?php echo $u['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge badge-<?php echo $u['status']; ?>">
                                                <?php echo ucfirst($u['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                <span style="color:#94a3b8; font-size:0.8rem;">Current Admin</span>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_user_status">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $u['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="toggle-btn" title="Toggle status">
                                                        <?php if ($u['status'] === 'active'): ?>
                                                            <i class="fa-solid fa-toggle-on" style="color:#198754;"></i> Deactivate
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-toggle-off" style="color:#dc3545;"></i> Activate
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Telemetry & Audit Logs -->
                <div class="card-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-clock-rotate-left" style="color:#198754;"></i> Security Audit Logs</h2>
                        <span style="font-size:0.85rem; color:#64748b;">Live Telemetry</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAuditLogs)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding:1.5rem; color:#64748b;">No audit records available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentAuditLogs as $log): ?>
                                        <tr>
                                            <td style="color:#64748b; font-size:0.8rem; white-space:nowrap;">
                                                <?php echo date('M d H:i', strtotime($log['timestamp'])); ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($log['username'] ?? 'Anonymous'); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                                            <td style="font-size:0.8rem; color:#64748b;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Analytics & Quick Links -->
            <div>
                <!-- Registration Analytics Breakdown (Advanced Extension) -->
                <div class="card-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-chart-simple" style="color:#6f42c1;"></i> Registration Breakdown</h2>
                    </div>
                    <p style="font-size:0.88rem; color:#64748b; margin-bottom:1.25rem;">
                        Event signups aggregated by category from MySQL database.
                    </p>
                    <div class="chart-box">
                        <?php 
                        $maxRegs = 1;
                        foreach ($categoryStats as $cs) {
                            if ($cs['total_regs'] > $maxRegs) $maxRegs = $cs['total_regs'];
                        }
                        ?>
                        <?php foreach ($categoryStats as $cs): 
                            $percent = round(($cs['total_regs'] / $maxRegs) * 100);
                        ?>
                            <div class="chart-bar-item">
                                <div class="chart-bar-header">
                                    <span style="font-weight:600; text-transform:capitalize;"><?php echo htmlspecialchars($cs['category']); ?></span>
                                    <span><strong><?php echo $cs['total_regs']; ?></strong> signups</span>
                                </div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar-fill" style="width: <?php echo max(8, $percent); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Admin Dynamic Management Tools -->
                <div class="card-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-screwdriver-wrench" style="color:#fd7e14;"></i> Administrative Tools</h2>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:0.75rem;">
                        <a href="students_crud.php" class="btn" style="text-align:left; display:flex; align-items:center; gap:0.5rem; background:#f8fafc; color:#1e293b; border:1px solid #e2e8f0;">
                            <i class="fa-solid fa-user-graduate" style="color:#0d6efd;"></i>
                            <span>Manage Student Directory &amp; CSV</span>
                        </a>
                        <a href="events_crud.php" class="btn" style="text-align:left; display:flex; align-items:center; gap:0.5rem; background:#f8fafc; color:#1e293b; border:1px solid #e2e8f0;">
                            <i class="fa-solid fa-calendar-days" style="color:#fd7e14;"></i>
                            <span>Manage Campus Events &amp; Posters</span>
                        </a>
                        <a href="ajax_students.html" class="btn" style="text-align:left; display:flex; align-items:center; gap:0.5rem; background:#f8fafc; color:#1e293b; border:1px solid #e2e8f0;">
                            <i class="fa-solid fa-bolt" style="color:#6f42c1;"></i>
                            <span>Live Asynchronous Fetch Console</span>
                        </a>
                        <a href="system_status.php" class="btn" style="text-align:left; display:flex; align-items:center; gap:0.5rem; background:#f8fafc; color:#1e293b; border:1px solid #e2e8f0;">
                            <i class="fa-solid fa-heart-pulse" style="color:#198754;"></i>
                            <span>System Diagnostics &amp; Integration Test</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2026 StudentHub. Administrative Suite with Role-Based Access Control &amp; SQL Aggregations.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
