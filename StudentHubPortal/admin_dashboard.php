<?php
require_once __DIR__ . '/auth.php';

// Restrict access: Only users with role = 'admin' are permitted
require_login('admin');

$currentUser = current_user();

// Fetch admin statistics from MySQL
$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;

if ($uRes = $conn->query("SELECT COUNT(*) FROM users")) {
    $totalUsers = $uRes->fetch_row()[0];
}
if ($sRes = $conn->query("SELECT COUNT(*) FROM students")) {
    $totalStudents = $sRes->fetch_row()[0];
}
if ($eRes = $conn->query("SELECT COUNT(*) FROM events")) {
    $totalEvents = $eRes->fetch_row()[0];
}
if ($rRes = $conn->query("SELECT COUNT(*) FROM registrations")) {
    $totalRegistrations = $rRes->fetch_row()[0];
}

// Fetch recently registered users
$recentUsers = [];
$usersQuery = "SELECT user_id, username, email, role, first_name, last_name, department, created_at FROM users ORDER BY user_id DESC LIMIT 10";
if ($res = $conn->query($usersQuery)) {
    while ($row = $res->fetch_assoc()) {
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
        .admin-badge {
            display: inline-block;
            background: #dc2626;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            margin-left: 6px;
            vertical-align: middle;
        }
        .student-badge {
            display: inline-block;
            background: #2563eb;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Portal</h2>
            <p>System Management</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active">Overview</a></li>
            <li><a href="dashboard.php">Student View</a></li>
            <li><a href="logout.php">Sign Out</a></li>
        </ul>
    </aside>

    <!-- Top Navbar -->
    <header class="top-navbar">
        <span class="navbar-title">Admin Management</span>
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
                <?= htmlspecialchars($currentUser['name'] ?: $currentUser['username'], ENT_QUOTES, 'UTF-8') ?>
                <span class="admin-badge">ADMIN</span>
            </span>
            <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 11.5px;">Sign Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?></strong>. Role-based control active.</p>
        </div>

        <!-- Metric Cards -->
        <div class="grid-4 dashboard-cards">
            <div class="card">
                <h3><?= (int)$totalUsers ?></h3>
                <p>Total Registered Users</p>
            </div>
            <div class="card">
                <h3><?= (int)$totalStudents ?></h3>
                <p>Students Directory</p>
            </div>
            <div class="card">
                <h3><?= (int)$totalEvents ?></h3>
                <p>Active Events</p>
            </div>
            <div class="card">
                <h3><?= (int)$totalRegistrations ?></h3>
                <p>Event Signups</p>
            </div>
        </div>

        <!-- Registered Users Table -->
        <div class="card" style="margin-top: 24px;">
            <h3>Recently Registered Accounts</h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Stored securely in MySQL <code>users</code> table.</p>

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
                                <td colspan="7" style="text-align: center;">No registered users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>#<?= (int)$u['user_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if (($u['role'] ?? '') === 'admin'): ?>
                                            <span class="admin-badge">ADMIN</span>
                                        <?php else: ?>
                                            <span class="student-badge">STUDENT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['department'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="js/theme.js"></script>
</body>
</html>
