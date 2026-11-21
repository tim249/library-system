<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Only administrators can delete books
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/index.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die('Invalid security token. Please go back and try again.');
}

// Validate book ID
$bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    header('Location: index.php?error=invalid_id');
    exit;
}

try {
    // Check whether the book has loan records
    $loanCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM loans WHERE book_id = ?"
    );
    $loanCheck->execute([$bookId]);
    $loanCount = (int) $loanCheck->fetchColumn();

    // Check whether the book appears in members' reading lists
    $readingCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM reading_lists WHERE book_id = ?"
    );
    $readingCheck->execute([$bookId]);
    $readingCount = (int) $readingCheck->fetchColumn();

    // Prevent deletion if records exist
    if ($loanCount > 0 || $readingCount > 0) {
        header('Location: index.php?error=book_in_use');
        exit;
    }

    // Delete the book
    $delete = $pdo->prepare(
        "DELETE FROM books WHERE book_id = ?"
    );
    $delete->execute([$bookId]);

    if ($delete->rowCount() > 0) {
        header('Location: index.php?deleted=1');
        exit;
    }

    header('Location: index.php?error=not_found');
    exit;

} catch (PDOException $e) {
    header('Location: index.php?error=delete_failed');
    exit;
}
?>