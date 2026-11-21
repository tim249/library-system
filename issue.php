<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only administrators can issue books
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errorMessage = '';

$memberId = '';
$bookId = '';
$dueDate = '';

// Fetch active members
$memberStmt = $pdo->query("
    SELECT member_id, member_number, full_name, status
    FROM members
    WHERE LOWER(status) = 'active'
    ORDER BY full_name ASC
");

$members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch books with available copies
$bookStmt = $pdo->query("
    SELECT book_id, title, isbn, available_copies
    FROM books
    WHERE available_copies > 0
    ORDER BY title ASC
");

$books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);

// Process issue form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $memberId = trim($_POST['member_id'] ?? '');
    $bookId = trim($_POST['book_id'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid form submission. Please try again.';

    } elseif (!ctype_digit($memberId) || !ctype_digit($bookId)) {
        $errorMessage = 'Please select a valid member and book.';

    } else {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $dueDate);
        $dateErrors = DateTime::getLastErrors();

        $validDate = $dateObject !== false
            && $dateObject->format('Y-m-d') === $dueDate
            && ($dateErrors === false
                || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));

        if (!$validDate || $dueDate <= date('Y-m-d')) {
            $errorMessage = 'Please enter a valid due date in the future.';
        } else {
            try {
                $pdo->beginTransaction();

                // Lock and verify the member record
                $memberCheck = $pdo->prepare("
                    SELECT member_id, status
                    FROM members
                    WHERE member_id = ?
                    FOR UPDATE
                ");

                $memberCheck->execute([$memberId]);
                $member = $memberCheck->fetch(PDO::FETCH_ASSOC);

                if (!$member || strtolower($member['status']) !== 'active') {
                    throw new RuntimeException('The selected member is not active.');
                }

                // Lock and verify the book record
                $bookCheck = $pdo->prepare("
                    SELECT book_id, available_copies
                    FROM books
                    WHERE book_id = ?
                    FOR UPDATE
                ");

                $bookCheck->execute([$bookId]);
                $book = $bookCheck->fetch(PDO::FETCH_ASSOC);

                if (!$book || (int) $book['available_copies'] < 1) {
                    throw new RuntimeException('This book has no available copies.');
                }

                // Create the loan record
                $issueStmt = $pdo->prepare("
                    INSERT INTO loans
                        (member_id, book_id, issued_by, date_issued, due_date)
                    VALUES
                        (?, ?, ?, CURDATE(), ?)
                ");

                $issueStmt->execute([
                    $memberId,
                    $bookId,
                    $_SESSION['user_id'],
                    $dueDate
                ]);

                // Reduce available copies
                $updateBook = $pdo->prepare("
                    UPDATE books
                    SET available_copies = available_copies - 1
                    WHERE book_id = ?
                ");

                $updateBook->execute([$bookId]);

                $pdo->commit();

                header('Location: index.php?issued=1');
                exit;

            } catch (RuntimeException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errorMessage = $e->getMessage();

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errorMessage = 'Something went wrong while issuing the book. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Book</title>

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/book-management.css">
</head>

<body>

<div class="books-container">

    <div class="books-header">
        <div>
            <h1>Issue a Book</h1>
            <p>Assign an available book to a registered member.</p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            Back to Loans
        </a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="form-container">

        <form method="POST" action="issue.php" class="form-card">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <div class="form-grid">

                <div class="form-group">
                    <label for="member_id">Select Member *</label>

                    <select name="member_id" id="member_id" required>
                        <option value="">-- Select Member --</option>

                        <?php foreach ($members as $member): ?>

                            <option
                                value="<?= e($member['member_id']) ?>"
                                <?= $memberId == $member['member_id'] ? 'selected' : '' ?>
                            >
                                <?= e($member['full_name']) ?>
                                (<?= e($member['member_number']) ?>)
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="book_id">Select Book *</label>

                    <select name="book_id" id="book_id" required>
                        <option value="">-- Select Book --</option>

                        <?php foreach ($books as $book): ?>

                            <option
                                value="<?= e($book['book_id']) ?>"
                                <?= $bookId == $book['book_id'] ? 'selected' : '' ?>
                            >
                                <?= e($book['title']) ?>
                                (<?= e($book['isbn']) ?>)
                                - <?= e($book['available_copies']) ?> available
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date *</label>

                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                        value="<?= e($dueDate) ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Issue Book
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
