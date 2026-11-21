<?php

// Database configuration

$host = 'localhost';
$dbname = 'library_db';
$username = 'root';
$password = '';

try {

    // Create PDO database connection

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Set PDO error mode

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Fetch database records as associative arrays

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Disable emulated prepared statements

    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {

    // Log technical error for administrator

    error_log($e->getMessage());

    // Display friendly error

    die("Database connection failed. Please contact the administrator.");

}
?>
