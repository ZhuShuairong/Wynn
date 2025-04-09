<?php
// Start session
session_start();

// Database connection information
$host = "localhost"; // Database host
$username = "root";  // Database username
$password = "";      // Database password
$database = "wynn_fyp"; // Database name

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if the form is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepare SQL query to fetch user data
    $sql = "SELECT User_ID, Password FROM user_file WHERE Email = ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param("s", $email);

    // Execute the SQL statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if the user exists
    if ($result->num_rows === 1) {
        // Fetch the user data
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['Password'])) {
            // Password is correct
            $_SESSION["user_id"] = $user['User_ID']; // Store User_ID in session
            $success_message = "Login successful! Redirecting to dashboard.";
            header("Location: dashboard.html?user_id=" . urlencode($user['User_ID'])); // Redirect to dashboard with User_ID
            exit;
        } else {
            // Password is incorrect
            $error_message = "Incorrect password.";
        }
    } else {
        // User does not exist
        $error_message = "User not found.";
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>