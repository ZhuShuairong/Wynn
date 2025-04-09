<?php
header('Content-Type: application/json'); // Ensure correct content type

// --- Database Configuration ---
// !! IMPORTANT: Replace with your actual credentials !!
$username = "root";
$password = "";
$dbname = "wynn_fyp";
$socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock'; // Use socket from config

// --- Create Connection ---
// Use socket if provided, otherwise use servername/port
$conn = new mysqli(
    ($socket ? null : $servername), // servername is null if using socket
    $username,
    $password,
    $dbname,
    ($socket ? null : 3306), // port is null if using socket (or use default)
    $socket
);

// --- Check Connection ---
if ($conn->connect_error) {
    // Log error securely instead of dying with details if possible in production
    // error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}
// Set charset AFTER connection
if (!$conn->set_charset("utf8mb4")) {
     // error_log("Error loading character set utf8mb4: " . $conn->error);
     // Continue, but be aware of potential encoding issues
}


// --- Get and Validate Topic ---
$topic = isset($_GET['topic']) ? trim(urldecode($_GET['topic'])) : '';

if (empty($topic)) {
    echo json_encode([]); // Return empty array if no topic is provided
    $conn->close();
    exit;
}

// --- Prepare and Execute SQL Query ---
// Use prepared statements to prevent SQL injection vulnerabilities
$sql = "SELECT Title, Link, Created_Time
        FROM topics_file
        WHERE Content LIKE ?
        ORDER BY Created_Time DESC"; // Optional: Order by date

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Log error securely
    // error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    die(json_encode(['error' => 'Failed to prepare database query.']));
}

// Bind the topic parameter with wildcards for LIKE search
$searchTerm = "%" . $topic . "%";
$stmt->bind_param("s", $searchTerm); // "s" means the variable is a string

$stmt->execute();
$result = $stmt->get_result();

$specificTopicEntries = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $specificTopicEntries[] = [
            // Ensure correct casing if DB columns are different
            'Title' => $row['Title'],
            'Link' => $row['Link'], // Include the Link
            'Created_Time' => $row['Created_Time'] ? date("Y-m-d H:i", strtotime($row['Created_Time'])) : 'N/A' // Format date nicely
        ];
    }
    $stmt->close();
} else {
    // Log error if query execution failed
    // error_log("Query execution failed: (" . $stmt->errno . ") " . $stmt->error);
}


// --- Close Connection ---
$conn->close();

// --- Return Data as JSON ---
echo json_encode($specificTopicEntries);
?>