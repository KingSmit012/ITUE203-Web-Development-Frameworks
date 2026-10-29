<?php
// api/events.php - JSON REST API for Event Management & Poster Uploads

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth.php';

// Authentication check: User must be logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper to send json response and exit
function sendJson($success, $message, $extra = [], $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 1. GET: List All Events (or single event by ID)
// -------------------------------------------------------------
if ($method === 'GET') {
    // Single event by ID
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $eventId = (int)$_GET['id'];
        $stmt = $conn->prepare("
            SELECT e.event_id, e.title, e.description, e.event_date, e.location, e.poster, e.created_at,
                   COUNT(r.registration_id) AS registration_count
            FROM events e
            LEFT JOIN registrations r ON e.event_id = r.event_id
            WHERE e.event_id = ?
            GROUP BY e.event_id
            LIMIT 1
        ");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($event = $res->fetch_assoc()) {
            sendJson(true, 'Event retrieved', ['event' => $event]);
        } else {
            sendJson(false, 'Event not found', [], 404);
        }
        $stmt->close();
    }

    // Search events
    $search = trim($_GET['search'] ?? '');
    $sql = "
        SELECT e.event_id, e.title, e.description, e.event_date, e.location, e.poster, e.created_at,
               COUNT(r.registration_id) AS registration_count
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id
    ";

    $params = [];
    $types  = "";

    if (!empty($search)) {
        $sql .= " WHERE (e.title LIKE ? OR e.location LIKE ? OR e.description LIKE ?)";
        $searchParam = "%" . $search . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    $sql .= " GROUP BY e.event_id ORDER BY e.event_date ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();

    sendJson(true, 'Events fetched successfully', [
        'count'  => count($events),
        'events' => $events
    ]);
}

// -------------------------------------------------------------
// 2. POST: Create, Update, Delete Events, or Register for Event
// -------------------------------------------------------------
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle student event registration (allowed for students & admins)
    if ($action === 'register') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($eventId <= 0) {
            sendJson(false, 'Valid Event ID is required.', [], 400);
        }

        // Get student_id matching current user's email
        $userEmail = $_SESSION['email'] ?? '';
        $findStudent = $conn->prepare("SELECT student_id FROM students WHERE email = ? LIMIT 1");
        $findStudent->bind_param("s", $userEmail);
        $findStudent->execute();
        $studentRes = $findStudent->get_result();

        if ($studentRow = $studentRes->fetch_assoc()) {
            $studentId = (int)$studentRow['student_id'];
        } else {
            // If student not found in students table, automatically create student record
            $nameParts = explode(' ', $_SESSION['name'] ?? $_SESSION['username'], 2);
            $fName = $nameParts[0] ?? 'Student';
            $lName = $nameParts[1] ?? 'User';
            $ins = $conn->prepare("INSERT INTO students (first_name, last_name, email) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $fName, $lName, $userEmail);
            $ins->execute();
            $studentId = $ins->insert_id;
            $ins->close();
        }
        $findStudent->close();

        // Check if already registered
        $checkReg = $conn->prepare("SELECT registration_id FROM registrations WHERE student_id = ? AND event_id = ? LIMIT 1");
        $checkReg->bind_param("ii", $studentId, $eventId);
        $checkReg->execute();
        if ($checkReg->get_result()->num_rows > 0) {
            $checkReg->close();
            sendJson(false, 'You are already registered for this event.', [], 409);
        }
        $checkReg->close();

        // Insert registration
        $regStmt = $conn->prepare("INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'Registered')");
        $regStmt->bind_param("ii", $studentId, $eventId);
        if ($regStmt->execute()) {
            $regStmt->close();
            sendJson(true, 'Registration successful! See you at the event.');
        } else {
            $err = $regStmt->error;
            $regStmt->close();
            sendJson(false, 'Failed to register: ' . $err, [], 500);
        }
    }

    // All other actions (create, update, delete) REQUIRE ADMIN ROLE
    if (($_SESSION['role'] ?? 'student') !== 'admin') {
        sendJson(false, 'Access denied. Administrator privileges are required.', [], 403);
    }

    // Helper: Validate and upload poster file
    function handlePosterUpload($file, &$errors) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // 1. File Size Validation (Max 2MB = 2 * 1024 * 1024 = 2,097,152 bytes)
        $maxBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $errors[] = "Poster image exceeds the 2MB size limit.";
            return null;
        }

        // 2. File Type Validation (Extension & MIME type)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes      = ['image/jpeg', 'image/png', 'image/webp'];

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions, true)) {
            $errors[] = "Invalid poster format. Allowed extensions: JPG, PNG, WEBP.";
            return null;
        }

        // Check actual MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes, true)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and WEBP images are allowed.";
            return null;
        }

        // 3. Generate safe unique filename
        $uploadDir = __DIR__ . '/../uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFilename = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/events/' . $newFilename;
        } else {
            $errors[] = "Failed to upload the poster file. Check directory permissions.";
            return null;
        }
    }

    // A. Action: CREATE Event
    if ($action === 'create' || $action === 'add') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $location    = trim($_POST['location'] ?? '');

        $errors = [];
        if (empty($title)) {
            $errors[] = "Event title is required.";
        }
        if (empty($eventDate)) {
            $errors[] = "Event date is required.";
        }
        if (empty($location)) {
            $errors[] = "Event location is required.";
        }

        // Process poster upload if provided
        $posterPath = null;
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $posterPath = handlePosterUpload($_FILES['poster'], $errors);
        }

        if (!empty($errors)) {
            sendJson(false, implode(' ', $errors), ['errors' => $errors], 400);
        }

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, poster) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $eventDate, $location, $posterPath);

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();
            sendJson(true, 'Event created successfully!', [
                'event_id' => $newId,
                'poster'   => $posterPath
            ], 201);
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendJson(false, 'Database insert error: ' . $err, [], 500);
        }
    }

    // B. Action: UPDATE Event
    if ($action === 'update' || $action === 'edit') {
        $eventId     = (int)($_POST['event_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $location    = trim($_POST['location'] ?? '');

        if ($eventId <= 0) {
            sendJson(false, 'Valid Event ID is required for update.', [], 400);
        }

        $errors = [];
        if (empty($title)) {
            $errors[] = "Event title is required.";
        }
        if (empty($eventDate)) {
            $errors[] = "Event date is required.";
        }
        if (empty($location)) {
            $errors[] = "Event location is required.";
        }

        // Get current event data to preserve or replace poster
        $currStmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ? LIMIT 1");
        $currStmt->bind_param("i", $eventId);
        $currStmt->execute();
        $currRes = $currStmt->get_result();
        if ($currRow = $currRes->fetch_assoc()) {
            $existingPoster = $currRow['poster'];
        } else {
            $currStmt->close();
            sendJson(false, 'Event not found.', [], 404);
        }
        $currStmt->close();

        // Process new poster upload if provided
        $posterPath = $existingPoster;
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $newPoster = handlePosterUpload($_FILES['poster'], $errors);
            if ($newPoster) {
                // Delete old poster file if existed
                if (!empty($existingPoster) && file_exists(__DIR__ . '/../' . $existingPoster)) {
                    @unlink(__DIR__ . '/../' . $existingPoster);
                }
                $posterPath = $newPoster;
            }
        }

        if (!empty($errors)) {
            sendJson(false, implode(' ', $errors), ['errors' => $errors], 400);
        }

        $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, poster = ? WHERE event_id = ?");
        $stmt->bind_param("sssssi", $title, $description, $eventDate, $location, $posterPath, $eventId);

        if ($stmt->execute()) {
            $stmt->close();
            sendJson(true, 'Event updated successfully!', ['poster' => $posterPath]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            sendJson(false, 'Database update error: ' . $err, [], 500);
        }
    }

    // C. Action: DELETE Event
    if ($action === 'delete') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($eventId <= 0) {
            sendJson(false, 'Valid Event ID is required for deletion.', [], 400);
        }

        // Fetch poster path to unlink file
        $currStmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ? LIMIT 1");
        $currStmt->bind_param("i", $eventId);
        $currStmt->execute();
        if ($row = $currStmt->get_result()->fetch_assoc()) {
            if (!empty($row['poster']) && file_exists(__DIR__ . '/../' . $row['poster'])) {
                @unlink(__DIR__ . '/../' . $row['poster']);
            }
        }
        $currStmt->close();

        // Remove registrations for this event
        $delReg = $conn->prepare("DELETE FROM registrations WHERE event_id = ?");
        $delReg->bind_param("i", $eventId);
        $delReg->execute();
        $delReg->close();

        // Delete event
        $delStmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $delStmt->bind_param("i", $eventId);

        if ($delStmt->execute()) {
            if ($delStmt->affected_rows > 0) {
                $delStmt->close();
                sendJson(true, 'Event deleted successfully.');
            } else {
                $delStmt->close();
                sendJson(false, 'Event not found.', [], 404);
            }
        } else {
            $err = $delStmt->error;
            $delStmt->close();
            sendJson(false, 'Database deletion error: ' . $err, [], 500);
        }
    }

    sendJson(false, 'Invalid action specified.', [], 400);
}

sendJson(false, 'HTTP method not supported.', [], 405);
