<?php

/*
    StudentHub - Practical 8
    view_students.php

    Displays all students stored in the MySQL database.
    Uses PDO Prepared Statements for safe querying.
    Styled 100% using StudentHub's original style.css and script.js.
*/

// ======================================================
// 1. CONNECT TO DATABASE
// ======================================================

require_once "db.php";
require_db_connection();


// ======================================================
// 2. SEARCH & FETCH STUDENTS (Prepared Statement)
// ======================================================

$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $stmt = $pdo->prepare(
        "SELECT id, name, email, phone, course, semester, message, created_at
         FROM students
         WHERE name LIKE :q
            OR email LIKE :q
            OR course LIKE :q
         ORDER BY created_at DESC"
    );

    $stmt->execute([":q" => "%" . $search . "%"]);

} else {

    $stmt = $pdo->prepare(
        "SELECT id, name, email, phone, course, semester, message, created_at
         FROM students
         ORDER BY created_at DESC"
    );

    $stmt->execute();
}

$students = $stmt->fetchAll();
$totalStudents = count($students);


// ======================================================
// 3. COURSE SUMMARY STATS (Prepared Statement)
// ======================================================

$statsStmt = $pdo->prepare(
    "SELECT course, COUNT(*) AS count
     FROM students
     GROUP BY course
     ORDER BY count DESC"
);

$statsStmt->execute();
$courseStats = $statsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub | Registered Students</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header>

        <div class="header-container">

            <div class="logo">
                <h2>🎓 StudentHub</h2>
            </div>

            <!-- Hamburger Button -->
            <button
                class="hamburger"
                id="hamburgerBtn"
                type="button">
                ☰
            </button>

            <!-- Navigation -->
            <nav id="mainNav">
                <a href="index.html">Home</a>
                <a href="registration.html">Registration</a>
                <a href="view_students.php" style="color: yellow; text-decoration: underline;">Students</a>
                <a href="events.html">Events</a>
                <a href="view_events.php">Event Bookings</a>
                <a href="stored_procedures.php">Registrations Lookup</a>
                <a href="er_model.html">Database Model</a>
                <a href="test_connection.php">System Status</a>
                <a href="dashboard.html">Dashboard</a>
            </nav>

            <!-- Theme Toggle -->
            <button
                class="theme-btn"
                id="themeToggle"
                type="button">
                🌙 Dark Mode
            </button>

        </div>

    </header>


    <!-- ================= NOTIFICATION BANNER ================= -->

    <div
        id="notification"
        class="notification">

        <span id="notificationMessage"></span>

        <button
            id="closeNotification"
            type="button">
            ×
        </button>

    </div>


    <!-- ================= HERO ================= -->

    <section class="hero">

        <h1>👥 Registered Students</h1>

        <p>
            Database-backed records fetched securely using PHP PDO &amp; Prepared Statements.
        </p>

    </section>


    <!-- ================= SUMMARY STATS CARDS ================= -->

    <section>

        <div class="grid">

            <div class="card">
                <h3>Total Students</h3>
                <h1 style="color: #0d6efd;"><?= $totalStudents ?></h1>
                <p>In Database</p>
            </div>

            <?php foreach ($courseStats as $stat): ?>
            <div class="card">
                <h3><?= htmlspecialchars($stat["course"], ENT_QUOTES, "UTF-8") ?></h3>
                <h1 style="color: #0d6efd;"><?= htmlspecialchars((string)$stat["count"], ENT_QUOTES, "UTF-8") ?></h1>
                <p>Enrolled</p>
            </div>
            <?php endforeach; ?>

        </div>

    </section>


    <!-- ================= SEARCH & ACTIONS ================= -->

    <section>

        <div class="card" style="max-width: 800px; margin: 0 auto 30px;">

            <h3>Search Student Records</h3>

            <form method="GET" action="view_students.php" style="padding: 10px 0;">

                <div class="form-row">

                    <div class="form-group">
                        <label for="search">Student Name, Email, or Course</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Type to search..."
                            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                        >
                    </div>

                </div>

                <div class="button-group">

                    <button type="submit">
                        🔍 Search
                    </button>

                    <?php if ($search !== ""): ?>
                    <a href="view_students.php" style="text-decoration:none;">
                        <button type="button" style="background:#6c757d;">
                            ✕ Clear Filter
                        </button>
                    </a>
                    <?php endif; ?>

                    <a href="registration.html" style="text-decoration:none;">
                        <button type="button" style="background:#198754;">
                            + Register New Student
                        </button>
                    </a>

                </div>

            </form>

        </div>

    </section>


    <!-- ================= STUDENTS TABLE ================= -->

    <section style="padding: 0 20px;">

        <h2 align="center">Student Records</h2>

        <?php if ($search !== ""): ?>
            <p align="center" style="margin: 10px 0; color: #666;">
                Showing results for "<strong><?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?></strong>" (<?= $totalStudents ?> found)
            </p>
        <?php endif; ?>

        <?php if (empty($students)): ?>

            <div class="card" style="max-width: 600px; margin: 30px auto; text-align: center;">
                <h3>No Students Found</h3>
                <p>No student records match your query.</p>
                <br>
                <a href="registration.html">
                    <button type="button">Register a Student</button>
                </a>
            </div>

        <?php else: ?>

            <div style="overflow-x: auto;">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($students as $student): ?>

                        <tr>
                            <td><strong>#<?= htmlspecialchars((string)$student["id"], ENT_QUOTES, "UTF-8") ?></strong></td>
                            <td><?= htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["phone"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><strong><?= htmlspecialchars($student["course"], ENT_QUOTES, "UTF-8") ?></strong></td>
                            <td>Sem <?= htmlspecialchars((string)$student["semester"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars(date("d M Y", strtotime($student["created_at"])), ENT_QUOTES, "UTF-8") ?></td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


    <!-- ================= FOOTER ================= -->

    <footer>
        <p>© 2026 StudentHub Portal</p>
    </footer>


    <!-- ================= MAIN JAVASCRIPT ================= -->

    <script src="script.js"></script>

</body>

</html>
