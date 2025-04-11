<?php
session_start();

error_reporting(0);
ini_set('display_errors', 0);


$conn = new mysqli("localhost", "root", "", "wynn_fyp");
if ($conn->connect_error) die("Database connection failed");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    $sql = "SELECT User_ID, Password FROM user_file WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Database error");
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) die("Database error");
    
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["logged_in"] = true;
            $_SESSION["email"] = $email;
            
            // 确保没有任何输出 before this
            header("Location: dashboard.html");
            exit();
        } else {
            $error_message = "Invalid password";
        }
    } else {
        $error_message = "User not found";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FinSight – Login</title>
  <!-- Link to your main CSS file -->
  <link rel="stylesheet" href="/Users/nixonng0912/Downloads/isom4007/FinSight/static/css/main.css">
  <style>
    /* Global Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Full-page layout */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    /* Header */
    header {
        background-color: #0A74DA; /* FinSight Blue */
        color: #fff;
        padding: 1rem;
    }

    header h1 {
        margin: 0;
        text-align: center;
        line-height: 1.2;
        font-size: 1.8rem;
    }

    nav {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 0.5rem;
    }

    nav a {
        color: white;
        text-decoration: none;
        font-weight: bold;
        padding: 0.3rem 0.6rem;
    }

    nav a:hover {
        text-decoration: underline;
    }

    /* Centered Main Content */
    main {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem;
    }

    .login-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        margin-bottom: 1.5rem;
        color: #0A74DA;
        text-align: center;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }

    input {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    input:focus {
        outline: none;
        border-color: #0A74DA;
        box-shadow: 0 0 0 2px rgba(10, 116, 218, 0.2);
    }

    /* Button */
    button {
        width: 100%;
        padding: 0.8rem;
        border: none;
        background-color: #0A74DA;
        color: white;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #084a9a;
    }

    /* Forgot Password */
    .forgot-password {
        margin-top: 1.5rem;
        text-align: center;
    }

    a {
        color: #0A74DA;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    /* Footer */
    footer {
        background-color: #f4f4f4;
        text-align: center;
        padding: 1rem;
        font-size: 0.9rem;
        border-top: 1px solid #e0e0e0;
    }

    /* Error Message */
    .error-message {
        color: #d32f2f;
        background-color: #fde8e8;
        padding: 0.8rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
        text-align: center;
        border: 1px solid #f5c6cb;
    }

    select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        background-color: white;
    }
  </style>
</head>
<body>
  <header>
    <h1>Login to your FinSight account</h1>
    <nav>
      <a href="dashboard.html">Home</a>
      <a href="register.php">Register</a>
    </nav>
  </header>

  <main>
    <div class="login-container">
      <h2>Login</h2>
      <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <div class="form-group">
          <label for="email">Email:</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="Enter your email" 
            required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
          >
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="Enter your password" 
            required
          >
        </div>

        <button type="submit">Login</button>
      </form>

      <p class="forgot-password">
        <a href="reset_password.php">Forgot Password?</a>
      </p>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 FinSight. All rights reserved.</p>
  </footer>
</body>
</html>

<style>
    /* Global Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Full-page layout */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    /* Header */
    header {
        background-color: #0A74DA; /* FinSight Blue */
        color: #fff;
        padding: 1rem;
    }

    header h1 {
        margin: 0;
        text-align: center;
        line-height: 1.2;
        font-size: 1.8rem;
    }

    nav {
        display: flex;
        justify-content: center;  /* <<< THIS LINE IS CHANGED */
        gap: 1.5rem; /* Increased gap slightly for better visual separation */
        margin-top: 0.5rem;
    }

    nav a {
        color: white;
        text-decoration: none;
        font-weight: bold;
        padding: 0.3rem 0.6rem;
    }

    nav a:hover {
        text-decoration: underline;
    }

    /* Centered Main Content */
    main {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem;
    }

    .login-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        margin-bottom: 1.5rem;
        color: #0A74DA;
        text-align: center;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }

    input {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    input:focus {
        outline: none;
        border-color: #0A74DA;
        box-shadow: 0 0 0 2px rgba(10, 116, 218, 0.2);
    }

    /* Button */
    button {
        width: 100%;
        padding: 0.8rem;
        border: none;
        background-color: #0A74DA;
        color: white;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #084a9a;
    }

    /* Forgot Password */
    .forgot-password {
        margin-top: 1.5rem;
        text-align: center;
    }

    a {
        color: #0A74DA;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    /* Footer */
    footer {
        background-color: #f4f4f4;
        text-align: center;
        padding: 1rem;
        font-size: 0.9rem;
        border-top: 1px solid #e0e0e0;
    }

    /* Error Message */
    .error-message {
        color: #d32f2f;
        background-color: #fde8e8;
        padding: 0.8rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
        text-align: center;
        border: 1px solid #f5c6cb;
    }

    select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        background-color: white;
    }
  </style>