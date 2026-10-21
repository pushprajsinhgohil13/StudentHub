<?php
/**
 * StudentHub - Practical 11 Intermediate Extension
 * Export Student List to CSV
 */

require_once 'db.php';
start_secure_session();

// Open database connection using MySQLi
$conn = get_mysqli();
if (!$conn) {
    die("Database connection failed.");
}

// Log audit action
$username = $_SESSION['username'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;
log_audit_action('EXPORT_CSV', "Exported student list to CSV", $userId, $username);

// Fetch active students (excluding soft deleted)
$sql = "SELECT id, name, email, phone, course, semester, status, created_at 
        FROM students 
        WHERE status != 'deleted' 
        ORDER BY id ASC";
$result = $conn->query($sql);

$filename = "students_export_" . date('Y-m-d_His') . ".csv";

// Set download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV Header row
fputcsv($output, ['Student ID', 'Full Name', 'Email Address', 'Phone', 'Course', 'Semester', 'Status', 'Registered Date']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['course'],
            $row['semester'],
            ucfirst($row['status']),
            $row['created_at']
        ]);
    }
}

fclose($output);
exit;
