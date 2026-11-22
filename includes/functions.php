<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store temporary messages
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Display temporary messages
function displayMessage() {

    if (isset($_SESSION['message'])) {

        $message = $_SESSION['message'];

        echo '<div class="alert alert-' .
            htmlspecialchars($message['type']) . '">';

        echo htmlspecialchars($message['text']);

        echo '</div>';

        unset($_SESSION['message']);
    }
}

// Escape output
function clean($data) {
    return htmlspecialchars(
        (string)$data,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Generate CSRF token
function csrfToken() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token) {

    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

?>