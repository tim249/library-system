<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireAdmin();

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Fetch all loan records
$sql = "
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
    ORDER BY loans.date_issued DESC
";

$stmt = $pdo->query($sql);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Feedback messages
$successMessage = '';
$errorMessage = '';

if (isset($_GET['issued'])) {
    $successMessage = 'Book successfully issued.';
}

if (isset($_GET['returned'])) {
    $successMessage = 'Book successfully returned.';
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_id') {
        $errorMessage = 'Invalid loan record.';
    } elseif ($_GET['error'] === 'already_returned') {
        $errorMessage = 'This book has already been returned.';
    } elseif ($_GET['error'] === 'return_failed') {
        $errorMessage = 'Unable to process the return.';
    }
}

// Shared layout settings
$pageTitle = 'Loans Management';

$extraCss = [
    'assets/css/admin-dashboard.css',
    'assets/css/book-management.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="books-container">

    <div class="books-header">

        <div>
            <h1>Loans Management</h1>
            <p>Manage borrowed books and returned books.</p>
        </div>

        <a href="issue.php" class="btn btn-primary">
            + Issue Book
        </a>

    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="success-message">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="error-message">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="table-container">

        <table class="books-table">

            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Member</th>
                    <th>Member Number</th>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Date Issued</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($loans)): ?>

                <?php foreach ($loans as $loan): ?>

                    <?php
                    if ($loan['date_returned'] !== null) {
                        $status = 'Returned';
                        $statusClass = 'status-returned';

                    } elseif (strtotime($loan['due_date']) < strtotime(date('Y-m-d'))) {
                        $status = 'Overdue';
                        $statusClass = 'status-overdue';

                    } else {
                        $status = 'Borrowed';
                        $statusClass = 'status-borrowed';
                    }
                    ?>

                    <tr>

                        <td><?= e($loan['loan_id']) ?></td>

                        <td><?= e($loan['member_name']) ?></td>

                        <td><?= e($loan['member_number']) ?></td>

                        <td><?= e($loan['book_title']) ?></td>

                        <td><?= e($loan['isbn']) ?></td>

                        <td><?= e($loan['date_issued']) ?></td>

                        <td><?= e($loan['due_date']) ?></td>

                        <td>
                            <span class="loan-status <?= e($statusClass) ?>">
                                <?= e($status) ?>
                            </span>
                        </td>

                        <td>

                            <?php if ($loan['date_returned'] === null): ?>

                                <a
                                    href="return.php?id=<?= e($loan['loan_id']) ?>"
                                    class="btn btn-secondary"
                                    onclick="return confirm('Are you sure you want to return this book?');"
                                >
                                    Return
                                </a>

                            <?php else: ?>

                                <span>Completed</span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="empty-message">
                        No loan records found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="form-actions">

        <a href="../admin/dashboard.php" class="btn btn-secondary">
            Back to Dashboard
        </a>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
