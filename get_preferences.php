<?php
session_start();

// Get user_id from query string
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID is missing']);
    exit;
}

// Database configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "wynn_fyp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Fetch preferences for the user
$sql = "SELECT Preferred_Page_Types FROM user_preferred_dashboard_file WHERE User_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $preferences = [
        'display_types' => explode(',', $row['Preferred_Page_Types']),
        'metrics' => [] // Add additional fields as needed
    ];
    echo json_encode(['success' => true, 'preferences' => $preferences]);
} else {
    echo json_encode(['success' => false, 'error' => 'Preferences not found']);
}

$conn->close();
?>