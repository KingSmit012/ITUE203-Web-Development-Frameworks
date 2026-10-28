<?php
require_once __DIR__ . '/auth.php';

// Restrict access: Only logged-in users can view the dashboard
require_login();

// If an admin accidentally navigates here, redirect them to admin dashboard
if (($_SESSION['role'] ?? 'student') === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .role-badge {
            display: inline-block;
            background: #2563eb;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            margin-left: 6px;
            vertical-align: middle;
        }
        .session-info {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Student Portal</h2>
            <p>Academic Year 2026-27</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="attendance.html">Attendance</a></li>
            <li><a href="courses.html">Courses</a></li>
            <li><a href="assignments.html">Assignments</a></li>
            <li><a href="exams.html">Exams</a></li>
            <li><a href="results.html">Results</a></li>
            <li><a href="timetable.html">Timetable</a></li>
            <li><a href="fees.html">Fees</a></li>
            <li><a href="settings.html">Settings</a></li>
        </ul>
    </aside>

    <!-- Top Navbar -->
    <header class="top-navbar">
        <span class="navbar-title">Dashboard</span>
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
                Welcome, <?= htmlspecialchars($currentUser['name'] ?: $currentUser['username'], ENT_QUOTES, 'UTF-8') ?>
                <span class="role-badge"><?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 11.5px;" title="Securely Sign Out">Sign Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <h1>Student Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($currentUser['name'] ?: $currentUser['username'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?>)</p>
            <p class="session-info">Active Session protected | Inactivity timeout: 30 minutes</p>
        </div>

        <div class="grid-4 dashboard-cards">
            <div class="card">
                <h3>99%</h3>
                <p>Attendance</p>
            </div>
            <div class="card">
                <h3>7</h3>
                <p>Active Courses</p>
            </div>
            <div class="card">
                <h3>1</h3>
                <p>Pending Assignments</p>
            </div>
            <div class="card">
                <h3>Sem 3</h3>
                <p>Current Semester</p>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-column">

            <!-- Announcements -->
            <div class="column">
                <div class="card">
                    <h3>Announcements</h3>
                    <ul class="announcements-list">
                        <li><b>15 Jul 2026:</b> Mid-term exams will start from 1st August.</li>
                        <li><b>12 Jul 2026:</b> Assignment 2 for FDSA has been extended to 20th July.</li>
                        <li><b>10 Jul 2026:</b> College will remain closed on 17th July for holiday.</li>
                        <li><b>05 Jul 2026:</b> Fee payment deadline is 31st July 2026.</li>
                    </ul>
                </div>
            </div>

            <!-- Today's Timetable -->
            <div class="column">
                <div class="card">
                    <h3>Today's Schedule</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Subject</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>09:10 - 10:10</td>
                                <td>FDSA</td>
                            </tr>
                            <tr>
                                <td>10:10 - 11:10</td>
                                <td>Discrete Mathematics</td>
                            </tr>
                            <tr>
                                <td>01:10 - 02:10</td>
                                <td>OOP</td>
                            </tr>
                            <tr>
                                <td>02:20 - 04:20</td>
                                <td>Laboratory Session</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

    <script src="js/theme.js"></script>
</body>
</html>
