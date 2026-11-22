<?php
declare(strict_types=1);

// Start the session if it has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all session variables.
$_SESSION = [];

// Destroy the session.
session_destroy();

// Redirect the user to the login page.
header("Location: login.php");
exit;
?>