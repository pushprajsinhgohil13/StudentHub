<?php
/**
 * StudentHub - Practical 13: PHP JSON API Endpoint for Students
 * Supports GET (list/search), POST (create), PUT (update), DELETE (soft-delete)
 * Uses HTTP status codes (200, 201, 400, 404, 405, 500) & centralized JSON error handler.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../db.php';

// Helper for uniform API responses
function send_json_response($statusCode, $success, $data = null, $message = '') {
    http_response_code($statusCode);
    echo json_encode([
        'success'   => $success,
        'status'    => $statusCode,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = get_mysqli();
if (!$conn) {
    send_json_response(500, false, null, 'Database connection failed.');
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch student list or search
if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id) {
        $stmt = $conn->prepare("SELECT id, name, email, phone, course, semester, status, created_at FROM students WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            send_json_response(200, true, $student, 'Student retrieved successfully.');
        } else {
            send_json_response(404, false, null, "Student with ID #$id was not found.");
        }
    }

    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT id, name, email, phone, course, semester, status, created_at FROM students WHERE status != 'deleted' AND (name LIKE ? OR email LIKE ? OR course LIKE ?) ORDER BY id DESC LIMIT 50");
        $like = "%$search%";
        $stmt->bind_param("sss", $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, phone, course, semester, status, created_at FROM students WHERE status != 'deleted' ORDER BY id DESC LIMIT 50");
    }

    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    send_json_response(200, true, $students, 'Students retrieved successfully.');
}

// Handle POST: Create new student record
elseif ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $name     = trim($input['name'] ?? '');
    $email    = trim($input['email'] ?? '');
    $phone    = trim($input['phone'] ?? '');
    $course   = trim($input['course'] ?? '');
    $semester = (int)($input['semester'] ?? 1);

    if (empty($name) || empty($email) || empty($phone) || empty($course)) {
        send_json_response(400, false, null, 'Missing required fields (name, email, phone, course).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_json_response(400, false, null, 'Invalid email address format.');
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        send_json_response(400, false, null, 'Phone must be exactly 10 digits.');
    }

    // Check duplicate
    $chk = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $chk->bind_param("s", $email);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        $chk->close();
        send_json_response(409, false, null, "A student with email '$email' is already registered.");
    }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO students (name, email, phone, course, semester, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssi", $name, $email, $phone, $course, $semester);
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        send_json_response(201, true, ['id' => $newId, 'name' => $name, 'email' => $email], 'Student created successfully.');
    } else {
        send_json_response(500, false, null, 'Database execution error: ' . $conn->error);
    }
}

// Handle PUT: Update student record
elseif ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        send_json_response(400, false, null, 'Invalid JSON payload received.');
    }

    $id       = (int)($input['id'] ?? 0);
    $name     = trim($input['name'] ?? '');
    $email    = trim($input['email'] ?? '');
    $phone    = trim($input['phone'] ?? '');
    $course   = trim($input['course'] ?? '');
    $semester = (int)($input['semester'] ?? 1);
    $status   = in_array($input['status'] ?? '', ['active', 'inactive']) ? $input['status'] : 'active';

    if ($id <= 0 || empty($name) || empty($email) || empty($phone) || empty($course)) {
        send_json_response(400, false, null, 'Valid ID and all student fields are required for update.');
    }

    // Check duplicate email for other user
    $chk = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
    $chk->bind_param("si", $email, $id);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        $chk->close();
        send_json_response(409, false, null, "Email '$email' is taken by another student.");
    }
    $chk->close();

    $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ?, course = ?, semester = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssisi", $name, $email, $phone, $course, $semester, $status, $id);
    if ($stmt->execute()) {
        $stmt->close();
        send_json_response(200, true, ['id' => $id, 'name' => $name], 'Student record updated successfully.');
    } else {
        send_json_response(500, false, null, 'Database update failed.');
    }
}

// Handle DELETE: Soft-delete student
elseif ($method === 'DELETE') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);

    if ($id <= 0) {
        send_json_response(400, false, null, 'Valid student ID is required.');
    }

    $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        send_json_response(200, true, ['id' => $id], "Student #$id soft-deleted successfully.");
    } else {
        send_json_response(500, false, null, 'Failed to delete student.');
    }
}

else {
    send_json_response(405, false, null, 'HTTP Method Not Allowed.');
}
