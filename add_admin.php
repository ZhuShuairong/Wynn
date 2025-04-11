<?php
session_start(); // Optional

// --- Configuration ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database Credentials (Replace with your actual credentials)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your DB password
define('DB_NAME', 'wynn_fyp'); // Your database name

// --- Dropdown Options (CUSTOMIZE THESE) ---
$privilege_options = ['none', 'view', 'edit', 'all']; // Example privileges
$role_options = ['consultant', 'administrator', 'editor']; // Example roles


// --- Variable Initialization ---
$username = '';
$email = '';
$referral_id = '';
$selected_privilege = '';
$selected_role = '';
$error_message = '';
$success_message = '';

// --- Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $referral_id = trim($_POST['referral_id']); // Now mandatory
    $selected_privilege = trim($_POST['privileges']);
    $selected_role = trim($_POST['roles']);

    // --- Validation (Referral ID is now mandatory) ---
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($referral_id) || empty($selected_privilege) || empty($selected_role)) {
        $error_message = 'All fields are required.'; // Updated error message
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = 'Password must contain at least one uppercase letter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $error_message = 'Invalid email format.';
    } elseif (!ctype_digit($referral_id)) { // Check if Referral ID is numeric (since it's mandatory)
         $error_message = 'Referral Admin ID must be a number.';
    } elseif (!in_array($selected_privilege, $privilege_options)) {
        $error_message = 'Invalid privilege selected.';
    } elseif (!in_array($selected_role, $role_options)) {
        $error_message = 'Invalid role selected.';
    } else {
        // --- Database Connection ---
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Add Admin DB Connection Error: " . $conn->connect_error);
            $error_message = "Database connection failed. Please try again later.";
        } else {
            // --- Check for Duplicate Username ---
            $check_sql = "SELECT Admin_ID FROM admin_file WHERE Admin_Login_Name = ? OR Email = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                $check_stmt->store_result();
            
                if ($check_stmt->num_rows > 0) {
                    $error_message = "An admin with this username or email already exists. Please use a different one.";        
                } else {
                     // --- Check if Referral Admin ID exists (Now always checked as it's mandatory) ---
                     $referrer_exists = false; // Assume false until proven true
                     $ref_check_sql = "SELECT Admin_ID FROM admin_file WHERE Admin_ID = ?";
                     $ref_stmt = $conn->prepare($ref_check_sql);
                     if($ref_stmt){
                         $ref_stmt->bind_param("i", $referral_id);
                         $ref_stmt->execute();
                         $ref_stmt->store_result();
                         if($ref_stmt->num_rows > 0){
                             $referrer_exists = true; // Referrer found
                         } else {
                             $error_message = "The specified Referral Admin ID does not exist.";
                         }
                         $ref_stmt->close();
                     } else {
                         $error_message = "Error checking referral ID.";
                         error_log("Referral Check Prepare Error: " . $conn->error);
                     }

                    // --- Proceed only if username is unique AND referrer exists ---
                    if($referrer_exists) {
                        // Hash the password securely
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Referral ID is now always an integer
                        $referral_id_to_insert = (int)$referral_id;

                        // Prepare INSERT statement including Referral_Admin_ID
                        $sql = "INSERT INTO admin_file (Admin_Login_Name, Password, Email, Referral_Admin_ID, Privileges, Roles, Add_Time, Change_Timestamp) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                        $stmt = $conn->prepare($sql);

                        if ($stmt) {
                            // Bind parameters (s = string, i = integer)
                            $stmt->bind_param("sssiss",
                                $username,
                                $hashed_password,
                                $email,
                                $referral_id_to_insert,
                                $selected_privilege,
                                $selected_role
                            );

                            // Execute statement
                            if ($stmt->execute()) {
                                $success_message = "Admim '".htmlspecialchars($username)."' added successfully!";
                                // Clear form fields on success
                                $username = '';
                                $email = '';
                                $referral_id = '';
                                $selected_privilege = '';
                                $selected_role = '';
                            } else {
                                error_log("Add Admin Execute Error: " . $stmt->error);
                                $error_message = 'Failed to add admin user. Please try again.';
                            }
                            $stmt->close();
                        } else {
                            error_log("Add Admin Prepare Error: " . $conn->error);
                            $error_message = 'Failed to prepare adding admin user. Please try again.';
                        }
                    } // end if referrer_exists
                }
                $check_stmt->close();
            } else {
                 error_log("Add Admin Check Prepare Error: " . $conn->error);
                 $error_message = 'Failed to check username validity. Please try again.';
            }
            $conn->close();
        } // End database connection else
    } // End validation else
} // End POST request processing

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Admin - FinSight</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; padding: 20px; background-color: #f8f9fa; color: #212529; }
        .container { max-width: 550px; margin: 40px auto; background: #fff; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border: 1px solid #e3e6f0; }
        h2 { text-align: center; color: #343a40; margin-bottom: 25px; font-weight: 600; }
        .form-group { margin-bottom: 20px; } /* Increased spacing */
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px; /* Slightly larger padding */
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.95rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        input:focus, select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        /* Style for password hint - adjusted margin */
        .input-hint {
            font-size: 0.85em;
            color: #6c757d; /* Standard Bootstrap secondary text color */
            margin-top: 5px; /* Added space below the input */
            display: block;
        }
        button[type="submit"] {
            display: block;
            width: 100%;
            background-color: #007bff; /* Bootstrap primary blue */
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        button[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; text-align: center; border: 1px solid transparent; font-size: 0.95rem;}
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb;}
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .back-link { display: block; text-align: center; margin-top: 25px; font-size: 0.95rem;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Admin User</h2>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
            <div class="form-group">
                <label for="username">Admin Login Name (Username):</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required pattern="(?=.*[A-Z]).{8,}" title="Password must be at least 8 characters long and contain at least one uppercase letter.">
                 <span class="input-hint">Min. 8 characters, at least one uppercase letter.</span>
            </div>

             <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

             <div class="form-group">
                <label for="referral_id">Referral Admin ID:</label> 
                <input type="number" id="referral_id" name="referral_id" value="<?php echo htmlspecialchars($referral_id); ?>" min="1" required> 
                 <span class="input-hint">Enter the ID of the existing admin who referred this user.</span> 
            </div>

            <div class="form-group">
                <label for="privileges">Privileges:</label>
                <select id="privileges" name="privileges" required>
                    <option value="">-- Select Privilege --</option>
                    <?php foreach ($privilege_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selected_privilege === $option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($option)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="roles">Role:</label>
                <select id="roles" name="roles" required>
                    <option value="">-- Select Role --</option>
                     <?php foreach ($role_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selected_role === $option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($option)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Add Admin</button>
        </form>

        <div class="back-link">
            <a href="admin_dashboard.php">Back to Admin Dashboard</a> <!-- Adjust link if needed -->
        </div>
    </div>
</body>
</html>