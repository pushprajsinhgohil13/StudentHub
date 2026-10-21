<?php

/*
    StudentHub - Practical 8
    view_events.php

    Events Listing & Event Registration via MySQL and PDO.
    Fully styled using StudentHub's original style.css and script.js.
*/

// ======================================================
// 1. CONNECT TO DATABASE
// ======================================================

require_once "db.php";
require_db_connection();


// ======================================================
// 2. HANDLE EVENT REGISTRATION (POST)
// ======================================================

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "register") {

    $studentId = (int) ($_POST["student_id"] ?? 0);
    $eventId   = (int) ($_POST["event_id"] ?? 0);

    if ($studentId > 0 && $eventId > 0) {

        // Check if student exists
        $chkStudent = $pdo->prepare("SELECT id, name FROM students WHERE id = :id LIMIT 1");
        $chkStudent->execute([":id" => $studentId]);
        $student = $chkStudent->fetch();

        if (!$student) {

            $message = "Student ID #$studentId does not exist. Please register first.";
            $messageType = "danger";

        } else {

            // Check if already registered
            $chkReg = $pdo->prepare(
                "SELECT id FROM event_registrations WHERE student_id = :sid AND event_id = :eid LIMIT 1"
            );
            $chkReg->execute([":sid" => $studentId, ":eid" => $eventId]);

            if ($chkReg->fetch()) {

                $message = "You are already registered for this event!";
                $messageType = "warning";

            } else {

                // Insert registration
                try {
                    $insertReg = $pdo->prepare(
                        "INSERT INTO event_registrations (student_id, event_id, status) VALUES (:sid, :eid, 'confirmed')"
                    );
                    $insertReg->execute([":sid" => $studentId, ":eid" => $eventId]);

                    $message = "Registration successful for " . htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") . "!";
                    $messageType = "success";

                } catch (PDOException $e) {
                    $message = "Database error: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        }
    } else {
        $message = "Please provide a valid Student ID and select an Event.";
        $messageType = "danger";
    }
}


// ======================================================
// 3. FETCH EVENTS FROM DATABASE (Prepared Statement)
// ======================================================

$eventsStmt = $pdo->prepare(
    "SELECT e.id, e.title, e.category, e.description, e.event_date, e.venue, e.max_seats,
            COUNT(er.id) AS registered_count
     FROM events e
     LEFT JOIN event_registrations er ON er.event_id = e.id
     GROUP BY e.id
     ORDER BY e.event_date ASC"
);
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();


// ======================================================
// 4. FETCH RECENT REGISTRATIONS (Prepared Statement)
// ======================================================

$regsStmt = $pdo->prepare(
    "SELECT er.id, s.name AS student_name, s.course, e.title AS event_title, er.status, er.registered_at
     FROM event_registrations er
     JOIN students s ON er.student_id = s.id
     JOIN events e ON er.event_id = e.id
     ORDER BY er.registered_at DESC
     LIMIT 10"
);
$regsStmt->execute();
$recentRegistrations = $regsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub | Events (MySQL)</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header>

        <div class="header-container">

            <!-- Logo -->
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
                <a href="view_students.php">Students</a>
                <a href="events.html">Events</a>
                <a href="view_events.php" style="color: yellow; text-decoration: underline;">Event Bookings</a>
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

        <h1>🎉 Campus Events</h1>

        <p>
            Browse upcoming campus events stored in the MySQL database and register online.
        </p>

    </section>


    <!-- ================= REGISTRATION ALERT ================= -->

    <?php if ($message !== ""): ?>
    <section style="padding: 0 20px;">
        <div class="card" style="max-width: 700px; margin: 15px auto; background: <?= $messageType === 'success' ? '#d1e7dd' : ($messageType === 'warning' ? '#fff3cd' : '#f8d7da') ?>; color: #333;">
            <p><strong><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></strong></p>
        </div>
    </section>
    <?php endif; ?>


    <!-- ================= EVENT REGISTRATION FORM ================= -->

    <section>

        <div class="card" style="max-width: 700px; margin: 0 auto 30px;">

            <h2 align="center">Register for an Event</h2>

            <form method="POST" action="view_events.php">

                <input type="hidden" name="action" value="register">

                <div class="form-row">

                    <div class="form-group">
                        <label for="student_id">Your Student ID</label>
                        <input
                            type="number"
                            id="student_id"
                            name="student_id"
                            placeholder="Enter your student ID (e.g. 1)"
                            min="1"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="event_id">Select Event</label>
                        <select id="event_id" name="event_id" required>
                            <option value="">-- Choose an Event --</option>
                            <?php foreach ($events as $ev): ?>
                            <option value="<?= htmlspecialchars((string)$ev["id"], ENT_QUOTES, "UTF-8") ?>">
                                <?= htmlspecialchars($ev["title"], ENT_QUOTES, "UTF-8") ?>
                                (<?= htmlspecialchars(date("d M Y", strtotime($ev["event_date"])), ENT_QUOTES, "UTF-8") ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="button-group">
                    <button type="submit">
                        Submit Registration
                    </button>
                    <button type="reset" style="background: #6c757d;">
                        Clear
                    </button>
                </div>

            </form>

        </div>

    </section>


    <!-- ================= EVENTS LIST (GRID) ================= -->

    <section>

        <h2 align="center">Upcoming Campus Events</h2>

        <div class="grid">

            <?php foreach ($events as $event): ?>

            <div class="card" id="event-card-<?= $event["id"] ?>">

                <h3 style="color: #0d6efd;">
                    <?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>
                </h3>

                <p style="margin: 8px 0; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; color: #666;">
                    📂 <?= htmlspecialchars($event["category"], ENT_QUOTES, "UTF-8") ?>
                </p>

                <p style="margin: 10px 0;">
                    <?= htmlspecialchars($event["description"], ENT_QUOTES, "UTF-8") ?>
                </p>

                <p style="margin: 6px 0;">
                    📅 <strong>Date:</strong> <?= htmlspecialchars(date("d M Y", strtotime($event["event_date"])), ENT_QUOTES, "UTF-8") ?>
                </p>

                <p style="margin: 6px 0;">
                    📍 <strong>Venue:</strong> <?= htmlspecialchars($event["venue"], ENT_QUOTES, "UTF-8") ?>
                </p>

                <p style="margin: 6px 0;">
                    👥 <strong>Registered:</strong> <?= (int)$event["registered_count"] ?> / <?= (int)$event["max_seats"] ?> Seats
                </p>

                <br>

                <button
                    type="button"
                    onclick="document.getElementById('event_id').value = '<?= $event['id'] ?>'; document.getElementById('student_id').focus(); window.scrollTo({top: 250, behavior: 'smooth'});"
                >
                    Register
                </button>

            </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- ================= RECENT REGISTRATIONS TABLE ================= -->

    <section style="padding: 20px;">

        <h2 align="center">Recent Registrations</h2>

        <?php if (empty($recentRegistrations)): ?>
            <p align="center" style="margin-top: 15px;">No registrations recorded yet.</p>
        <?php else: ?>

            <div style="overflow-x: auto;">

                <table>

                    <thead>
                        <tr>
                            <th>Reg #</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($recentRegistrations as $reg): ?>
                        <tr>
                            <td>#<?= htmlspecialchars((string)$reg["id"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($reg["student_name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($reg["course"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><strong><?= htmlspecialchars($reg["event_title"], ENT_QUOTES, "UTF-8") ?></strong></td>
                            <td><?= htmlspecialchars(ucfirst($reg["status"]), ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($reg["registered_at"])), ENT_QUOTES, "UTF-8") ?></td>
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
