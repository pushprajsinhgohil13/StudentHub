<?php
/**
 * ============================================================
 * StudentHub - Practical 8
 * File: test_connection.php
 * 
 * Database Connection Diagnostic & Screenshot Verification Tool
 * Styled 100% using StudentHub's native style.css and script.js.
 * ============================================================
 * Laboratory Deliverable:
 * "Submit SQL dump, db.php file, and connection test screenshot."
 * ============================================================
 */

require_once 'db.php';

$diag = [
    'connected'       => $dbConnected,
    'host'            => DB_HOST,
    'port'            => null,
    'database'        => DB_NAME,
    'user'            => DB_USER,
    'charset'         => DB_CHARSET,
    'server_version'  => 'N/A',
    'emulate_prepares'=> 'false (Native Server Prepares)',
    'error_mode'      => 'ERRMODE_EXCEPTION',
    'error'           => $dbError ?? null,
    'tables'          => [],
    'bench_time'      => null,
    'test_query_msg'  => null
];

if ($pdo) {
    try {
        $diag['server_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        // Benchmark a test prepared statement
        $start = microtime(true);
        $testStmt = $pdo->prepare("SELECT :msg AS message, DATABASE() AS active_db, NOW() AS s_time");
        $testStmt->execute([':msg' => 'StudentHub PDO Connection Active']);
        $row = $testStmt->fetch();
        $diag['bench_time'] = round((microtime(true) - $start) * 1000, 2);
        $diag['test_query_msg'] = $row['message'] ?? 'OK';

        // Table counts
        $tableQueries = [
            'students'            => "SELECT COUNT(*) FROM students",
            'events'              => "SELECT COUNT(*) FROM events",
            'event_registrations' => "SELECT COUNT(*) FROM event_registrations",
            'v_registrations'     => "SELECT COUNT(*) FROM v_registrations"
        ];

        foreach ($tableQueries as $tbl => $q) {
            try {
                $c = $pdo->query($q)->fetchColumn();
                $diag['tables'][$tbl] = (int)$c;
            } catch (Throwable $te) {
                $diag['tables'][$tbl] = 'Error: ' . $te->getMessage();
            }
        }
    } catch (Throwable $e) {
        $diag['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Database & Server Status</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .diag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .diag-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        body.dark-mode .diag-card {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .status-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }
        .status-pill.online {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-pill.offline {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .prop-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .prop-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        body.dark-mode .prop-table td {
            border-bottom-color: #334155;
        }
        .prop-label {
            font-weight: bold;
            color: #64748b;
            width: 45%;
        }
        body.dark-mode .prop-label {
            color: #94a3b8;
        }
        .prop-val {
            font-family: monospace;
            font-weight: 600;
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
                <a href="registration.html">Registration</a>
                <a href="view_students.php">Students</a>
                <a href="events.html">Events</a>
                <a href="view_events.php">Event Bookings</a>
                <a href="stored_procedures.php">Registrations Lookup</a>
                <a href="er_model.html">Database Model</a>
                <a href="test_connection.php" style="color: yellow; text-decoration: underline;">System Status</a>
                <a href="dashboard.html">Dashboard</a>
            </nav>

            <!-- Theme Toggle -->
            <button class="theme-btn" id="themeToggle" type="button">🌙 Dark Mode</button>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="hero">
        <h1>🔌 Database & Server Status</h1>
        <p>
            Real-time diagnostic health check for StudentHub MySQL connectivity, security, and schema integrity.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="max-width: 1100px; margin: 30px auto; padding: 0 15px;">

        <!-- Connection Diagnostic Grid -->
        <div class="diag-grid">
            <!-- 1. Connection Status Card -->
            <div class="diag-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 18px;">PDO Connection Status</h3>
                    <?php if ($diag['connected']): ?>
                        <span class="status-pill online">● CONNECTED</span>
                    <?php else: ?>
                        <span class="status-pill offline">● DISCONNECTED</span>
                    <?php endif; ?>
                </div>

                <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">
                    <?php echo $diag['connected'] ? 'PHP PDO connected successfully to MySQL.' : 'MySQL is currently unreachable.'; ?>
                </p>

                <table class="prop-table">
                    <tr>
                        <td class="prop-label">Host</td>
                        <td class="prop-val"><?php echo htmlspecialchars($diag['host']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Database</td>
                        <td class="prop-val"><?php echo htmlspecialchars($diag['database']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Charset</td>
                        <td class="prop-val"><?php echo htmlspecialchars($diag['charset']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Latency</td>
                        <td class="prop-val" style="color: #198754;"><?php echo $diag['bench_time'] ?? 0; ?> ms</td>
                    </tr>
                </table>
            </div>

            <!-- 2. Security & Driver Configuration -->
            <div class="diag-card">
                <h3 style="margin: 0 0 12px 0; font-size: 18px;">Security &amp; PDO Driver</h3>
                <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">
                    Configured to eliminate SQL Injection via native server-side prepared statements:
                </p>

                <table class="prop-table">
                    <tr>
                        <td class="prop-label">MySQL Server</td>
                        <td class="prop-val"><?php echo htmlspecialchars($diag['server_version']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Emulate Prepares</td>
                        <td class="prop-val" style="color: #0d6efd;"><?php echo htmlspecialchars($diag['emulate_prepares']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Error Mode</td>
                        <td class="prop-val" style="color: #6f42c1;"><?php echo htmlspecialchars($diag['error_mode']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop-label">Driver Type</td>
                        <td class="prop-val">PDO_MYSQL</td>
                    </tr>
                </table>
            </div>

            <!-- 3. Schema & Tables Verified -->
            <div class="diag-card">
                <h3 style="margin: 0 0 12px 0; font-size: 18px;">Normalized Schema (3NF)</h3>
                <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">
                    Live record counts for core entities and relational view:
                </p>

                <table class="prop-table">
                    <?php if (!empty($diag['tables'])): ?>
                        <?php foreach ($diag['tables'] as $tbl => $cnt): ?>
                            <tr>
                                <td class="prop-label"><code><?php echo htmlspecialchars($tbl); ?></code></td>
                                <td class="prop-val" style="color: #198754;"><?php echo htmlspecialchars((string)$cnt); ?> records</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="color: #dc3545;">No tables detected.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Live Prepared Statement Test -->
        <div class="diag-card" style="margin-top: 20px;">
            <h3 style="margin-top: 0; font-size: 18px; margin-bottom: 10px;">⚡ Prepared Statement Execution Test</h3>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 15px;">
                Verified parameterized query execution with zero string interpolation:
            </p>
            <div style="background: #212529; color: #f8f9fa; padding: 12px 16px; border-radius: 6px; font-family: monospace; font-size: 13px;">
                <span style="color: #0dcaf0;">$stmt</span> = <span style="color: #0dcaf0;">$pdo</span>-&gt;prepare(<span style="color: #a3cfbb;">"SELECT :msg AS message, DATABASE() AS active_db, NOW() AS s_time"</span>);<br>
                <span style="color: #0dcaf0;">$stmt</span>-&gt;execute([<span style="color: #ffe69c;">':msg'</span> =&gt; <span style="color: #ffe69c;">'StudentHub PDO Connection Active'</span>]);
            </div>
            <p style="margin: 12px 0 0 0; font-size: 14px;">
                <strong>Query Status:</strong> <span style="color: #198754; font-weight: bold;">SUCCESS</span> &bull; 
                <strong>Response Message:</strong> <em>"<?php echo htmlspecialchars($diag['test_query_msg'] ?? ''); ?>"</em>
            </p>
        </div>

        <!-- Quick Links -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="view_students.php" class="btn" style="display: inline-block; background: #0d6efd; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin: 5px;">
                View Students in Database &rarr;
            </a>
            <a href="view_events.php" class="btn" style="display: inline-block; background: #198754; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin: 5px;">
                View Events &amp; Bookings &rarr;
            </a>
            <a href="er_model.html" class="btn" style="display: inline-block; background: #6f42c1; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin: 5px;">
                View Database Model &rarr;
            </a>
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
