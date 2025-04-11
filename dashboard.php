<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html"); // Redirect to login if not logged in
    exit;
}

$userId = $_SESSION["user_id"]; // Get User_ID from session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FinSight – Dashboard</title>
    <!-- Include styles and scripts -->
</head>
<body>
    <script>
        // Pass User_ID to JavaScript
        const userId = "<?php echo htmlspecialchars($userId); ?>";
        console.log("User ID from session:", userId);
    </script>
    <!-- Rest of the dashboard content -->
</body>
</html>