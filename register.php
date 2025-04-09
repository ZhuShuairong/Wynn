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
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $gender = $_POST["gender"];
    $identity_number = $_POST["identity_number"];
    $phone_number = $_POST["phone_number"];

    // Validate if passwords match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generate a unique User_ID (format: U + 10 digits)
        $user_id = "U" . str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);

        // Generate login name
        $user_login_name = strtolower($first_name . $last_name);

        // Generate real name
        $real_name = $first_name . " " . $last_name;

        // Prepare SQL query to insert data
        $sql = "INSERT INTO user_file (User_ID, User_Login_Name, Password, Real_Name, Gender, Identity_Number, Email, Add_Time, Phone_Number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        // Prepare the statement
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bind_param("ssssssss", $user_id, $user_login_name, $hashed_password, $real_name, $gender, $identity_number, $email, $phone_number);

        // Execute the SQL statement
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION["registered"] = true; // Set session variable
            $success_message = "Registration successful! Redirecting to login page...";
            header("Location: login.html"); // Redirect to login page
            exit;
        } else {
            // Registration failed
            $error_message = "Registration failed, please try again!";
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the database connection
$conn->close();
?>