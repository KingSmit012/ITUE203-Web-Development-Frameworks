<?php
// api/students.php - JSON REST API for Student Management

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
// 1. GET: List, Search, and Filter Students, or View Single Student
// -------------------------------------------------------------
if ($method === 'GET') {
    // Single student by ID
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $studentId = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT student_id, first_name, last_name, email, mobile, department, institute, created_at FROM students WHERE student_id = ? LIMIT 1");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($student = $res->fetch_assoc()) {
            sendJson(true, 'Student retrieved', ['student' => $student]);
        } else {
            sendJson(false, 'Student not found', [], 404);
        }
        $stmt->close();
    }

    // Search and filter parameters
    $search     = trim($_GET['search'] ?? '');
    $department = trim($_GET['department'] ?? '');
    $institute  = trim($_GET['institute'] ?? '');

    $sql = "SELECT student_id, first_name, last_name, email, mobile, department, institute, created_at FROM students WHERE 1=1";
    $params = [];
    $types  = "";

    if (!empty($search)) {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
        $searchParam = "%" . $search . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }

    if (!empty($department)) {
        $sql .= " AND department = ?";
        $params[] = $department;
        $types .= "s";
    }

    if (!empty($institute)) {
        $sql .= " AND institute = ?";
        $params[] = $institute;
        $types .= "s";
    }

    $sql .= " ORDER BY student_id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    sendJson(true, 'Students fetched successfully', [
        'count'    => count($students),
        'students' => $students
    ]);
}

// -------------------------------------------------------------
// 2. POST: Create, Update, Delete Students
// -------------------------------------------------------------
if ($method === 'POST') {
    // Parse JSON body or $_POST
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    $inputData = [];
    if (strpos($contentType, "application/json") !== false) {
        $raw = file_get_contents("php://input");
        $inputData = json_decode($raw, true) ?? [];
    } else {
        $inputData = $_POST;
    }

    $action = $inputData['action'] ?? 'create';

    // A. Action: CREATE student
    if ($action === 'create' || $action === 'add') {
        $firstName  = trim($inputData['first_name'] ?? '');
        $lastName   = trim($inputData['last_name'] ?? '');
        $email      = trim($inputData['email'] ?? '');
        $mobile     = trim($inputData['mobile'] ?? '');
        $department = trim($inputData['department'] ?? '');
        $institute  = trim($inputData['institute'] ?? '');

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
            sendJson(false, 'First name, last name, and email are required.', [], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(false, 'Invalid email address format.', [], 400);
        }

        if (!empty($mobile) && !preg_match("/^[0-9]{10}$/", $mobile)) {
            sendJson(false, 'Mobile number must be exactly 10 digits.', [], 400);
        }

        // Check duplicate email in students table
        $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE email = ? LIMIT 1");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            sendJson(false, 'A student with this email address already exists.', [], 409);
        }
        $checkStmt->close();

        // Insert using MySQLi prepared statement
        $insertStmt = $conn->prepare("INSERT INTO students (first_name, last_name, email, mobile, department, institute) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssss", $firstName, $lastName, $email, $mobile, $department, $institute);

        if ($insertStmt->execute()) {
            $newId = $insertStmt->insert_id;
            $insertStmt->close();
            sendJson(true, 'Student added successfully!', ['student_id' => $newId], 201);
        } else {
            $err = $insertStmt->error;
            $insertStmt->close();
            sendJson(false, 'Database insert error: ' . $err, [], 500);
        }
    }

    // B. Action: UPDATE student
    if ($action === 'update' || $action === 'edit') {
        $studentId  = (int)($inputData['student_id'] ?? 0);
        $firstName  = trim($inputData['first_name'] ?? '');
        $lastName   = trim($inputData['last_name'] ?? '');
        $email      = trim($inputData['email'] ?? '');
        $mobile     = trim($inputData['mobile'] ?? '');
        $department = trim($inputData['department'] ?? '');
        $institute  = trim($inputData['institute'] ?? '');

        if ($studentId <= 0) {
            sendJson(false, 'Valid Student ID is required for update.', [], 400);
        }

        if (empty($firstName) || empty($lastName) || empty($email)) {
            sendJson(false, 'First name, last name, and email are required.', [], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(false, 'Invalid email address format.', [], 400);
        }

        if (!empty($mobile) && !preg_match("/^[0-9]{10}$/", $mobile)) {
            sendJson(false, 'Mobile number must be exactly 10 digits.', [], 400);
        }

        // Check if student exists
        $checkExist = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkExist->bind_param("i", $studentId);
        $checkExist->execute();
        if ($checkExist->get_result()->num_rows === 0) {
            $checkExist->close();
            sendJson(false, 'Student not found.', [], 404);
        }
        $checkExist->close();

        // Check duplicate email among other students
        $dupStmt = $conn->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ? LIMIT 1");
        $dupStmt->bind_param("si", $email, $studentId);
        $dupStmt->execute();
        $dupStmt->store_result();
        if ($dupStmt->num_rows > 0) {
            $dupStmt->close();
            sendJson(false, 'Another student is already using this email address.', [], 409);
        }
        $dupStmt->close();

        // Update using MySQLi prepared statement
        $updateStmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ?, email = ?, mobile = ?, department = ?, institute = ? WHERE student_id = ?");
        $updateStmt->bind_param("ssssssi", $firstName, $lastName, $email, $mobile, $department, $institute, $studentId);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            sendJson(true, 'Student record updated successfully!');
        } else {
            $err = $updateStmt->error;
            $updateStmt->close();
            sendJson(false, 'Database update error: ' . $err, [], 500);
        }
    }

    // C. Action: DELETE student
    if ($action === 'delete') {
        $studentId = (int)($inputData['student_id'] ?? 0);

        if ($studentId <= 0) {
            sendJson(false, 'Valid Student ID is required for deletion.', [], 400);
        }

        // First remove any registrations associated with this student
        $delRegStmt = $conn->prepare("DELETE FROM registrations WHERE student_id = ?");
        $delRegStmt->bind_param("i", $studentId);
        $delRegStmt->execute();
        $delRegStmt->close();

        // Delete student record
        $delStmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $delStmt->bind_param("i", $studentId);

        if ($delStmt->execute()) {
            if ($delStmt->affected_rows > 0) {
                $delStmt->close();
                sendJson(true, 'Student record deleted successfully.');
            } else {
                $delStmt->close();
                sendJson(false, 'Student record not found.', [], 404);
            }
        } else {
            $err = $delStmt->error;
            $delStmt->close();
            sendJson(false, 'Database deletion error: ' . $err, [], 500);
        }
    }

    sendJson(false, 'Invalid action specified.', [], 400);
}

// Method not allowed
sendJson(false, 'HTTP method not supported.', [], 405);
