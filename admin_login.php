<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
session_unset();
session_destroy();

// Start a fresh one (must be called AFTER destroy)
session_start();


require_once 'admin_db_connect.php'; // Include the database connection

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $login_name = trim($_POST['login_name']);
    $password = $_POST['password'];

    if (empty($login_name) || empty($password)) {
        $login_error = "Please enter both login name and password.";
    } else {
        // Prepare statement to prevent SQL injection
        $sql = "SELECT Admin_ID, Admin_Login_Name, Password FROM admin_file WHERE Admin_Login_Name = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $login_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Verify the password against the stored hash
                if (password_verify($password, $admin['Password'])) {
                    // Password is correct, start the session
                    session_regenerate_id(true); // Regenerate session ID for security
                    $_SESSION['admin_id'] = $admin['Admin_ID'];
                    $_SESSION['admin_name'] = $admin['Admin_Login_Name'];
                    $_SESSION['admin_login_name'] = $admin['Admin_Login_Name'];

                    // Redirect to the admin dashboard
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    // Invalid password
                    $login_error = "Invalid login name or password.";
                }
            } else {
                // No user found with that login name
                $login_error = "Invalid login name or password.";
            }
            $stmt->close();
        } else {
            // Error preparing statement
            $login_error = "Database error. Please try again later.";
            // Log the actual error: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinSight Admin Login</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Specific styles for login page */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #e9ecef; /* Lighter background */
            min-height: 100vh;
        }
        .login-box {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-box h1 {
            color: #0A74DA;
            margin-bottom: 1.5rem;
        }
        .login-form .form-group {
            margin-bottom: 1.5rem;
            text-align: left; /* Align labels left */
        }
        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .login-form input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .login-form button {
            width: 100%;
            padding: 0.8rem;
            background-color: #0A74DA;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .login-form button:hover {
            background-color: #065c9c;
        }
        .error-message {
            color: #dc3545; /* Red */
            margin-bottom: 1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Admin Login</h1>
        <?php if (!empty($login_error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form action="admin_login.php" method="post" class="login-form">
            <div class="form-group">
                <label for="login_name">Login Name:</label>
                <input type="text" id="login_name" name="login_name" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
    </div>

    <div style="margin-top: 1.5rem;">
        <a href="index.html" style="
            display: inline-block;
            padding: 0.8rem;
            width: 100%;
            background-color: #6c757d;
            color: white;
            border-radius: 4px;
            font-size: 1.1rem;
            text-decoration: none;
            transition: background-color 0.2s;
        " onmouseover="this.style.backgroundColor='#5a6268'" onmouseout="this.style.backgroundColor='#6c757d'">
            Back to Homepage
        </a>
    </div>
</body>
</html>