<?php
// profile.php - Student Profile View and Update Module
require_once __DIR__ . '/auth.php';

// Restrict access: Only authenticated users can access profile
require_login();

$userId = (int)$_SESSION['user_id'];
$successMessage = "";
$errorMessage = "";

// 1. Handle Profile Update Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName    = trim($_POST["firstName"] ?? "");
    $lastName     = trim($_POST["lastName"] ?? "");
    $gender       = trim($_POST["gender"] ?? "");
    $mobile       = trim($_POST["mobile"] ?? "");
    $department   = trim($_POST["department"] ?? "");
    $institute    = trim($_POST["institute"] ?? "");
    $academicYear = trim($_POST["academicYear"] ?? "");
    $newPassword  = $_POST["newPassword"] ?? "";

    // Validation
    $errors = [];
    if (empty($firstName) || !preg_match("/^[a-zA-Z\s]{1,50}$/", $firstName)) {
        $errors[] = "Enter a valid first name (letters only).";
    }
    if (empty($lastName) || !preg_match("/^[a-zA-Z\s]{1,50}$/", $lastName)) {
        $errors[] = "Enter a valid last name (letters only).";
    }
    if (!empty($mobile) && !preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }
    if (!empty($newPassword) && strlen($newPassword) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }

    if (empty($errors)) {
        // Update users table in MySQL
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, gender = ?, mobile = ?, 
                    department = ?, institute = ?, academic_year = ?, password_hash = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssssssssi", $firstName, $lastName, $gender, $mobile, $department, $institute, $academicYear, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, gender = ?, mobile = ?, 
                    department = ?, institute = ?, academic_year = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("sssssssi", $firstName, $lastName, $gender, $mobile, $department, $institute, $academicYear, $userId);
        }

        if ($stmt->execute()) {
            $stmt->close();

            // Update session name
            $_SESSION['name'] = trim("$firstName $lastName");

            // Also synchronize with students table by user email
            $syncStmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ?, mobile = ?, department = ?, institute = ? WHERE email = ?");
            if ($syncStmt) {
                $userEmail = $_SESSION['email'] ?? '';
                $syncStmt->bind_param("ssssss", $firstName, $lastName, $mobile, $department, $institute, $userEmail);
                $syncStmt->execute();
                $syncStmt->close();
            }

            $successMessage = "Your profile has been updated successfully!";
        } else {
            $errorMessage = "Failed to update profile: " . $conn->error;
            $stmt->close();
        }
    } else {
        $errorMessage = implode(" ", $errors);
    }
}

// 2. Fetch fresh user details from MySQL
$stmt = $conn->prepare("
    SELECT user_id, username, email, role, first_name, last_name, 
           gender, department, mobile, institute, academic_year, created_at 
    FROM users 
    WHERE user_id = ? 
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User account not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }
        .profile-avatar-wrap {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 16px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary, #2563eb);
            margin-bottom: 10px;
        }
        .profile-badge {
            display: inline-block;
            background: #2563eb;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 13.5px;
            line-height: 1.4;
        }
        .alert-success {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        [data-theme="dark"] .alert-success {
            background-color: rgba(6, 95, 70, 0.25);
            border-color: #059669;
            color: #6ee7b7;
        }
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        [data-theme="dark"] .alert-error {
            background-color: rgba(153, 27, 27, 0.25);
            border-color: #dc2626;
            color: #fca5a5;
        }
        .view-table td:first-child {
            font-weight: 600;
            width: 130px;
            color: var(--text-heading);
            background-color: var(--bg-surface-elevated);
        }
    </style>
</head>
<body>

    <!-- Dynamic Sidebar Menu -->
    <?php render_sidebar('profile'); ?>

    <!-- Dynamic Top Navbar -->
    <?php render_top_navbar('My Profile'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
            <p>View and update your personal and academic information in real time.</p>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-box alert-success" role="alert">
                <strong>✓ <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-box alert-error" role="alert">
                <strong>✗ <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            
            <!-- Left Card: Current Profile Overview -->
            <div class="card">
                <div class="profile-avatar-wrap">
                    <img src="images/Profile.jpg" alt="Profile Avatar" class="profile-avatar" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($user['username']) ?>'">
                    <h3 style="margin: 0 0 4px 0;"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="margin: 0 0 8px 0; font-size: 13px; color: var(--text-muted);">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="profile-badge"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="table-responsive">
                    <table class="view-table">
                        <tr>
                            <td>Email Address</td>
                            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Gender</td>
                            <td><?= htmlspecialchars($user['gender'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Mobile</td>
                            <td><?= htmlspecialchars($user['mobile'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Department</td>
                            <td><?= htmlspecialchars($user['department'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Institute</td>
                            <td><?= htmlspecialchars($user['institute'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Academic Year</td>
                            <td><?= htmlspecialchars($user['academic_year'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td>Registered On</td>
                            <td><?= htmlspecialchars(substr($user['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Right Card: Edit Profile Form -->
            <div class="card">
                <h3 style="margin-top: 0; margin-bottom: 6px;">Update Profile Details</h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px;">Modify your details below and click Save Changes to update your MySQL record.</p>

                <form id="profile-update-form" action="profile.php" method="POST">
                    <div class="form-group">
                        <label for="first-name">First Name *</label>
                        <input type="text" id="first-name" name="firstName" value="<?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last-name">Last Name *</label>
                        <input type="text" id="last-name" name="lastName" value="<?= htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <div class="gender-options">
                            <label><input type="radio" name="gender" value="Male" <?= ($user['gender'] === 'Male') ? 'checked' : '' ?>> Male</label>
                            <label><input type="radio" name="gender" value="Female" <?= ($user['gender'] === 'Female') ? 'checked' : '' ?>> Female</label>
                            <label><input type="radio" name="gender" value="Other" <?= ($user['gender'] === 'Other') ? 'checked' : '' ?>> Other</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number (10 digits)</label>
                        <input type="text" id="mobile" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. 9876543210">
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">-- Select Department --</option>
                            <?php 
                            $depts = ["Computer Engineering", "Information Technology", "Computer Science", "Mechanical Engineering", "Civil Engineering", "Electrical Engineering"];
                            foreach ($depts as $d): ?>
                                <option value="<?= $d ?>" <?= ($user['department'] === $d) ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="institute">Institute</label>
                        <select id="institute" name="institute">
                            <option value="">-- Select Institute --</option>
                            <?php 
                            $insts = ["CSPIT", "DEPSTAR", "RPCP", "CMPICA", "I2IM", "PDPIAS"];
                            foreach ($insts as $i): ?>
                                <option value="<?= $i ?>" <?= ($user['institute'] === $i) ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="academic-year">Academic Year</label>
                        <select id="academic-year" name="academicYear">
                            <option value="">-- Select Academic Year --</option>
                            <?php 
                            $years = ["2026-27", "2025-26", "2024-25", "2023-24"];
                            foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= ($user['academic_year'] === $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="new-password">New Password (leave blank to keep current password)</label>
                        <input type="password" id="new-password" name="newPassword" placeholder="Minimum 6 characters">
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
                </form>
            </div>

        </div>
    </main>

    <script src="js/theme.js"></script>
</body>
</html>
