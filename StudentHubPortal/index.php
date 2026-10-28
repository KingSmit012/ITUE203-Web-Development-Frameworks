<?php
require_once __DIR__ . '/auth.php';

// Restrict dashboard access: Redirects to login.php if not authenticated
require_login();

// Role-based routing
if (($_SESSION['role'] ?? 'student') === 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: dashboard.php");
}
exit;
