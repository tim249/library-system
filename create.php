<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireMember();

// Get the logged-in member's ID.
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

$error = '';

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if (!verifyCsrfToken($token)) {

        $error = "Invalid request. Please refresh the page.";

    } elseif ($bookId <= 0) {

        $error = "Please select a book.";

    } else {

        try {

            // Check whether the selected book exists.
            $stmt = $pdo->prepare("
                SELECT book_id
                FROM books
                WHERE book_id = ?
            ");

            $stmt->execute([$bookId]);

            if (!$stmt->fetch()) {

                $error = "The selected book does not exist.";

            } else {

                // Check whether the book is already in the reading list.
                $stmt = $pdo->prepare("
                    SELECT reading_id
                    FROM reading_lists
                    WHERE member_id = ? AND book_id = ?
                ");

                $stmt->execute([$memberId, $bookId]);

                if ($stmt->fetch()) {

                    $error = "This book is already in your reading list.";

                } else {

                    // Add book to reading list.
                    $stmt = $pdo->prepare("
                        INSERT INTO reading_lists
                        (member_id, book_id, reading_status)
                        VALUES (?, ?, 'Want to Read')
                    ");

                    $stmt->execute([$memberId, $bookId]);

                    $_SESSION['success'] =
                        "Book added to your reading list successfully.";

                    header("Location: index.php");
                    exit();
                }
            }

        } catch (PDOException $e) {

            error_log('Reading list create error: ' . $e->getMessage());

            $error = "Unable to add the book. Please try again.";
        }
    }
}

// Fetch books from the catalogue.
$stmt = $pdo->query("
    SELECT book_id, title
    FROM books
    ORDER BY title ASC
");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Shared layout.
$pageTitle = 'Add Book to Reading List';

$extraCss = [
    'assets/css/reading-list.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">

    <header class="header">

        <div>
            <h1>Add a Book</h1>
            <p>Add a book to your personal reading list.</p>
        </div>

        <a href="index.php" class="add-btn">
            Back to Reading List
        </a>

    </header>

    <section class="panel">

        <h2>Add Book to My Reading List</h2>

        <p class="welcome">
            Select a book from the library catalogue to add it
            to your personal reading list.
        </p>

        <?php if ($error !== ''): ?>

            <div class="message error">
                <?= clean($error) ?>
            </div>

        <?php endif; ?>

        <?php if (count($books) > 0): ?>

            <form method="POST" class="edit-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= clean(csrfToken()) ?>"
                >

                <label for="book_id">Select Book</label>

                <select name="book_id" id="book_id" required>

                    <option value="">-- Choose a Book --</option>

                    <?php foreach ($books as $book): ?>

                        <option
                            value="<?= (int) $book['book_id'] ?>"
                            <?= (int) ($_POST['book_id'] ?? 0) === (int) $book['book_id'] ? 'selected' : '' ?>
                        >
                            <?= clean($book['title']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="add-btn">
                    Save to Reading List
                </button>

            </form>

        <?php else: ?>

            <div class="empty">

                <h3>No Books Available</h3>

                <p>
                    The library catalogue does not contain any books yet.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>

<script src="<?= $baseUrl ?>/assets/js/reading-list.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
