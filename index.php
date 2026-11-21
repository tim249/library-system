<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireMember();

// Get member ID.
$stmt = $pdo->prepare("
    SELECT member_id
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

if (!$member || !$member['member_id']) {
    die("Your account is not linked to a library member record.");
}

$memberId = (int) $member['member_id'];

// Get reading list.
$stmt = $pdo->prepare("
    SELECT
        rl.reading_id,
        rl.reading_status,
        rl.notes,
        rl.date_added,
        b.title,
        a.name AS author_name
    FROM reading_lists rl
    INNER JOIN books b ON rl.book_id = b.book_id
    LEFT JOIN authors a ON b.author_id = a.author_id
    WHERE rl.member_id = ?
    ORDER BY rl.date_added DESC
");

$stmt->execute([$memberId]);
$readingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Member information.
$fullName = $_SESSION['full_name'] ?? 'Member';

// Display messages.
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

// Shared layout.
$pageTitle = 'My Reading List';

$extraCss = [
    'assets/css/reading-list.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">

    <header class="header">

        <div>
            <h1>My Reading List</h1>
            <p>Welcome, <?= clean($fullName) ?>.</p>
        </div>

        <a href="create.php" class="add-btn">
            + Add New Book
        </a>

    </header>

    <a href="../dashboard/index.php" class="back-link">
        ← Back to Dashboard
    </a>

    <?php if ($success !== ''): ?>

        <div class="message success">
            <?= clean($success) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="message error">
            <?= clean($error) ?>
        </div>

    <?php endif; ?>

    <section class="panel">

        <h2>My Saved Books (<?= count($readingList) ?>)</h2>

        <?php if (!empty($readingList)): ?>

            <div class="reading-list">

                <?php foreach ($readingList as $item): ?>

                    <article class="book-card">

                        <div class="book-info">

                            <h3><?= clean($item['title']) ?></h3>

                            <p>
                                Author:
                                <?= clean($item['author_name'] ?? 'Unknown') ?>
                            </p>

                            <span class="status">
                                <?= clean($item['reading_status']) ?>
                            </span>

                            <p class="notes">
                                Notes:
                                <?= clean($item['notes'] ?? '') ?: 'No notes added.' ?>
                            </p>

                            <small>
                                Added:
                                <?= clean($item['date_added']) ?>
                            </small>

                        </div>

                        <div class="book-actions">

                            <a
                                href="edit.php?id=<?= (int) $item['reading_id'] ?>"
                                class="edit-btn"
                            >
                                Edit
                            </a>

                            <form
                                action="delete.php"
                                method="POST"
                                class="delete-form"
                                onsubmit="return confirm('Are you sure you want to remove this book from your reading list?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= clean(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="reading_id"
                                    value="<?= (int) $item['reading_id'] ?>"
                                >

                                <button type="submit" class="delete-btn">
                                    Delete
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <h3>Your reading list is empty.</h3>

                <p>
                    Start building your personal reading list
                    by adding a book.
                </p>

                <a href="create.php" class="add-btn">
                    Add Your First Book
                </a>

            </div>

        <?php endif; ?>

    </section>

</div>

<script src="<?= $baseUrl ?>/assets/js/reading-list.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
