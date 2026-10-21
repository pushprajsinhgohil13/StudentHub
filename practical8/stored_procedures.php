<?php
/**
 * ============================================================
 * StudentHub - Practical 8
 * File: stored_procedures.php
 * 
 * Demonstrates:
 * - Advanced Extension: Add stored procedural concept.
 * - Calling MySQL Stored Procedure `GetStudentRegistrations` via PHP PDO.
 * Styled 100% using StudentHub's native style.css and script.js.
 * ============================================================
 */

require_once 'db.php';
require_db_connection();

$selectedStudentId = (int)($_GET['student_id'] ?? 1);
$results = [];
$error = null;
$executed = false;

// Fetch all students for the dropdown
try {
    $studentsList = $pdo->query("SELECT id, name, course FROM students ORDER BY name ASC")->fetchAll();
} catch (Throwable $e) {
    $studentsList = [];
}

// Call Stored Procedure
if ($selectedStudentId > 0 && isset($_GET['run'])) {
    try {
        $stmt = $pdo->prepare("CALL GetStudentRegistrations(:sid)");
        $stmt->execute([':sid' => $selectedStudentId]);
        $results = $stmt->fetchAll();
        $stmt->closeCursor();
        $executed = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Student Event Registrations</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sp-card {
            background: white;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        body.dark-mode .sp-card {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .code-snippet {
            background: #212529;
            color: #f8f9fa;
            padding: 14px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 12px 0;
            line-height: 1.5;
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
                <a href="stored_procedures.php" style="color: yellow; text-decoration: underline;">Registrations Lookup</a>
                <a href="er_model.html">Database Model</a>
                <a href="test_connection.php">System Status</a>
                <a href="dashboard.html">Dashboard</a>
            </nav>

            <!-- Theme Toggle -->
            <button class="theme-btn" id="themeToggle" type="button">🌙 Dark Mode</button>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="hero">
        <h1>⚙️ Student Registrations Lookup</h1>
        <p>
            Lookup all registered events for any student via database stored procedure.
        </p>
    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="container" style="max-width: 1100px; margin: 30px auto; padding: 0 15px;">

        <!-- Concepts Card -->
        <div class="sp-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 style="margin: 0; font-size: 20px;">Database Stored Procedure Integration</h2>
            </div>
            <p style="font-size: 14px; line-height: 1.6; margin-bottom: 10px; color: #64748b;">
                A <strong>Stored Procedure</strong> is a pre-compiled collection of SQL statements stored directly inside the MySQL database catalog.
            </p>
            <ul style="font-size: 14px; line-height: 1.7; padding-left: 20px; color: #475569;">
                <li><strong>Pre-compiled Execution:</strong> Compiled once upon creation and cached in MySQL memory for fast execution.</li>
                <li><strong>Network Optimization:</strong> Only the procedure call (<code>CALL GetStudentRegistrations(...)</code>) traverses the network rather than large multi-line SQL strings.</li>
                <li><strong>Security &amp; Privilege Isolation:</strong> Database users can be granted execute permissions on specific procedures without granting raw table SELECT/INSERT rights.</li>
            </ul>

            <h4 style="margin: 15px 0 6px 0;">MySQL DDL Definition (from studenthub.sql):</h4>
            <div class="code-snippet">
<span style="color: #6ea8fe;">CREATE PROCEDURE</span> GetStudentRegistrations(
    <span style="color: #ffda6a;">IN</span> p_student_id <span style="color: #a3cfbb;">INT UNSIGNED</span>
)
<span style="color: #6ea8fe;">BEGIN</span>
    <span style="color: #6ea8fe;">SELECT</span> e.title, e.category, e.event_date, e.venue, er.status, er.registered_at
    <span style="color: #6ea8fe;">FROM</span> event_registrations er
    <span style="color: #6ea8fe;">JOIN</span> events e <span style="color: #6ea8fe;">ON</span> er.event_id = e.id
    <span style="color: #6ea8fe;">WHERE</span> er.student_id = p_student_id
    <span style="color: #6ea8fe;">ORDER BY</span> e.event_date <span style="color: #6ea8fe;">ASC</span>;
<span style="color: #6ea8fe;">END</span>;
            </div>
        </div>

        <!-- Interactive Runner Card -->
        <div class="sp-card">
            <h2 style="margin-top: 0; font-size: 20px; margin-bottom: 15px;">Test Procedure Execution via PHP PDO</h2>

            <form method="GET" action="stored_procedures.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 250px;">
                    <label for="student_id" style="font-weight: bold; font-size: 14px; display: block; margin-bottom: 6px;">Select Enrolled Student:</label>
                    <select id="student_id" name="student_id" style="width: 100%; padding: 9px; border-radius: 6px; border: 1px solid #ced4da; font-size: 14px;">
                        <?php foreach ($studentsList as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php if ($selectedStudentId == $st['id']) echo 'selected'; ?>>
                                #<?php echo $st['id']; ?> - <?php echo htmlspecialchars($st['name']); ?> (<?php echo htmlspecialchars($st['course']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" name="run" value="1" style="background: #0d6efd; color: white; padding: 10px 22px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
                        Execute Stored Procedure &rarr;
                    </button>
                </div>
            </form>

            <?php if ($error): ?>
                <div style="margin-top: 20px; padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($executed): ?>
                <div style="margin-top: 25px;">
                    <h3 style="font-size: 16px; margin-bottom: 10px;">Results Returned from Procedure:</h3>
                    <?php if (!empty($results)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <thead>
                                    <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                                        <th style="padding: 10px 12px;">Event Title</th>
                                        <th style="padding: 10px 12px;">Category</th>
                                        <th style="padding: 10px 12px;">Event Date</th>
                                        <th style="padding: 10px 12px;">Venue</th>
                                        <th style="padding: 10px 12px;">Status</th>
                                        <th style="padding: 10px 12px;">Registered On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $r): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 10px 12px;"><strong><?php echo htmlspecialchars($r['event_title']); ?></strong></td>
                                            <td style="padding: 10px 12px;"><span style="background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo strtoupper($r['category']); ?></span></td>
                                            <td style="padding: 10px 12px;"><?php echo date('M d, Y', strtotime($r['event_date'])); ?></td>
                                            <td style="padding: 10px 12px;"><?php echo htmlspecialchars($r['venue']); ?></td>
                                            <td style="padding: 10px 12px;">
                                                <?php if ($r['status'] === 'confirmed'): ?>
                                                    <span style="color: #198754; font-weight: bold;">Confirmed</span>
                                                <?php else: ?>
                                                    <span style="color: #fd7e14; font-weight: bold;">Waitlisted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 10px 12px; color: #64748b; font-size: 12px;"><?php echo htmlspecialchars($r['registered_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: #64748b; font-size: 14px;">No event registrations found for this student.</p>
                    <?php endif; ?>
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
