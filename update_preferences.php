<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wynn_fyp";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];
$display_types = json_encode($data['display_types']);
$metrics = json_encode($data['metrics']);

// Check if a record exists for the user
$sql_check = "SELECT * FROM user_preferred_dashboard_file WHERE User_ID = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $user_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // Update existing record
    $sql_update = "UPDATE user_preferred_dashboard_file 
                   SET Preferred_Page_Types = ?, Add_Time = NOW() 
                   WHERE User_ID = ?";
    $stmt_update = $conn->prepare($sql_update);
    $preferred_page_types = json_encode(['display_types' => $data['display_types'], 'metrics' => $data['metrics']]);
    $stmt_update->bind_param("ss", $preferred_page_types, $user_id);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update preferences.']);
    }
} else {
    // Insert new record
    $sql_insert = "INSERT INTO user_preferred_dashboard_file (User_ID, Preferred_Page_Types, Add_Time) 
                   VALUES (?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $preferred_page_types = json_encode(['display_types' => $data['display_types'], 'metrics' => $data['metrics']]);
    $stmt_insert->bind_param("ss", $user_id, $preferred_page_types);
    $stmt_insert->execute();

    if ($stmt_insert->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create preferences.']);
    }
}

// Close connections
$stmt_check->close();
if (isset($stmt_update)) $stmt_update->close();
if (isset($stmt_insert)) $stmt_insert->close();
$conn->close();
?>