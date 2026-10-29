<?php
require_once __DIR__ . '/auth.php';

// Restrict access: Only logged-in users can view the dashboard
require_login();

$isAdminPreview = (($_SESSION['role'] ?? 'student') === 'admin');
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

    <!-- Dynamic Sidebar Menu -->
    <?php render_sidebar('dashboard'); ?>

    <!-- Dynamic Top Navbar -->
    <?php render_top_navbar('Student Dashboard'); ?>

    <!-- Main Content -->
    <main class="main-content">

        <?php if ($isAdminPreview): ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; gap: 10px;">
                <span><strong>🛡️ Admin Preview Mode:</strong> You are viewing the Student Portal with Administrator privileges.</span>
                <a href="admin_dashboard.php" class="btn btn-sm" style="background: #2563eb; color: white; text-decoration: none;">&larr; Back to Admin Dashboard</a>
            </div>
        <?php endif; ?>

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
