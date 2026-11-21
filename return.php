<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only administrators can process returns
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errorMessage = '';
$loan = null;

// Get loan ID
$loanId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? trim($_POST['loan_id'] ?? '')
    : trim($_GET['id'] ?? '');

if (!ctype_digit($loanId) || (int) $loanId < 1) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Fetch loan details
$stmt = $pdo->prepare("
    SELECT
        loans.loan_id,
        loans.date_issued,
        loans.due_date,
        loans.date_returned,
        members.full_name AS member_name,
        members.member_number,
        books.title AS book_title,
        books.isbn
    FROM loans
    INNER JOIN members ON loans.member_id = members.member_id
    INNER JOIN books ON loans.book_id = books.book_id
    WHERE loans.loan_id = ?
    LIMIT 1
");

$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Prevent returning an already returned book
if ($loan['date_returned'] !== null) {
    header('Location: index.php?error=already_returned');
    exit;
}

// Process return
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid form submission. Please try again.';

    } else {

        try {
            $pdo->beginTransaction();

            // Lock the loan record
            $checkLoan = $pdo->prepare("
                SELECT loan_id, book_id, date_returned
                FROM loans
                WHERE loan_id = ?
                FOR UPDATE
            ");

            $checkLoan->execute([$loanId]);
            $currentLoan = $checkLoan->fetch(PDO::FETCH_ASSOC);

            if (!$currentLoan) {
                throw new RuntimeException('Loan record not found.');
            }

            if ($currentLoan['date_returned'] !== null) {
                throw new RuntimeException('This book has already been returned.');
            }

            // Record the return date
            $returnStmt = $pdo->prepare("
                UPDATE loans
                SET date_returned = CURDATE()
                WHERE loan_id = ?
                AND date_returned IS NULL
            ");

            $returnStmt->execute([$loanId]);

            if ($returnStmt->rowCount() !== 1) {
                throw new RuntimeException('Unable to update the loan record.');
            }

            // Increase available copies without exceeding total copies
            $bookStmt = $pdo->prepare("
                UPDATE books
                SET available_copies = LEAST(total_copies, available_copies + 1)
                WHERE book_id = ?
            ");

            $bookStmt->execute([$currentLoan['book_id']]);

            if ($bookStmt->rowCount() !== 1) {
                throw new RuntimeException('Unable to update book availability.');
            }

            $pdo->commit();

            header('Location: index.php?returned=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errorMessage = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Return Book</title>

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/book-management.css">
</head>

<body>

<div class="books-container">

    <div class="books-header">
        <div>
            <h1>Return Book</h1>
            <p>Confirm the return of this borrowed book.</p>
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

    <div class="details-card">

        <h2>Loan Information</h2>

        <div class="details-grid">

            <div class="detail-item">
                <strong>Loan ID</strong>
                <span><?= e($loan['loan_id']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Member Name</strong>
                <span><?= e($loan['member_name']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Member Number</strong>
                <span><?= e($loan['member_number']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Book Title</strong>
                <span><?= e($loan['book_title']) ?></span>
            </div>

            <div class="detail-item">
                <strong>ISBN</strong>
                <span><?= e($loan['isbn']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Date Issued</strong>
                <span><?= e($loan['date_issued']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Due Date</strong>
                <span><?= e($loan['due_date']) ?></span>
            </div>

            <div class="detail-item">
                <strong>Return Date</strong>
                <span>Not yet returned</span>
            </div>

        </div>

        <form method="POST" action="return.php" class="form-actions">

            <input
                type="hidden"
                name="loan_id"
                value="<?= e($loan['loan_id']) ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <button
                type="submit"
                class="btn btn-primary"
                onclick="return confirm('Are you sure you want to confirm this book return?');"
            >
                Confirm Return
            </button>

            <a href="index.php" class="btn btn-secondary">
                Cancel
            </a>

        </form>

    </div>

</div>

</body>
</html>
