<?php
// admin_dashboard.php - Administrator Analytics & Overview
require_once __DIR__ . '/auth.php';

// Restrict access: Only administrators can view this dashboard
require_login('admin');

$currentUser = current_user();

// 1. Fetch live metrics from MySQL
$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;

if ($uRes = $conn->query("SELECT COUNT(*) FROM users")) {
    $totalUsers = (int)$uRes->fetch_row()[0];
}
if ($sRes = $conn->query("SELECT COUNT(*) FROM students")) {
    $totalStudents = (int)$sRes->fetch_row()[0];
}
if ($eRes = $conn->query("SELECT COUNT(*) FROM events")) {
    $totalEvents = (int)$eRes->fetch_row()[0];
}
if ($rRes = $conn->query("SELECT COUNT(*) FROM registrations")) {
    $totalRegistrations = (int)$rRes->fetch_row()[0];
}

// 2. Fetch Latest Event Registrations (Joined across registrations, students, and events)
$latestRegistrations = [];
$regSql = "
    SELECT r.registration_id, r.registration_date, r.status,
           s.first_name, s.last_name, s.email, s.department,
           e.title AS event_title, e.event_date
    FROM registrations r
    LEFT JOIN students s ON r.student_id = s.student_id
    LEFT JOIN events e ON r.event_id = e.event_id
    ORDER BY r.registration_id DESC
    LIMIT 8
";
if ($res = $conn->query($regSql)) {
    while ($row = $res->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }
}

// 3. Fetch Latest User Signups (From users table)
$recentUsers = [];
$usersSql = "SELECT user_id, username, email, role, first_name, last_name, department, created_at FROM users ORDER BY user_id DESC LIMIT 6";
if ($uRes = $conn->query($usersSql)) {
    while ($row = $uRes->fetch_assoc()) {
        $recentUsers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        .role-badge-admin {
            display: inline-block;
            background: #dc2626;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            margin-left: 6px;
        }
        .role-badge-student {
            display: inline-block;
            background: #2563eb;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 12px;
        }
        .quick-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

    <!-- Dynamic Sidebar Menu (shows Admin Options) -->
    <?php render_sidebar('admin_dashboard'); ?>

    <!-- Dynamic Top Navbar with Theme Toggle -->
    <?php render_top_navbar('Administrator Dashboard'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Admin Overview & Analytics</h1>
            <p>Welcome, <strong><?= htmlspecialchars($currentUser['name'] ?: $currentUser['username']) ?></strong>. Monitor users, students, events, and registrations.</p>
        </div>

        <!-- Quick Access Action Buttons -->
        <div class="quick-actions">
            <a href="students_manage.php" class="btn">Manage Students &rarr;</a>
            <a href="events_manage.php" class="btn" style="background: #059669;">Manage Events &rarr;</a>
            <a href="dashboard.php" class="btn" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-primary);">View Student Portal &rarr;</a>
        </div>

        <!-- 1. Statistics Cards -->
        <div class="grid-4 dashboard-cards">
            <div class="card">
                <h3><?= $totalUsers ?></h3>
                <p>User Accounts</p>
            </div>
            <div class="card">
                <h3><?= $totalStudents ?></h3>
                <p>Student Directory</p>
            </div>
            <div class="card">
                <h3><?= $totalEvents ?></h3>
                <p>Campus Events</p>
            </div>
            <div class="card">
                <h3><?= $totalRegistrations ?></h3>
                <p>Event Registrations</p>
            </div>
        </div>

        <!-- Two Column or Stacked Data Tables -->
        <div style="margin-top: 24px; display: grid; grid-template-columns: 1fr; gap: 24px;">

            <!-- 2. Latest Event Registrations -->
            <div class="card" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Latest Event Registrations</h3>
                    <span style="font-size: 12px; color: var(--text-muted);">Real-time signups from students</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Reg ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Event Title</th>
                                <th>Event Date</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latestRegistrations)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                        No event registrations recorded yet. Students can register for events on <a href="events.php">Campus Events</a> page.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latestRegistrations as $reg): ?>
                                    <tr>
                                        <td>#<?= (int)$reg['registration_id'] ?></td>
                                        <td><strong><?= htmlspecialchars(trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? '')) ?: 'Student') ?></strong></td>
                                        <td><?= htmlspecialchars($reg['email'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($reg['event_title'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($reg['event_date'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars(substr($reg['registration_date'] ?? '', 0, 16)) ?></td>
                                        <td><span class="status-badge"><?= htmlspecialchars($reg['status'] ?? 'Registered') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Latest Registered User Accounts -->
            <div class="card" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Recent User Accounts</h3>
                    <span style="font-size: 12px; color: var(--text-muted);">Stored in MySQL <code>users</code> table</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px;">No registered user accounts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $u): ?>
                                    <tr>
                                        <td>#<?= (int)$u['user_id'] ?></td>
                                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                        <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '—') ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <?php if (($u['role'] ?? '') === 'admin'): ?>
                                                <span class="role-badge-admin">ADMIN</span>
                                            <?php else: ?>
                                                <span class="role-badge-student">STUDENT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($u['department'] ?: '—') ?></td>
                                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="js/theme.js"></script>
</body>
</html>
