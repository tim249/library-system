<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireMember();

$error = '';
$loans = [];

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

    // Retrieve this member's loan history.
    $stmt = $pdo->prepare(
        "SELECT
            l.loan_id,
            l.date_issued,
            l.due_date,
            l.date_returned,
            b.title,
            a.name AS author_name,
            CASE
                WHEN l.date_returned IS NOT NULL THEN 'Returned'
                WHEN l.due_date < CURDATE() THEN 'Overdue'
                ELSE 'Borrowed'
            END AS loan_status
         FROM loans l
         INNER JOIN books b ON l.book_id = b.book_id
         LEFT JOIN authors a ON b.author_id = a.author_id
         WHERE l.member_id = :member_id
         ORDER BY l.date_issued DESC"
    );

    $stmt->execute([
        'member_id' => $memberId
    ]);

    $loans = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log('Member loan history error: ' . $e->getMessage());
    $error = 'Unable to load your loan history. Please try again.';
}

// Shared layout settings.
$pageTitle = 'My Loan History';

$extraCss = [
    'assets/css/member-loans.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="member-loans-container">

    <div class="loans-heading">

        <div>
            <h1>My Loan History</h1>
            <p>View your borrowed books and their return dates.</p>
        </div>

        <a href="../books/browse.php" class="browse-btn">
            Browse Books
        </a>

    </div>

    <?php if ($error !== ''): ?>

        <div class="loan-message error">
            <?= clean($error) ?>
        </div>

    <?php elseif (empty($loans)): ?>

        <div class="empty-loans">
            <h2>No Loan Records Found</h2>

            <p>You have not borrowed any books yet.</p>

            <a href="../books/browse.php" class="browse-btn">
                Explore Books
            </a>
        </div>

    <?php else: ?>

        <!-- Loan Statistics -->
        <div class="loan-summary">

            <div class="summary-card">
                <h3><?= count($loans) ?></h3>
                <p>Total Loan Records</p>
            </div>

            <div class="summary-card">
                <h3>
                    <?= count(array_filter(
                        $loans,
                        fn($loan) => $loan['loan_status'] === 'Borrowed'
                    )) ?>
                </h3>
                <p>Currently Borrowed</p>
            </div>

            <div class="summary-card">
                <h3>
                    <?= count(array_filter(
                        $loans,
                        fn($loan) => $loan['loan_status'] === 'Overdue'
                    )) ?>
                </h3>
                <p>Overdue Books</p>
            </div>

            <div class="summary-card">
                <h3>
                    <?= count(array_filter(
                        $loans,
                        fn($loan) => $loan['loan_status'] === 'Returned'
                    )) ?>
                </h3>
                <p>Returned Books</p>
            </div>

        </div>

        <!-- Loan History Table -->
        <div class="loan-table-container">

            <table class="loan-table">

                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Date Issued</th>
                        <th>Due Date</th>
                        <th>Date Returned</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($loans as $loan): ?>

                        <tr>

                            <td>
                                <?= clean($loan['title']) ?>
                            </td>

                            <td>
                                <?= clean($loan['author_name'] ?? 'Unknown author') ?>
                            </td>

                            <td>
                                <?= clean($loan['date_issued']) ?>
                            </td>

                            <td>
                                <?= clean($loan['due_date']) ?>
                            </td>

                            <td>
                                <?= $loan['date_returned']
                                    ? clean($loan['date_returned'])
                                    : 'Not returned' ?>
                            </td>

                            <td>
                                <span class="loan-status <?= strtolower($loan['loan_status']) ?>">
                                    <?= clean($loan['loan_status']) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
