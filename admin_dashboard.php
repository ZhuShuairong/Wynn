<?php
$pageTitle = "Dashboard";
require_once 'admin_header.php'; // Includes session check, the $adminName variable, and header HTML
require_once 'admin_db_connect.php';

// Fetch basic stats
$userCount = 0;
$adminCount = 0;
$reportCount = 0;

$userResult = $conn->query("SELECT COUNT(*) as count FROM user_file");
if ($userResult) {
    $userCount = $userResult->fetch_assoc()['count'];
}

$adminResult = $conn->query("SELECT COUNT(*) as count FROM admin_file");
if ($adminResult) {
    $adminCount = $adminResult->fetch_assoc()['count'];
}

$reportResult = $conn->query("SELECT COUNT(*) as count FROM report_file");
if ($reportResult) {
    $reportCount = $reportResult->fetch_assoc()['count'];
}

$conn->close();
?>

<!-- BEGIN Improved Layout -->
<div style="padding: 1rem 2rem;">

  <!-- Welcome Message -->
  <h1 style="color: #0A74DA; font-size: 2rem; margin-bottom: 0.5rem;">
    Welcome admin <?php echo $adminName; ?>!
  </h1>
  <p style="font-size: 1.1rem; color: #666; margin-bottom: 2rem;">
    This is the administration dashboard for FinSight.
  </p>

  <!-- System Overview Section -->
  <h2 style="color: #333; font-size: 1.4rem; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">
    System Overview
  </h2>
  <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
    <!-- Registered Users Card -->
    <div style="flex: 1; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); min-width: 200px; text-align: center;">
      <h3 style="color: #0A74DA; font-size: 2rem; margin-bottom: 0.5rem;">
        <?php echo $userCount; ?>
      </h3>
      <p style="margin: 0; color: #444;">Registered Users</p>
    </div>
    
    <!-- Admin Accounts Card -->
    <div style="flex: 1; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); min-width: 200px; text-align: center;">
      <h3 style="color: #0A74DA; font-size: 2rem; margin-bottom: 0.5rem;">
        <?php echo $adminCount; ?>
      </h3>
      <p style="margin: 0; color: #444;">Admin Accounts</p>
    </div>
    
    <!-- Generated Reports Card -->
    <div style="flex: 1; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); min-width: 200px; text-align: center;">
      <h3 style="color: #0A74DA; font-size: 2rem; margin-bottom: 0.5rem;">
        <?php echo $reportCount; ?>
      </h3>
      <p style="margin: 0; color: #444;">Generated Reports</p>
    </div>
  </div>

  <!-- Quick Actions Section -->
  <h2 style="color: #333; font-size: 1.4rem; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">
    Quick Actions
  </h2>
  <ul style="list-style-type: none; padding-left: 0; margin: 0; font-size: 1rem;">
    <li style="margin: 0.5rem 0;">
      <a href="admin_manage_users.php" style="text-decoration: none; color: #0A74DA;">Manage Users</a>
    </li>
    <li style="margin: 0.5rem 0;">
      <a href="add_admin.php" style="text-decoration: none; color: #0A74DA;">Add New Admin</a>
    </li>
    <li style="margin: 0.5rem 0;">
      <a href="admin_manage_reports.php" style="text-decoration: none; color: #0A74DA;">View Reports</a>
    </li>
  </ul>

</div>
<!-- END Improved Layout -->

<?php
require_once 'admin_footer.php'; // This file should end your HTML structure (e.g., closing </main>, </body>, </html>)
?>
