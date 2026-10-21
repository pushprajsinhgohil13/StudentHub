<?php
/**
 * StudentHub - Unified Database & Security Helper
 * Supports Practicals 8, 9, 10, 11, 12, 13, 14, 15
 * Features:
 * - Dual Driver: PDO (Practical 8) and MySQLi (Practicals 9-14)
 * - Session Management, Session Timeout, RBAC (Practical 10)
 * - Remember-Me Token Handler (Practical 10 Advanced)
 * - Audit Logger (Practicals 9, 10, 12, 14)
 * - CSRF Protection (Practical 9 Advanced)
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'studenthub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Global connection handles
$mysqli = null;
$pdo = null;
$dbConnected = false;
$activePort = 3306;

// Try ports 3306 and 3307
$ports = [3306, 3307];

foreach ($ports as $port) {
    try {
        // 1. Initialize PDO
        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $tempPdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);

        // 2. Initialize MySQLi
        $tempMysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);
        if (!$tempMysqli->connect_error) {
            $tempMysqli->set_charset(DB_CHARSET);

            $pdo = $tempPdo;
            $mysqli = $tempMysqli;
            $dbConnected = true;
            $activePort = $port;
            break;
        }
    } catch (Throwable $e) {
        // Auto-create database & tables if missing
        try {
            $serverPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . $port . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'studenthub.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $serverPdo->exec($sqlContent);
            }
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);
            $mysqli->set_charset(DB_CHARSET);
            $dbConnected = true;
            $activePort = $port;
            break;
        } catch (Throwable $inner) {
            // port unavailable
        }
    }
}

// ----------------------------------------------------
// AUTO-SCHEMA COMPATIBILITY (Practicals 8-15)
// Ensures columns (status, poster_image, remember_token)
// exist if database was previously initialized with an older schema.
// ----------------------------------------------------
if ($mysqli && $dbConnected) {
    try {
        // 1. Ensure students.status ENUM exists
        $rStatus = $mysqli->query("SHOW COLUMNS FROM students LIKE 'status'");
        if ($rStatus && $rStatus->num_rows === 0) {
            $mysqli->query("ALTER TABLE students ADD COLUMN status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active' AFTER semester");
        }
        // 2. Ensure students.updated_at exists
        $rUpd = $mysqli->query("SHOW COLUMNS FROM students LIKE 'updated_at'");
        if ($rUpd && $rUpd->num_rows === 0) {
            $mysqli->query("ALTER TABLE students ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
        // 3. Ensure users table exists with required columns
        $rUsers = $mysqli->query("SHOW TABLES LIKE 'users'");
        if ($rUsers && $rUsers->num_rows === 0) {
            $mysqli->query("CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone CHAR(10) NOT NULL,
                role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
                course VARCHAR(50) DEFAULT NULL,
                semester TINYINT DEFAULT 1,
                password_hash VARCHAR(255) NOT NULL,
                remember_token VARCHAR(64) DEFAULT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                last_login DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $adminHash = password_hash('Password@123', PASSWORD_BCRYPT);
            $mysqli->query("INSERT IGNORE INTO users (name, username, email, phone, role, course, semester, password_hash, status)
                VALUES ('System Administrator', 'admin', 'admin@studenthub.local', '9876543210', 'admin', 'Administration', 8, '$adminHash', 'active'),
                       ('Aarav Sharma', 'student1', 'aarav.sharma@example.com', '9876543211', 'student', 'B.Tech CSE', 4, '$adminHash', 'active')");
        } else {
            // Ensure role column exists
            $rRole = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
            if ($rRole && $rRole->num_rows === 0) {
                $mysqli->query("ALTER TABLE users ADD COLUMN role ENUM('admin', 'student') NOT NULL DEFAULT 'student' AFTER phone");
            }
            // Ensure remember_token column exists
            $rTok = $mysqli->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
            if ($rTok && $rTok->num_rows === 0) {
                $mysqli->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) DEFAULT NULL AFTER password_hash");
            }
            // Ensure status column exists
            $rUStat = $mysqli->query("SHOW COLUMNS FROM users LIKE 'status'");
            if ($rUStat && $rUStat->num_rows === 0) {
                $mysqli->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER remember_token");
            }
            // Ensure last_login column exists
            $rLogin = $mysqli->query("SHOW COLUMNS FROM users LIKE 'last_login'");
            if ($rLogin && $rLogin->num_rows === 0) {
                $mysqli->query("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER status");
            }
            // Ensure admin user exists and has role='admin'
            $rAdmin = $mysqli->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
            if ($rAdmin && $rAdmin->num_rows === 0) {
                $adminHash = password_hash('Password@123', PASSWORD_BCRYPT);
                $mysqli->query("INSERT IGNORE INTO users (name, username, email, phone, role, course, semester, password_hash, status)
                    VALUES ('System Administrator', 'admin', 'admin@studenthub.local', '9876543210', 'admin', 'Administration', 8, '$adminHash', 'active')");
            } else {
                $mysqli->query("UPDATE users SET role = 'admin' WHERE username = 'admin'");
            }
        }
        // 4. Ensure events columns exist
        $rEvents = $mysqli->query("SHOW TABLES LIKE 'events'");
        if ($rEvents && $rEvents->num_rows > 0) {
            $rPost = $mysqli->query("SHOW COLUMNS FROM events LIKE 'poster_image'");
            if ($rPost && $rPost->num_rows === 0) {
                $mysqli->query("ALTER TABLE events ADD COLUMN poster_image VARCHAR(255) DEFAULT NULL AFTER max_seats");
            }
            $rEStat = $mysqli->query("SHOW COLUMNS FROM events LIKE 'status'");
            if ($rEStat && $rEStat->num_rows === 0) {
                $mysqli->query("ALTER TABLE events ADD COLUMN status ENUM('open', 'closed') NOT NULL DEFAULT 'open' AFTER poster_image");
            }
        }
        // 5. Ensure audit_logs exists
        $mysqli->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            username VARCHAR(50) DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $ignore) {
        // Non-blocking auto-heal
    }
}


// ----------------------------------------------------
// CONNECTION GETTERS
// ----------------------------------------------------
function get_pdo() {
    global $pdo;
    return $pdo;
}

// Alias used by Practical 8 legacy scripts
function get_db_connection() {
    global $pdo;
    return $pdo;
}

function get_mysqli() {
    global $mysqli;
    return $mysqli;
}

// Called by Practical 8 scripts to assert connection exists
function require_db_connection() {
    global $pdo, $dbConnected;
    if (!$dbConnected || !$pdo) {
        http_response_code(503);
        die('<p style="font-family:sans-serif; color:#dc3545; padding:20px;">⚠️ Database connection failed. Please ensure XAMPP MySQL service is running.</p>');
    }
}

// ----------------------------------------------------
// AUDIT LOGGER (Practicals 9, 10, 12, 14)
// ----------------------------------------------------
function log_audit_action($action, $details = null, $userId = null, $username = null) {
    global $mysqli;
    if (!$mysqli) return false;

    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    if ($username === null && isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    $stmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, username, action, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $userId, $username, $action, $ip, $agent, $details);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
    return false;
}

// ----------------------------------------------------
// SESSION & RBAC MANAGEMENT (Practical 10 & 14)
// ----------------------------------------------------
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure cookie settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }

    // Practical 10: Inactivity Session Timeout (15 minutes = 900 seconds)
    $timeoutDuration = 900;
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {
            // Session timed out!
            log_audit_action('SESSION_TIMEOUT', 'User session expired due to 15 minutes of inactivity.');
            destroy_secure_session();
            header("Location: login.php?msg=timeout");
            exit;
        }
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    // Check remember-me token if not authenticated in session
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        verify_remember_cookie();
    }
}

function destroy_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Remove remember cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, "/");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function require_login() {
    start_secure_session();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=unauthorized");
        exit;
    }
}

function require_role($requiredRole) {
    require_login();
    if (($_SESSION['role'] ?? '') !== $requiredRole) {
        log_audit_action('UNAUTHORIZED_ACCESS_BLOCKED', "User attempted access to $requiredRole restricted resource.");
        header("Location: login.php?error=forbidden");
        exit;
    }
}

// ----------------------------------------------------
// REMEMBER-ME TOKEN HANDLER (Practical 10 Advanced)
// ----------------------------------------------------
function set_remember_cookie($userId) {
    global $mysqli;
    if (!$mysqli) return;

    $rawToken = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);

    $stmt = $mysqli->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $hashedToken, $userId);
        $stmt->execute();
        $stmt->close();

        // 30 days cookie expiry
        setcookie('remember_token', $userId . ':' . $rawToken, time() + (86400 * 30), "/", "", false, true);
    }
}

function verify_remember_cookie() {
    global $mysqli;
    if (!$mysqli || empty($_COOKIE['remember_token'])) return false;

    $parts = explode(':', $_COOKIE['remember_token']);
    if (count($parts) !== 2) return false;

    $userId = (int)$parts[0];
    $rawToken = $parts[1];
    $hashedToken = hash('sha256', $rawToken);

    $stmt = $mysqli->prepare("SELECT id, name, username, email, role, course, status, remember_token FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && hash_equals($user['remember_token'], $hashedToken)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['LAST_ACTIVITY'] = time();
            log_audit_action('AUTO_LOGIN_REMEMBER_ME', 'Authenticated via remember-me cookie.');
            return true;
        }
    }
    return false;
}

// ----------------------------------------------------
// CSRF PROTECTION (Practical 9 Advanced)
// ----------------------------------------------------
function get_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
