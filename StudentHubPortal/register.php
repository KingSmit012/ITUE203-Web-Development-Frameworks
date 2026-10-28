<?php
session_start();

// Include database connection
require_once __DIR__ . '/config/db.php';

// Process only submitted POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: signup.html");
    exit;
}

// 1. Read and sanitize text inputs
$firstName    = trim($_POST["firstName"] ?? "");
$lastName     = trim($_POST["lastName"] ?? "");
$username     = trim($_POST["username"] ?? "");
$email        = trim($_POST["email"] ?? "");
$gender       = trim($_POST["gender"] ?? "");
$department   = trim($_POST["department"] ?? "");
$mobile       = trim($_POST["mobile"] ?? "");
$institute    = trim($_POST["institute"] ?? "");
$academicYear = trim($_POST["academicYear"] ?? "");
$password     = $_POST["password"] ?? "";

// 2. Validate inputs on the backend
$errors = [];

// Name validation (letters and spaces only)
if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $firstName)) {
    $errors[] = "First name must contain letters only (max 50 characters).";
}

if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $lastName)) {
    $errors[] = "Last name must contain letters only (max 50 characters).";
}

// Username validation (alphanumeric and underscore, 3-30 chars)
if (!preg_match("/^[a-zA-Z0-9_]{3,30}$/", $username)) {
    $errors[] = "Username must be 3-30 characters and contain only letters, numbers, or underscores.";
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

// Gender validation
$allowedGenders = ["Male", "Female", "Other"];
if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = "Please select a valid gender option.";
}

// Department validation
$allowedDepartments = [
    "Computer Engineering",
    "Information Technology",
    "Computer Science",
    "Mechanical Engineering",
    "Civil Engineering",
    "Electrical Engineering"
];
if (!in_array($department, $allowedDepartments, true)) {
    $errors[] = "Please select a valid department.";
}

// Mobile validation (exactly 10 digits)
if (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain exactly 10 digits.";
}

// Institute validation
$allowedInstitutes = [
    "CSPIT", "DEPSTAR", "RPCP", "CMPICA", "I2IM", "PDPIAS"
];
if (!in_array($institute, $allowedInstitutes, true)) {
    $errors[] = "Please select a valid institute.";
}

// Academic Year validation
$allowedYears = [
    "2026-27", "2025-26", "2024-25", "2023-24"
];
if (!in_array($academicYear, $allowedYears, true)) {
    $errors[] = "Please select a valid academic year.";
}

// Password validation (min 6 characters)
if (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters long.";
}

$success = false;
$message = "";

// 3. Check duplicate username or email and insert into MySQL
if (empty($errors)) {
    try {
        // Check for existing duplicate username or email using a prepared statement
        $checkStmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row["username"], $username) === 0) {
                $errors[] = "Username '{$username}' is already taken. Please choose another username.";
            }
            if (strcasecmp($row["email"], $email) === 0) {
                $errors[] = "Email address '{$email}' is already registered. Please log in or use another email.";
            }
        }
        $checkStmt->close();

        // If no duplicates found, hash password and insert
        if (empty($errors)) {
            // Hash the password securely using password_hash()
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into 'users' table using prepared statement
            $insertStmt = $conn->prepare("
                INSERT INTO users (
                    username, email, password_hash, first_name, last_name,
                    gender, department, mobile, institute, academic_year
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                "ssssssssss",
                $username,
                $email,
                $passwordHash,
                $firstName,
                $lastName,
                $gender,
                $department,
                $mobile,
                $institute,
                $academicYear
            );

            if ($insertStmt->execute()) {
                $success = true;
                $message = "Registration successful! Your account has been created.";

                // Also keep 'students' table synchronized so student_id is available for event registrations
                $studentStmt = $conn->prepare("
                    INSERT IGNORE INTO students (first_name, last_name, email, mobile, department, institute)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if ($studentStmt) {
                    $studentStmt->bind_param("ssssss", $firstName, $lastName, $email, $mobile, $department, $institute);
                    $studentStmt->execute();
                    $studentStmt->close();
                }
            } else {
                $errors[] = "Failed to register user. Error: " . $conn->error;
            }

            $insertStmt->close();
        }
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $errors[] = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Result - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .result-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .result-success {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        [data-theme="dark"] .result-success {
            background-color: rgba(6, 95, 70, 0.25);
            border-color: #059669;
            color: #6ee7b7;
        }
        .result-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        [data-theme="dark"] .result-error {
            background-color: rgba(153, 27, 27, 0.25);
            border-color: #dc2626;
            color: #fca5a5;
        }
        .result-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .result-box li {
            margin-bottom: 6px;
        }
        .summary-card {
            margin-top: 15px;
            padding: 12px;
            background: var(--bg-surface-elevated, #f9fafb);
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <!-- Simple Top Navigation -->
    <header class="simple-navbar">
        <h2>Student Portal</h2>
        <div class="simple-nav-right">
            <a href="home.html" style="font-size: 13px; margin-right: 10px;">Home</a>
            <button id="theme-toggle" class="theme-toggle-btn" type="button" aria-label="Toggle theme" title="Toggle theme">
                <span class="theme-toggle-track">
                    <span class="theme-toggle-thumb">
                        <svg class="theme-icon sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <svg class="theme-icon moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </span>
                </span>
                <span class="theme-toggle-text">Theme</span>
            </button>
            <a href="login.php" class="btn">Login</a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="simple-container">
        <div class="card">
            <h2>Registration Status</h2>

            <?php if ($success): ?>
                <div class="result-box result-success" role="alert">
                    <strong>&#10003; <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></strong>
                    <div class="summary-card">
                        <p><strong>Name:</strong> <?= htmlspecialchars($firstName . " " . $lastName, ENT_QUOTES, "UTF-8") ?></p>
                        <p><strong>Username:</strong> <?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?></p>
                        <p><strong>Department:</strong> <?= htmlspecialchars($department, ENT_QUOTES, "UTF-8") ?></p>
                        <p><strong>Institute:</strong> <?= htmlspecialchars($institute, ENT_QUOTES, "UTF-8") ?></p>
                    </div>
                </div>

                <div class="btn-group">
                    <a class="btn" href="login.php">Proceed to Login</a>
                    <a class="btn" href="home.html" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary);">Back to Home</a>
                </div>

            <?php else: ?>
                <div class="result-box result-error" role="alert">
                    <strong>Please correct the following issue(s):</strong>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, "UTF-8") ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="btn-group">
                    <a class="btn" href="javascript:history.back()">Go Back & Edit</a>
                    <a class="btn" href="signup.html" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary);">New Registration</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/theme.js"></script>
</body>
</html>
