<?php
/**
 * StudentHub - Practical 12: Event Management CRUD with Poster Upload & File Validation
 * Admin CRUD for campus events with poster upload, MIME/size validation, 
 * secure unique renaming, .htaccess directory protection, and audit logging.
 */

require_once 'db.php';
start_secure_session();

$conn = get_mysqli();
$feedback = null;
$csrfToken = get_csrf_token();

$uploadDir = __DIR__ . '/uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper function to handle secure poster upload
function handle_poster_upload($fileInputName, &$errorMsg) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "File upload failed with error code: " . $file['error'];
        return false;
    }

    // 1. Max size check: 2MB (2 * 1024 * 1024 bytes)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $errorMsg = "Poster file size exceeds the 2MB limit. (Current: " . round($file['size']/1024/1024, 2) . "MB)";
        return false;
    }

    // 2. MIME type verification using finfo
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        $errorMsg = "Invalid file type ($mimeType). Only JPG, PNG, and WebP images are permitted.";
        return false;
    }

    // 3. Extension matching
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = $extMap[$mimeType];

    // 4. Secure Random Renaming (prevents path traversal and execution)
    $filename = 'event_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
    $targetPath = __DIR__ . '/uploads/posters/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMsg = "Could not save uploaded file to destination directory.";
        return false;
    }

    return $filename;
}

// Handle Form Submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $feedback = ['type' => 'danger', 'msg' => 'Security token invalid or expired. Please refresh the page.'];
    } else {
        $action = $_POST['action'] ?? '';

        // 1. CREATE EVENT
        if ($action === 'create') {
            $title       = trim($_POST['title'] ?? '');
            $category    = $_POST['category'] ?? 'workshop';
            $description = trim($_POST['description'] ?? '');
            $eventDate   = $_POST['event_date'] ?? '';
            $venue       = trim($_POST['venue'] ?? '');
            $maxSeats    = (int)($_POST['max_seats'] ?? 50);
            $status      = in_array($_POST['status'] ?? '', ['open', 'closed']) ? $_POST['status'] : 'open';

            $uploadError = null;
            $posterFilename = handle_poster_upload('poster_image', $uploadError);

            if ($uploadError) {
                $feedback = ['type' => 'danger', 'msg' => "Poster Error: $uploadError"];
            } elseif (empty($title) || empty($description) || empty($eventDate) || empty($venue)) {
                $feedback = ['type' => 'danger', 'msg' => 'Please provide all mandatory event details.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, venue, max_seats, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiss", $title, $category, $description, $eventDate, $venue, $maxSeats, $posterFilename, $status);
                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    $feedback = ['type' => 'success', 'msg' => "✅ Event #$newId ('$title') created successfully!"];
                    log_audit_action('EVENT_CREATED', "Admin created event #$newId ($title)", $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'admin');
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Database error while saving event.'];
                }
                $stmt->close();
            }
        }

        // 2. UPDATE EVENT
        elseif ($action === 'update') {
            $id          = (int)($_POST['event_id'] ?? 0);
            $title       = trim($_POST['title'] ?? '');
            $category    = $_POST['category'] ?? 'workshop';
            $description = trim($_POST['description'] ?? '');
            $eventDate   = $_POST['event_date'] ?? '';
            $venue       = trim($_POST['venue'] ?? '');
            $maxSeats    = (int)($_POST['max_seats'] ?? 50);
            $status      = in_array($_POST['status'] ?? '', ['open', 'closed']) ? $_POST['status'] : 'open';

            $uploadError = null;
            $newPoster = handle_poster_upload('poster_image', $uploadError);

            if ($uploadError) {
                $feedback = ['type' => 'danger', 'msg' => "Poster Upload Error: $uploadError"];
            } elseif ($id <= 0 || empty($title) || empty($description) || empty($eventDate) || empty($venue)) {
                $feedback = ['type' => 'danger', 'msg' => 'Please complete all required event fields.'];
            } else {
                if ($newPoster) {
                    $stmt = $conn->prepare("UPDATE events SET title=?, category=?, description=?, event_date=?, venue=?, max_seats=?, poster_image=?, status=? WHERE id=?");
                    $stmt->bind_param("sssssissi", $title, $category, $description, $eventDate, $venue, $maxSeats, $newPoster, $status, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE events SET title=?, category=?, description=?, event_date=?, venue=?, max_seats=?, status=? WHERE id=?");
                    $stmt->bind_param("sssssisi", $title, $category, $description, $eventDate, $venue, $maxSeats, $status, $id);
                }

                if ($stmt->execute()) {
                    $feedback = ['type' => 'success', 'msg' => "✅ Event #$id ('$title') updated successfully."];
                    log_audit_action('EVENT_UPDATED', "Admin updated event #$id ($title)", $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'admin');
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Failed to update event details.'];
                }
                $stmt->close();
            }
        }

        // 3. DELETE EVENT
        elseif ($action === 'delete') {
            $id = (int)($_POST['event_id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $feedback = ['type' => 'warning', 'msg' => "🗑️ Event #$id was removed from the database."];
                    log_audit_action('EVENT_DELETED', "Admin deleted event #$id", $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'admin');
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Could not delete event due to associated registrations.'];
                }
                $stmt->close();
            }
        }
    }
}

// Search and Filter logic
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$searchQuery    = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if (!empty($categoryFilter)) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}
if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND (title LIKE ? OR venue LIKE ? OR description LIKE ?)";
    $like = "%" . $searchQuery . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY event_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management &amp; Posters - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .banner-box {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #fff;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 4px 14px rgba(13, 110, 253, 0.25);
        }
        .banner-box h1 { margin: 0 0 0.4rem 0; font-size: 1.8rem; }
        .banner-box p { margin: 0; opacity: 0.9; }

        .filter-bar {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar input, .filter-bar select {
            padding: 0.6rem 0.85rem;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 6px;
            font-size: 0.92rem;
            background: var(--input-bg, #fff);
            color: var(--text-color, #1e293b);
        }

        .alert-box {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }
        .event-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }
        .event-poster-wrapper {
            height: 180px;
            background: #f1f5f9;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .event-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-poster {
            color: #94a3b8;
            font-size: 3rem;
        }
        .badge-category {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(13, 110, 253, 0.9);
            color: #fff;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-status {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: #d1fae5; color: #065f46; }
        .status-closed { background: #fee2e2; color: #991b1b; }

        .event-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .event-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: var(--text-color, #1e293b);
        }
        .event-meta {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .event-desc {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }
        .event-card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color, #e2e8f0);
            padding-top: 0.85rem;
        }

        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 580px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .modal-header h3 { margin: 0; font-size: 1.3rem; color: #1e293b; }
        .modal-close {
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; font-size: 0.88rem; margin-bottom: 0.35rem; color: #334155; }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>StudentHub</span>
        </div>
        <nav class="nav-links">
            <a href="index.html">Home</a>
            <a href="courses.html">Courses</a>
            <a href="events.html">Events Portal</a>
            <a href="students_crud.php">Students</a>
            <a href="events_crud.php" class="active">Event Manager</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin_dashboard.php" style="color: #fd7e14; font-weight: bold;"><i class="fa-solid fa-shield-halved"></i> Admin</a>
                <?php else: ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" style="color: #dc3545;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fa-solid fa-user"></i> Login</a>
            <?php endif; ?>
            <button id="themeToggle" class="theme-btn" aria-label="Toggle Theme"><i class="fa-solid fa-moon"></i></button>
        </nav>
        <div class="hamburger"><i class="fa-solid fa-bars"></i></div>
    </header>

    <div class="event-container">
        <!-- Banner Box -->
        <div class="banner-box">
            <div>
                <h1>Campus Event Management</h1>
                <p>Create, update, upload event posters (MIME verified &le; 2MB), and manage schedules.</p>
            </div>
            <div>
                <button class="btn" style="background: #fff; color: #0d6efd; font-weight: 600;" onclick="openAddModal()">
                    <i class="fa-solid fa-calendar-plus"></i> Add New Event
                </button>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($feedback): ?>
            <div class="alert-box alert-<?php echo $feedback['type']; ?>">
                <i class="fa-solid <?php echo $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($feedback['msg']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 2 1 200px;">
            
            <select name="category" style="flex: 1 1 150px;">
                <option value="">All Categories</option>
                <option value="workshop" <?php echo $categoryFilter === 'workshop' ? 'selected' : ''; ?>>Workshop</option>
                <option value="seminar" <?php echo $categoryFilter === 'seminar' ? 'selected' : ''; ?>>Seminar</option>
                <option value="hackathon" <?php echo $categoryFilter === 'hackathon' ? 'selected' : ''; ?>>Hackathon</option>
                <option value="cultural" <?php echo $categoryFilter === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                <option value="sports" <?php echo $categoryFilter === 'sports' ? 'selected' : ''; ?>>Sports</option>
            </select>

            <select name="status" style="flex: 1 1 130px;">
                <option value="">All Statuses</option>
                <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open Events</option>
                <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed Events</option>
            </select>

            <button type="submit" class="btn" style="padding: 0.6rem 1.2rem;">
                <i class="fa-solid fa-filter"></i> Apply Filter
            </button>
            <?php if (!empty($searchQuery) || !empty($categoryFilter) || !empty($statusFilter)): ?>
                <a href="events_crud.php" class="btn" style="background: #64748b; color: #fff; padding: 0.6rem 1rem;">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--card-bg, #fff); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0); color: #64748b;">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block;"></i>
                    No events found matching your filter criteria.
                </div>
            <?php else: ?>
                <?php foreach ($events as $e): ?>
                    <div class="event-card">
                        <div class="event-poster-wrapper">
                            <?php if (!empty($e['poster_image']) && file_exists(__DIR__ . '/uploads/posters/' . $e['poster_image'])): ?>
                                <img src="uploads/posters/<?php echo htmlspecialchars($e['poster_image']); ?>" alt="Poster" class="event-poster-img">
                            <?php else: ?>
                                <i class="fa-solid fa-image no-poster"></i>
                            <?php endif; ?>
                            <span class="badge-category"><?php echo htmlspecialchars($e['category']); ?></span>
                            <span class="badge-status status-<?php echo $e['status']; ?>"><?php echo strtoupper($e['status']); ?></span>
                        </div>
                        <div class="event-body">
                            <h3 class="event-title"><?php echo htmlspecialchars($e['title']); ?></h3>
                            <div class="event-meta">
                                <div><i class="fa-regular fa-calendar" style="color: #0d6efd;"></i> <?php echo date('F d, Y', strtotime($e['event_date'])); ?></div>
                                <div><i class="fa-solid fa-location-dot" style="color: #dc3545;"></i> <?php echo htmlspecialchars($e['venue']); ?></div>
                                <div><i class="fa-solid fa-chair" style="color: #198754;"></i> Capacity: <?php echo htmlspecialchars($e['max_seats']); ?> Seats</div>
                            </div>
                            <p class="event-desc"><?php echo htmlspecialchars(substr($e['description'], 0, 140)) . (strlen($e['description']) > 140 ? '...' : ''); ?></p>
                            
                            <div class="event-card-actions">
                                <span style="font-size: 0.8rem; color: #94a3b8;">ID: #<?php echo $e['id']; ?></span>
                                <div>
                                    <button class="btn" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; background: #0d6efd; color:#fff;" onclick='openEditModal(<?php echo json_encode($e); ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ Delete event \'<?php echo htmlspecialchars(addslashes($e['title'])); ?>\' permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                        <button type="submit" class="btn" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; background: #dc3545; color:#fff;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: Add Event -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-calendar-plus" style="color: #0d6efd;"></i> Add Campus Event</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. AI Innovation Summit 2026" required>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label>Category *</label>
                        <select name="category" class="form-control" required>
                            <option value="workshop">Workshop</option>
                            <option value="seminar">Seminar</option>
                            <option value="hackathon">Hackathon</option>
                            <option value="cultural">Cultural</option>
                            <option value="sports">Sports</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <div style="flex: 2;">
                        <label>Venue / Location *</label>
                        <input type="text" name="venue" class="form-control" placeholder="e.g. Main Auditorium Hall A" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Max Seats *</label>
                        <input type="number" name="max_seats" class="form-control" value="60" min="5" max="1000" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Event Description *</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief outline and schedule of the event..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Event Poster Image (JPG, PNG, WebP &le; 2MB)</label>
                    <input type="file" name="poster_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="open">Open for Registrations</option>
                        <option value="closed">Closed / Finished</option>
                    </select>
                </div>
                <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn" style="background:#cbd5e1; color:#334155;" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Event -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: #0d6efd;"></i> Edit Campus Event</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="event_id" id="edit_event_id">

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label>Category *</label>
                        <select name="category" id="edit_category" class="form-control" required>
                            <option value="workshop">Workshop</option>
                            <option value="seminar">Seminar</option>
                            <option value="hackathon">Hackathon</option>
                            <option value="cultural">Cultural</option>
                            <option value="sports">Sports</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" id="edit_event_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <div style="flex: 2;">
                        <label>Venue / Location *</label>
                        <input type="text" name="venue" id="edit_venue" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Max Seats *</label>
                        <input type="number" name="max_seats" id="edit_max_seats" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Event Description *</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Replace Poster (Optional &le; 2MB)</label>
                    <input type="file" name="poster_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn" style="background:#cbd5e1; color:#334155;" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2026 StudentHub. All rights reserved. Event Management &amp; Secure File Uploads.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('open');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('open');
        }
        function openEditModal(event) {
            document.getElementById('edit_event_id').value = event.id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_category').value = event.category;
            document.getElementById('edit_event_date').value = event.event_date;
            document.getElementById('edit_venue').value = event.venue;
            document.getElementById('edit_max_seats').value = event.max_seats;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_status').value = event.status;
            document.getElementById('editModal').classList.add('open');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('open');
        }
    </script>
</body>
</html>
