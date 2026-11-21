<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Make sure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Only members can access this page.
if (($_SESSION['role'] ?? '') !== 'member') {
    header('Location: ../index.php');
    exit;
}

// Deletion must happen through a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify the security token.
$token = $_POST['csrf_token'] ?? '';

if (!verifyCsrfToken($token)) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: index.php');
    exit;
}

// Validate the reading-list entry ID.
$readingId = filter_input(INPUT_POST, 'reading_id', FILTER_VALIDATE_INT);

if (!$readingId || $readingId < 1) {
    $_SESSION['error'] = 'Invalid reading-list entry.';
    header('Location: index.php');
    exit;
}

try {
    // Get the member ID linked to the logged-in user.
    $stmt = $pdo->prepare(
        'SELECT member_id
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute([
        'user_id' => $_SESSION['user_id']
    ]);

    $member = $stmt->fetch();

    if (!$member || empty($member['member_id'])) {
        $_SESSION['error'] = 'Your account is not linked to a library membership.';
        header('Location: index.php');
        exit;
    }

    $memberId = (int) $member['member_id'];

    // Delete only the selected entry belonging to this member.
    $delete = $pdo->prepare(
        'DELETE FROM reading_lists
         WHERE reading_id = :reading_id
           AND member_id = :member_id'
    );

    $delete->execute([
        'reading_id' => $readingId,
        'member_id' => $memberId
    ]);

    if ($delete->rowCount() > 0) {
        $_SESSION['success'] = 'Book removed from your reading list successfully.';
    } else {
        $_SESSION['error'] = 'Reading-list entry not found or already removed.';
    }

} catch (PDOException $e) {
    error_log('Reading list deletion error: ' . $e->getMessage());
    $_SESSION['error'] = 'A database error occurred. Please try again.';
}

header('Location: index.php');
exit;