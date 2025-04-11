<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// DB connection
$host = "localhost";
$username = "root";
$password = ""; // Replace with your actual DB password if needed
$database = "wynn_fyp";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    // More user-friendly error for production, log the details
    error_log("Database connection failed: " . $conn->connect_error);
    die("❌ Could not connect to the service. Please try again later.");
}

$error_message = null;
$success_message = null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form input using null coalescing operator
    $first_name = trim($_POST["first_name"] ?? '');
    $last_name = trim($_POST["last_name"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? ''; // Don't trim password initially
    $confirm_password = $_POST["confirm_password"] ?? '';
    $gender = $_POST["gender"] ?? '';

    // --- Input Validation ---

    // 1. Check for empty required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($gender)) {
        $error_message = "All fields are required!";
    }
    // 2. Validate Email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $error_message = "Invalid email format.";
    }
    // 3. Validate Password Length
    elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    }
    // 4. Validate Password Complexity (at least one uppercase)
    elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = "Password must contain at least one uppercase letter.";
    }
    // 5. Check if Passwords Match
    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    }
    // --- End Validation ---
    else {
        // All basic validation passed, proceed to database interaction

        // Hash the password *after* validation
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Create username and real name
        $user_login_name = strtolower(preg_replace('/[^a-z0-9]/i', '', $first_name . $last_name)); // Basic username generation
        $real_name = $first_name . " " . $last_name;

        // Check if email or generated username already exists (Optional but recommended)
        $sql_check = "SELECT User_ID FROM user_file WHERE Email = ? OR User_Login_Name = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
             $stmt_check->bind_param("ss", $email, $user_login_name);
             $stmt_check->execute();
             $stmt_check->store_result();
             if ($stmt_check->num_rows > 0) {
                 $error_message = "❌ Email or a similar username already exists.";
             }
             $stmt_check->close();
        } else {
             $error_message = "❌ Error checking existing user: " . $conn->error;
        }


        // Proceed only if no error message so far (including uniqueness check)
        if ($error_message === null) {
            // Prepare SQL INSERT statement (Removed Identity_Number, Phone_Number)
            $sql = "INSERT INTO user_file (User_Login_Name, Password, Real_Name, Gender, Email, Add_Time)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind parameters (Update types string from "sssssss" to "sssss")
                $stmt->bind_param("sssss", $user_login_name, $hashed_password, $real_name, $gender, $email);

                if ($stmt->execute()) {
                    // Set session variable or handle success as needed
                    // $_SESSION["registered_user"] = $user_login_name; // Example
                    $success_message = "✅ Registration successful! You can now login.";
                    // Optionally clear form fields or redirect
                    // header("Location: login.php");
                    // exit();
                } else {
                    // Log detailed error for admin, show generic message to user
                    error_log("Registration failed for email $email: " . $stmt->error);
                    $error_message = "❌ Registration failed. Please try again later.";
                    // Check for specific errors like duplicate entry if uniqueness check wasn't done/failed
                     if ($conn->errno == 1062) { // Error code for duplicate entry
                         $error_message = "❌ This email or username is already registered.";
                     }
                }
                $stmt->close();
            } else {
                // Log detailed error for admin
                error_log("SQL prepare error for registration: " . $conn->error);
                $error_message = "❌ An error occurred during registration preparation. Please try again.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FinSight – Register</title>
  <link rel="stylesheet" href="static/css/main.css"> <!-- Make sure this path is correct -->
  <style>
    /* Basic styles - same as provided */
    body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
    header { background-color: #0A74DA; color: white; padding: 1rem; text-align: center; }
    nav { display: flex; justify-content: center; gap: 1.5rem; margin-top: 0.5rem; }
    nav a { color: white; text-decoration: none; font-weight: bold; }
    main { display: flex; justify-content: center; align-items: center; padding: 2rem 1rem; /* Add padding */ min-height: calc(100vh - 150px); /* Adjust height considering header/footer */ }
    .register-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; }
    h2 { color: #0A74DA; margin-bottom: 1.5rem; text-align: center; }
    .form-group { margin-bottom: 1rem; }
    label { font-weight: bold; display: block; margin-bottom: 0.3rem; }
    input, select { width: 100%; padding: 0.6rem; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; /* Include padding in width */ }
    /* Style for placeholders */
    ::placeholder { color: #aaa; opacity: 1; /* Firefox */ }
    :-ms-input-placeholder { color: #aaa; } /* Internet Explorer 10-11 */
    ::-ms-input-placeholder { color: #aaa; } /* Microsoft Edge */

    button { width: 100%; background-color: #0A74DA; color: white; padding: 0.7rem; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem; }
    button:hover { background-color: #084a9a; }
    .message { margin-bottom: 1rem; /* Display message above form fields */ padding: 0.7rem; border-radius: 5px; text-align: center; }
    .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
    .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
    footer { background-color: #f0f0f0; text-align: center; padding: 1rem; font-size: 0.9rem; margin-top: 2rem; /* Ensure spacing */ }
  </style>
</head>
<body>
  <header>
    <h1>Create your FinSight Account</h1>
    <nav>
      <a href="index.html">Home</a> <!-- Adjust link if needed -->
      <a href="login.php">Login</a>   <!-- Adjust link if needed -->
    </nav>
  </header>

  <main>
    <div class="register-container">
      <h2>Register</h2>

      <?php if ($error_message): ?>
        <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <!-- Optionally hide the form after success -->
        <?php /* unset($_POST); // Clear POST data to prevent resubmission issues */ ?>
      <?php else: // Only show the form if there's no success message ?>

      <form method="POST" action="register.php" novalidate> <!-- novalidate disables browser validation to rely on server-side -->
        <div class="form-group">
          <label for="first-name">First Name:</label>
          <input type="text" id="first-name" name="first_name" placeholder="e.g., John" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="last-name">Last Name:</label>
          <input type="text" id="last-name" name="last_name" placeholder="e.g., Doe" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" name="password" placeholder="Min. 8 characters, 1 uppercase" required>
          <!-- Add password requirements hint -->
          <small style="display: block; margin-top: 4px; color: #666;">Must be at least 8 characters long and include one uppercase letter.</small>
        </div>

        <div class="form-group">
          <label for="confirm-password">Confirm Password:</label>
          <input type="password" id="confirm-password" name="confirm_password" placeholder="Re-type your password" required>
        </div>

        <div class="form-group">
          <label for="gender">Gender:</label>
          <select id="gender" name="gender" required>
            <option value="" <?php echo empty($_POST['gender']) ? 'selected' : ''; ?>>-- Select Gender --</option>
            <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
             <option value="Prefer not to say" <?php echo (($_POST['gender'] ?? '') === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option> <!-- Added option -->
          </select>
        </div>

        <!-- Identity Number field REMOVED -->
        <!-- Phone Number field REMOVED -->

        <button type="submit">Register</button>
      </form>
      <?php endif; // End of the 'else' block for showing the form ?>

    </div>
  </main>

  <footer>
    <p>© <?php echo date("Y"); ?> FinSight. All rights reserved.</p> <!-- Dynamic year -->
  </footer>

</body>
</html>