<?php
// Start session IMPORTANT: Must be called before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in. Redirect to login page if not.
// Allow access to login page itself without being logged in.
$currentPage = basename($_SERVER['PHP_SELF']); // Get the current script name
if (!isset($_SESSION['admin_id']) && $currentPage != 'admin_login.php') {
    header("Location: admin_login.php");
    exit();
}

$adminName = isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8') : 'Admin';

// Function to check if a nav link is active
function isActive($pageName) {
    return basename($_SERVER['PHP_SELF']) == $pageName ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinSight Admin - <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></title>
    <link rel="stylesheet" href="admin_style.css">
    <!-- Add any other CSS or JS libraries needed -->
</head>
<body>
    <div class="admin-container">
        <?php if (isset($_SESSION['admin_id'])): // Only show sidebar if logged in ?>
        <aside class="admin-sidebar">
            <h2>FinSight Admin</h2>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo isActive('admin_dashboard.php'); ?>">Dashboard</a></li>
                    <li><a href="admin_manage_users.php" class="<?php echo isActive('manage_users.php'); echo isActive('edit_user.php'); echo isActive('edit_user_preferences.php'); ?>">Manage Users</a></li>
                    <li><a href="admin_manage_admins.php" class="<?php echo isActive('manage_admins.php'); echo isActive('add_admin.php'); ?>">Manage Admins</a></li>
                    <li><a href="admin_manage_reports.php" class="<?php echo isActive('manage_reports.php'); echo isActive('edit_report.php'); ?>">Manage Reports</a></li>
                    <li><a href="admin_user_statistics.php" class="<?php echo isActive('user_statistics.php'); ?>">User Statistics</a></li>
                    <li class="logout-link"><a href="admin_logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        <?php endif; ?>
        <main class="admin-content">
        <!-- Page content starts here -->