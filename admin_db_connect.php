<?php
// Database configuration (Update with your credentials)
$servername = "localhost"; // Often "localhost"
$username = "root";     // Your database username
$password = "";          // Your database password
$dbname = "wynn_fyp"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // In a real app, log this error and show a user-friendly message
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 (good practice)
$conn->set_charset("utf8mb4");

// Note: No need to close connection here; it will be closed implicitly
// when the script finishes, or explicitly in scripts that use it.
?>