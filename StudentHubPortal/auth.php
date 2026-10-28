<?php
// auth.php - Session Management, Timeout & Access Control

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// Session Timeout duration in seconds (30 minutes)
define('SESSION_TIMEOUT_DURATION', 1800);

/**
 * 1. Check and handle session timeout
 */
function check_session_timeout() {
    if (isset($_SESSION['user_id'])) {
        // If last activity is set and older than timeout duration
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_DURATION)) {
            // Destroy session due to inactivity
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Redirect to login page with timeout notice
            header("Location: login.php?error=timeout");
            exit;
        }

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }
}

/**
 * 2. Handle Remember-Me Cookie (Auto Login)
 */
function check_remember_me($conn) {
    // Only check if user is not already logged in and remember-me cookie exists
    if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
        $cookieParts = explode(':', $_COOKIE['remember_me'], 2);
        if (count($cookieParts) === 2) {
            $userId = (int)$cookieParts[0];
            $plainToken = $cookieParts[1];

            // Look up user by ID
            $stmt = $conn->prepare("SELECT user_id, username, email, role, first_name, last_name, remember_token FROM users WHERE user_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    // Check if token matches stored SHA-256 hash
                    $expectedHash = hash('sha256', $plainToken);
                    if (!empty($user['remember_token']) && hash_equals($user['remember_token'], $expectedHash)) {
                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Populate session
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'] ?? 'student';
                        $_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                        $_SESSION['last_activity'] = time();
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Run Remember-Me check and Timeout check
check_remember_me($conn);
check_session_timeout();

/**
 * Helper: Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper: Restrict access to authenticated users (and optionally specific role)
 */
function require_login($required_role = null) {
    if (!is_logged_in()) {
        header("Location: login.php?error=unauthorized");
        exit;
    }

    // Role check if specified (e.g. 'admin')
    if ($required_role !== null && ($_SESSION['role'] ?? 'student') !== $required_role) {
        // If not authorized for this role, redirect to appropriate dashboard
        if (($_SESSION['role'] ?? 'student') === 'admin') {
            header("Location: admin_dashboard.php?error=access_denied");
        } else {
            header("Location: dashboard.php?error=access_denied");
        }
        exit;
    }
}

/**
 * Helper: Get current user info
 */
function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'student',
        'name'     => !empty($_SESSION['name']) ? $_SESSION['name'] : $_SESSION['username']
    ];
}
