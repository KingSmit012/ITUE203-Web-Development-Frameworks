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

/**
 * Render dynamic sidebar menu based on user role (Admin vs Student)
 */
function render_sidebar($activeItem = '') {
    $role = $_SESSION['role'] ?? 'student';
    $title = ($role === 'admin') ? 'Admin Portal' : 'Student Portal';
    $subtitle = ($role === 'admin') ? 'System Management' : 'Academic Year 2026-27';

    echo '<aside class="sidebar">' . "\n";
    echo '  <div class="sidebar-header">' . "\n";
    echo '    <h2>' . htmlspecialchars($title) . '</h2>' . "\n";
    echo '    <p>' . htmlspecialchars($subtitle) . '</p>' . "\n";
    echo '  </div>' . "\n";
    echo '  <ul class="sidebar-menu">' . "\n";

    if ($role === 'admin') {
        // Dynamic Menu for Admin Role
        $menu = [
            'admin_dashboard' => ['url' => 'admin_dashboard.php', 'label' => 'Dashboard Overview'],
            'students_manage' => ['url' => 'students_manage.php', 'label' => 'Student Management'],
            'events_manage'   => ['url' => 'events_manage.php',   'label' => 'Event Management'],
            'dashboard'       => ['url' => 'dashboard.php',       'label' => 'Student Portal View'],
            'logout'          => ['url' => 'logout.php',          'label' => 'Sign Out']
        ];
    } else {
        // Dynamic Menu for Student Role
        $menu = [
            'dashboard'   => ['url' => 'dashboard.php',   'label' => 'Dashboard'],
            'events'      => ['url' => 'events.php',      'label' => 'Campus Events'],
            'profile'     => ['url' => 'profile.php',     'label' => 'Profile'],
            'attendance'  => ['url' => 'attendance.html', 'label' => 'Attendance'],
            'courses'     => ['url' => 'courses.html',    'label' => 'Courses'],
            'assignments' => ['url' => 'assignments.html','label' => 'Assignments'],
            'exams'       => ['url' => 'exams.html',      'label' => 'Exams'],
            'results'     => ['url' => 'results.html',    'label' => 'Results'],
            'timetable'   => ['url' => 'timetable.html',  'label' => 'Timetable'],
            'fees'        => ['url' => 'fees.html',       'label' => 'Fees'],
            'settings'    => ['url' => 'settings.html',   'label' => 'Settings'],
            'logout'      => ['url' => 'logout.php',      'label' => 'Sign Out']
        ];
    }

    foreach ($menu as $key => $item) {
        $activeClass = ($key === $activeItem) ? ' class="active"' : '';
        echo '    <li><a href="' . htmlspecialchars($item['url']) . '"' . $activeClass . '>' . htmlspecialchars($item['label']) . '</a></li>' . "\n";
    }

    echo '  </ul>' . "\n";
    echo '</aside>' . "\n";
}

/**
 * Render dynamic top navbar with theme toggle and user badge
 */
function render_top_navbar($pageTitle = 'Dashboard') {
    $user = current_user();
    $role = $user['role'] ?? 'student';
    $roleBadgeClass = ($role === 'admin') ? 'role-badge-admin' : 'role-badge-student';
    ?>
    <header class="top-navbar">
        <span class="navbar-title"><?= htmlspecialchars($pageTitle) ?></span>
        <div class="navbar-right">
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
            <span class="navbar-user">
                <?= htmlspecialchars($user['name'] ?: $user['username']) ?>
                <span class="role-badge <?= $roleBadgeClass ?>"><?= strtoupper(htmlspecialchars($role)) ?></span>
            </span>
            <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 11.5px;">Sign Out</a>
        </div>
    </header>
    <?php
}

