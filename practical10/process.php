<?php

/*
    StudentHub - Practical 8
    process.php

    PHP Form Processing with PDO & Prepared Statements.
    Retains the original StudentHub clean UI/UX from Practical 7.
*/


// ======================================================
// 1. CHECK REQUEST METHOD
// ======================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;background:#f4f6f8;padding:40px;}.box{max-width:650px;margin:auto;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}a{display:inline-block;margin-top:20px;padding:10px 18px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;}</style></head><body>";
    echo "<div class='box'><h2>Error</h2><p>Invalid request method.</p><a href='registration.html'>Go Back</a></div></body></html>";

    exit;
}


// ======================================================
// 2. GET FORM DATA
// ======================================================

$name     = $_POST["name"]     ?? "";
$email    = $_POST["email"]    ?? "";
$phone    = $_POST["phone"]    ?? "";
$course   = $_POST["course"]   ?? "";
$semester = $_POST["semester"] ?? "";
$message  = $_POST["message"]  ?? "";


// ======================================================
// 3. SANITIZE INPUT
// ======================================================

$name     = trim($name);
$email    = trim($email);
$phone    = trim($phone);
$course   = trim($course);
$semester = trim($semester);
$message  = trim($message);

$name     = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$email    = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$phone    = htmlspecialchars($phone, ENT_QUOTES, "UTF-8");
$course   = htmlspecialchars($course, ENT_QUOTES, "UTF-8");
$semester = htmlspecialchars($semester, ENT_QUOTES, "UTF-8");
$message  = htmlspecialchars($message, ENT_QUOTES, "UTF-8");


// ======================================================
// 4. SERVER-SIDE VALIDATION
// ======================================================

$errors = [];

// Name validation
if ($name === "") {
    $errors[] = "Name is required.";
} elseif (strlen($name) < 2) {
    $errors[] = "Name must contain at least 2 characters.";
}

// Email validation
if ($email === "") {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

// Phone validation
if ($phone === "") {
    $errors[] = "Phone number is required.";
} elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
    $errors[] = "Phone number must contain exactly 10 digits.";
}

// Course validation
if ($course === "") {
    $errors[] = "Please select a course.";
}

// Semester validation
if ($semester === "") {
    $errors[] = "Please select a semester.";
}

// Message validation
if (strlen($message) > 500) {
    $errors[] = "Message must not exceed 500 characters.";
}


// ======================================================
// 5. DISPLAY VALIDATION ERRORS
// ======================================================

if (!empty($errors)) {

    echo "<!DOCTYPE html>";
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Registration Error</title>";
    echo "<style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 40px;
        }
        .box {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #d32f2f;
        }
        li {
            margin: 10px 0;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='box'>";
    echo "<h2>❌ Registration Failed</h2>";
    echo "<p>Please correct the following errors:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error, ENT_QUOTES, "UTF-8") . "</li>";
    }
    echo "</ul>";
    echo '<a href="registration.html">Go Back to Registration</a>';
    echo "</div>";
    echo "</body>";
    echo "</html>";

    exit;
}


// ======================================================
// 6. CONNECT TO DATABASE (PDO) & PERSISTENCE
// ======================================================

require_once "db.php";

$newStudentId = null;
$submittedAt   = date("Y-m-d H:i:s");
$storageTarget = "Database";

// 7. CHECK & INSERT INTO MYSQL (IF AVAILABLE)
if ($pdo) {
    try {
        $checkStmt = $pdo->prepare(
            "SELECT id FROM students WHERE email = :email LIMIT 1"
        );
        $checkStmt->execute([":email" => $email]);

        if ($checkStmt->fetch()) {
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Already Registered</title>";
            echo "<style>body{font-family:Arial,sans-serif;background:#f4f6f8;padding:40px;}.box{max-width:650px;margin:auto;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);text-align:center;}h2{color:#d32f2f;}a{display:inline-block;margin-top:20px;padding:10px 18px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;}</style></head><body>";
            echo "<div class='box'>";
            echo "<h2>⚠️ Already Registered</h2>";
            echo "<p>The email <strong>" . htmlspecialchars($email, ENT_QUOTES, "UTF-8") . "</strong> is already registered in the database.</p>";
            echo '<a href="registration.html">Go Back to Registration</a>';
            echo "</div></body></html>";
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO students (name, email, phone, course, semester, message)
             VALUES (:name, :email, :phone, :course, :semester, :message)"
        );

        $stmt->execute([
            ":name"     => $name,
            ":email"    => $email,
            ":phone"    => $phone,
            ":course"   => $course,
            ":semester" => (int) $semester,
            ":message"  => ($message !== "") ? $message : null,
        ]);

        $newStudentId = $pdo->lastInsertId();
        $storageTarget = "MySQL Database & File Records";
    } catch (Throwable $dbErr) {
        error_log("DB Insert error: " . $dbErr->getMessage());
        $newStudentId = null;
        $storageTarget = "Local Records (JSON & CSV)";
    }
} else {
    $storageTarget = "Local Records (records.json & records.csv)";
}

// ======================================================
// 8. PERSIST TO JSON AND CSV (PRACTICAL 7 CORE)
// ======================================================

$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . "records.json";
$records = [];
if (file_exists($jsonFile)) {
    $raw = @file_get_contents($jsonFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $records = $decoded;
    }
}

$records[] = [
    "name"         => $name,
    "email"        => $email,
    "phone"        => $phone,
    "course"       => $course,
    "semester"     => $semester,
    "message"      => $message,
    "submitted_at" => $submittedAt
];
@file_put_contents($jsonFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . "records.csv";
$csvExists = file_exists($csvFile);
$fp = @fopen($csvFile, "a");
if ($fp) {
    if (!$csvExists || filesize($csvFile) === 0) {
        fputcsv($fp, ["Name", "Email", "Phone", "Course", "Semester", "Message", "Submitted At"]);
    }
    fputcsv($fp, [$name, $email, $phone, $course, $semester, $message, $submittedAt]);
    fclose($fp);
}

if (!$newStudentId) {
    $newStudentId = count($records);
}

// ======================================================
// 9. SUCCESS RESPONSE (Original UI/UX)
// ======================================================

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registration Successful</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 40px;
        }

        .success-box {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            color: #16803c;
        }

        .details {
            text-align: left;
            background: #f7f7f7;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .details p {
            margin: 10px 0;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 20px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        a.secondary {
            background: #198754;
            margin-left: 10px;
        }

    </style>

</head>

<body>

    <div class="success-box">

        <h1>✅ Registration Successful!</h1>

        <p>
            Your registration has been successfully saved (Student ID: #<?= htmlspecialchars((string)$newStudentId, ENT_QUOTES, "UTF-8") ?>).
        </p>

        <div class="details">

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?= htmlspecialchars($phone, ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Course:</strong>
                <?= htmlspecialchars($course, ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Semester:</strong>
                Semester <?= htmlspecialchars($semester, ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Submitted At:</strong>
                <?= htmlspecialchars($submittedAt, ENT_QUOTES, "UTF-8") ?>
            </p>

        </div>

        <a href="registration.html">
            Register Another Student
        </a>

        <a href="view_students.php" class="secondary">
            View All Students (DB)
        </a>

        <a href="index.html" style="background:#6c757d; margin-left:10px;">
            Go to StudentHub
        </a>

    </div>

</body>

</html>