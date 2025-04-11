<?php
session_start();

// Database configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "wynn_fyp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$email = $_POST['email'];
$password = $_POST['password'];

// Validate credentials
$sql = "SELECT User_ID, Password FROM user_file WHERE Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['Password'])) {
        // Authentication successful
        $userId = $user['User_ID'];
        $_SESSION['user_id'] = $userId; // Use 'User_ID' instead of 'id'
        setcookie("user_id", $userId, time() + (86400 * 30), "/"); // Set cookie

        // Check if user has preferences in the database
        $checkPreferencesSql = "SELECT User_ID FROM user_preferred_dashboard_file WHERE User_ID = ?";
        $checkStmt = $conn->prepare($checkPreferencesSql);
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $preferencesResult = $checkStmt->get_result();

        if ($preferencesResult->num_rows === 0) {
            // No preferences found, insert default preferences
            $defaultPreferences = "linecharts,barcharts"; // Default preferred page types
            $insertPreferencesSql = "INSERT INTO user_preferred_dashboard_file (User_ID, Preferred_Page_Types, Add_Time) VALUES (?, ?, NOW())";
            $insertStmt = $conn->prepare($insertPreferencesSql);
            $insertStmt->bind_param("is", $userId, $defaultPreferences);
            $insertStmt->execute();
        }

        // Redirect to dashboard
        header("Location: dashboard.html?user_id=" . urlencode($userId));
        exit;
    } else {
        // Invalid password
        echo json_encode(['error' => 'Invalid email or password']);
    }
} else {
    // User not found
    echo json_encode(['error' => 'Invalid email or password']);
}

$conn->close();
?>