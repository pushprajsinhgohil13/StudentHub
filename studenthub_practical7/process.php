<?php

/*
    StudentHub - Practical 7

    PHP Form Processing with
    Server-Side Validation and
    CSV/JSON File Storage
*/


// ======================================================
// 1. CHECK REQUEST METHOD
// ======================================================


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo "<h2>Error</h2>";
    echo "<p>Invalid request method.</p>";
    echo '<a href="registration.html">Go Back</a>';

    exit;
}


// ======================================================
// 2. GET FORM DATA
// ======================================================

$name = $_POST["name"] ?? "";
$email = $_POST["email"] ?? "";
$phone = $_POST["phone"] ?? "";
$course = $_POST["course"] ?? "";
$semester = $_POST["semester"] ?? "";
$message = $_POST["message"] ?? "";


// ======================================================
// 3. SANITIZE INPUT
// ======================================================

$name = trim($name);
$email = trim($email);
$phone = trim($phone);
$course = trim($course);
$semester = trim($semester);
$message = trim($message);


// Convert special characters safely
$name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$phone = htmlspecialchars($phone, ENT_QUOTES, "UTF-8");
$course = htmlspecialchars($course, ENT_QUOTES, "UTF-8");
$semester = htmlspecialchars($semester, ENT_QUOTES, "UTF-8");
$message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");


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
            background: #1677ff;
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
// 6. CREATE RECORD
// ======================================================

$record = [

    "name" => $name,

    "email" => $email,

    "phone" => $phone,

    "course" => $course,

    "semester" => $semester,

    "message" => $message,

    "submitted_at" => date("Y-m-d H:i:s")

];


// ======================================================
// 7. SAVE TO CSV
// ======================================================

$csvFile = "records.csv";


// Check whether CSV already has data
$fileExists = file_exists($csvFile);

$file = fopen($csvFile, "a");


// Make sure file opened successfully
if ($file === false) {

    die("Error: Unable to open CSV file.");
}


// Lock file before writing
if (flock($file, LOCK_EX)) {


    // Add header if file is empty/new
    if (!$fileExists || filesize($csvFile) === 0) {

        fputcsv($file, [

            "Name",
            "Email",
            "Phone",
            "Course",
            "Semester",
            "Message",
            "Submitted At"

        ]);
    }


    // Add record
    fputcsv($file, [

        $record["name"],
        $record["email"],
        $record["phone"],
        $record["course"],
        $record["semester"],
        $record["message"],
        $record["submitted_at"]

    ]);


    // Unlock
    flock($file, LOCK_UN);
}


fclose($file);


// ======================================================
// 8. SAVE TO JSON
// ======================================================

$jsonFile = "records.json";


// Existing JSON records
$existingRecords = [];


// If JSON file exists, read it
if (file_exists($jsonFile)) {

    $jsonData = file_get_contents($jsonFile);

    if ($jsonData !== false && trim($jsonData) !== "") {

        $decodedData = json_decode($jsonData, true);

        if (is_array($decodedData)) {

            $existingRecords = $decodedData;
        }
    }
}


// Add new record
$existingRecords[] = $record;


// Convert to JSON
$newJsonData = json_encode(

    $existingRecords,

    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE

);


// Write JSON safely
if (

    file_put_contents(

        $jsonFile,

        $newJsonData,

        LOCK_EX

    ) === false

) {

    die("Error: Unable to save JSON file.");
}


// ======================================================
// 9. SUCCESS RESPONSE
// ======================================================

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

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

            background: #1677ff;

            color: white;

            text-decoration: none;

            border-radius: 6px;

        }

    </style>

</head>

<body>

    <div class="success-box">

        <h1>✅ Registration Successful!</h1>

        <p>
            Your registration has been successfully submitted.
        </p>


        <div class="details">

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($name) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($email) ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?= htmlspecialchars($phone) ?>
            </p>

            <p>
                <strong>Course:</strong>
                <?= htmlspecialchars($course) ?>
            </p>

            <p>
                <strong>Semester:</strong>
                <?= htmlspecialchars($semester) ?>
            </p>

            <p>
                <strong>Submitted At:</strong>
                <?= htmlspecialchars($record["submitted_at"]) ?>
            </p>

        </div>


        <a href="registration.html">
            Register Another Student
        </a>

        <a href="index.html">
            Go to StudentHub
        </a>

    </div>

</body>

</html>