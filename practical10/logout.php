<?php
/**
 * StudentHub - Logout Handler
 * Securely terminates session, cleans cookies, and redirects.
 */

require_once 'db.php';
start_secure_session();

$username = $_SESSION['username'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    log_audit_action('LOGOUT', "User $username logged out securely.", $userId, $username);
}

// Clear remember me token in DB if set
if ($userId) {
    $conn = get_mysqli();
    if ($conn) {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy session
destroy_secure_session();

// Redirect to login with logout message
header("Location: login.php?msg=logged_out");
exit;
