<?php
// logout.php

// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage
header("Location: index.html");
exit();
?>