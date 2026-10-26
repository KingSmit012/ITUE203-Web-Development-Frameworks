
<?php
session_start();

// Process only submitted POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: signup.html");
    exit;
}

// Read and sanitize text inputs.
$firstName = trim($_POST["firstName"] ?? "");
$lastName = trim($_POST["lastName"] ?? "");
$email = trim($_POST["email"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$department = trim($_POST["department"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$institute = trim($_POST["institute"] ?? "");
$academicYear = trim($_POST["academicYear"] ?? "");
$password = $_POST["password"] ?? "";

// Validate the submitted data on the server.
$errors = [];

if (!preg_match("/^[\p{L} ]+$/u", $firstName)) {
    $errors[] = "Enter a valid first name.";
}

if (!preg_match("/^[\p{L} ]+$/u", $lastName)) {
    $errors[] = "Enter a valid last name.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email address.";
}

if (!in_array($gender, ["Male", "Female", "Other"], true)) {
    $errors[] = "Select a valid gender.";
}

$allowedDepartments = [
    "Computer Engineering",
    "Information Technology",
    "Computer Science",
    "Mechanical Engineering",
    "Civil Engineering",
    "Electrical Engineering"
];

if (!in_array($department, $allowedDepartments, true)) {
    $errors[] = "Select a valid department.";
}

if (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain exactly 10 digits.";
}

$allowedInstitutes = [
    "CSPIT", "DEPSTAR", "RPCP", "CMPICA", "I2IM", "PDPIAS"
];

if (!in_array($institute, $allowedInstitutes, true)) {
    $errors[] = "Select a valid institute.";
}

$allowedYears = [
    "2026-27", "2025-26", "2024-25", "2023-24"
];

if (!in_array($academicYear, $allowedYears, true)) {
    $errors[] = "Select a valid academic year.";
}

if (strlen($password) < 6 || strlen($password) > 4096) {
    $errors[] = "Password must contain at least 6 characters.";
}

// Prepare the result message.
$message = "";
$success = false;

if (empty($errors)) {
    $file = __DIR__ . "/data/registrations.json";

    // Create the file if it does not exist.
    if (!file_exists($file)) {
        file_put_contents(
            $file,
            json_encode(["registrations" => []], JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    // Open the file safely for reading and writing.
    $handle = @fopen($file, "c+");

    if ($handle === false) {
        $errors[] = "Cannot open the registration file. Check folder permissions.";
    } elseif (!flock($handle, LOCK_EX)) {
        fclose($handle);
        $errors[] = "Could not lock the registration file. Please try again.";
    } else {
        $contents = stream_get_contents($handle);
        $data = json_decode($contents, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($data) ||
            !isset($data["registrations"]) ||
            !is_array($data["registrations"])
        ) {
            $errors[] = "The registration JSON file is invalid. Check its contents.";
        } else {
            // Prevent duplicate email registrations.
            foreach ($data["registrations"] as $registration) {
                if (
                    isset($registration["email"]) &&
                    strcasecmp($registration["email"], $email) === 0
                ) {
                    $errors[] = "This email address is already registered.";
                    break;
                }
            }

            if (empty($errors)) {
                // Never store a plain-text password.
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashedPassword === false) {
                    $errors[] = "Could not secure the password.";
                } else {
                    $data["registrations"][] = [
                        "id" => uniqid("student_", true),
                        "firstName" => $firstName,
                        "lastName" => $lastName,
                        "email" => $email,
                        "gender" => $gender,
                        "department" => $department,
                        "mobile" => $mobile,
                        "institute" => $institute,
                        "academicYear" => $academicYear,
                        "password" => $hashedPassword,
                        "registeredAt" => date("c")
                    ];

                    $json = json_encode(
                        $data,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    );

                    if ($json === false) {
                        $errors[] = "Could not encode registration data.";
                    } else {
                        rewind($handle);

                        if (!ftruncate($handle, 0)) {
                            $errors[] = "Could not update the registration file.";
                        } else {
                            $written = fwrite($handle, $json);

                            if ($written === false || $written !== strlen($json)) {
                                $errors[] = "Could not save the registration.";
                            } else {
                                fflush($handle);
                                $success = true;
                                $message = "Registration successful! You can now log in.";
                            }
                        }
                    }
                }
            }
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

if (!empty($errors)) {
    $message = implode(" ", $errors);
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
</head>
<body>
    <main class="simple-container">
        <div class="card">
            <h2>Registration Result</h2>

            <p role="status" aria-live="polite"
               style="color: <?= $success ? 'green' : 'red' ?>;">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>

            <?php if ($success): ?>
                <a class="btn" href="login.html">Go to Login</a>
            <?php else: ?>
                <a class="btn" href="signup.html">Back to Registration</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
