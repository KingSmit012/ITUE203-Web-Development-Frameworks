<?php
require_once __DIR__ . '/auth.php';

// If user is already logged in, redirect them based on their role
if (is_logged_in()) {
    if (($_SESSION['role'] ?? 'student') === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$loginError = "";
$statusMessage = "";

// Check for status messages in URL query string
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $loginError = "Please log in to access your dashboard.";
    } elseif ($_GET['error'] === 'timeout') {
        $loginError = "Your session has expired due to inactivity. Please log in again.";
    } elseif ($_GET['error'] === 'access_denied') {
        $loginError = "Access denied: You do not have permission to view that section.";
    }
} elseif (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $statusMessage = "You have been successfully logged out.";
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginIdentifier = trim($_POST["loginIdentifier"] ?? "");
    $password        = $_POST["loginPassword"] ?? "";
    $rememberMe      = isset($_POST["rememberMe"]);

    if (empty($loginIdentifier) || empty($password)) {
        $loginError = "Please enter both username/email and password.";
    } else {
        // Query user by username OR email
        $stmt = $conn->prepare("
            SELECT user_id, username, email, password_hash, role, first_name, last_name 
            FROM users 
            WHERE username = ? OR email = ? 
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("ss", $loginIdentifier, $loginIdentifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // Verify password against stored bcrypt hash using password_verify()
                if (password_verify($password, $user["password_hash"])) {
                    
                    // 1. Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    // 2. Set authenticated session variables
                    $_SESSION["user_id"]       = $user["user_id"];
                    $_SESSION["username"]      = $user["username"];
                    $_SESSION["email"]         = $user["email"];
                    $_SESSION["role"]          = $user["role"] ?? "student";
                    $_SESSION["name"]          = trim(($user["first_name"] ?? "") . " " . ($user["last_name"] ?? ""));
                    $_SESSION["last_activity"] = time();

                    // 3. Handle optional secure Remember-Me token
                    if ($rememberMe) {
                        // Generate a cryptographically secure 32-byte token
                        $plainToken = bin2hex(random_bytes(32));
                        $tokenHash  = hash('sha256', $plainToken);

                        // Save the hashed token in database
                        $updateStmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("si", $tokenHash, $user["user_id"]);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }

                        // Store cookie: "userId:token" for 30 days (HttpOnly & SameSite)
                        $cookieValue = $user["user_id"] . ":" . $plainToken;
                        setcookie("remember_me", $cookieValue, [
                            "expires"  => time() + (86400 * 30),
                            "path"     => "/",
                            "httponly" => true,
                            "samesite" => "Lax"
                        ]);
                    }

                    $stmt->close();

                    // 4. Role-based redirection
                    if (($user["role"] ?? "student") === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;

                } else {
                    $loginError = "Invalid username/email or password.";
                }
            } else {
                $loginError = "Invalid username/email or password.";
            }

            $stmt->close();
        } else {
            $loginError = "A database error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 13.5px;
            line-height: 1.4;
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
        .remember-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0 18px 0;
            font-size: 13.5px;
            color: var(--text-secondary);
        }
        .remember-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .remember-group label {
            cursor: pointer;
            margin: 0;
            font-weight: 400;
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
            <a href="signup.html" class="btn">Sign Up</a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="simple-container">
        <div class="card">
            <h2 class="mb-10">Sign In to Student Portal</h2>
            <p>Enter your username or email address and password.</p>

            <?php if (!empty($loginError)): ?>
                <div class="alert-box alert-error" role="alert">
                    <?= htmlspecialchars($loginError, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($statusMessage)): ?>
                <div class="alert-box alert-success" role="alert">
                    <?= htmlspecialchars($statusMessage, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form id="login-form" action="login.php" method="POST">
                <div class="form-group">
                    <label for="login-identifier">Username or Email ID</label>
                    <input 
                        type="text" 
                        id="login-identifier" 
                        name="loginIdentifier" 
                        placeholder="e.g. smit_26 or smit@charusat.edu.in"
                        value="<?= htmlspecialchars($_POST['loginIdentifier'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input 
                        type="password" 
                        id="login-password" 
                        name="loginPassword" 
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <!-- Remember Me Option -->
                <div class="remember-group">
                    <input type="checkbox" id="remember-me" name="rememberMe" <?= isset($_POST['rememberMe']) ? 'checked' : '' ?>>
                    <label for="remember-me">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>

            <p style="margin-top: 18px; font-size: 13px;">
                Don't have an account? <a href="signup.html">Sign Up here</a>
            </p>
        </div>
    </main>

    <script src="js/theme.js"></script>
</body>
</html>
