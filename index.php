<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireAdmin();

// Safe output helper
function e($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// View individual book
$viewId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);
$book = null;

if ($viewId !== null && $viewId !== false && $viewId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            b.*,
            a.name AS author_name,
            c.name AS category_name
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.author_id
        LEFT JOIN categories c ON b.category_id = c.category_id
        WHERE b.book_id = :id
        LIMIT 1
    ");

    $stmt->execute(['id' => $viewId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: index.php');
        exit;
    }
}

// Search and fetch books
$books = [];
$search = trim($_GET['search'] ?? '');

if ($viewId === null || $viewId === false || $viewId < 1) {

    $sql = "
        SELECT
            b.book_id,
            b.isbn,
            b.title,
            b.publisher,
            b.year_published,
            b.total_copies,
            b.available_copies,
            a.name AS author_name,
            c.name AS category_name
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.author_id
        LEFT JOIN categories c ON b.category_id = c.category_id
    ";

    if ($search !== '') {
        $sql .= "
            WHERE b.title LIKE :search
            OR b.isbn LIKE :search
            OR a.name LIKE :search
        ";
    }

    $sql .= " ORDER BY b.book_id DESC";

    $stmt = $pdo->prepare($sql);

    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%');
    }

    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Feedback messages
$successMessage = '';
$errorMessage = '';

if (isset($_GET['deleted'])) {
    $successMessage = 'Book deleted successfully.';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'book_in_use':
            $errorMessage = 'This book cannot be deleted because it has loan or reading-list records.';
            break;

        case 'invalid_id':
            $errorMessage = 'Invalid book ID.';
            break;

        case 'not_found':
            $errorMessage = 'The selected book was not found.';
            break;

        case 'delete_failed':
            $errorMessage = 'Unable to delete this book. Please try again.';
            break;

        default:
            $errorMessage = 'An unexpected error occurred.';
    }
}

// Shared layout
$pageTitle = 'Book Management';

$extraCss = [
    'assets/css/admin-dashboard.css',
    'assets/css/book-management.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="books-container">

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

    <?php if ($book): ?>

        <!-- INDIVIDUAL BOOK DETAILS -->

        <div class="books-header">

            <div>
                <h2>Book Details</h2>
                <p>Complete information about this book.</p>
            </div>

        </div>

        <div class="details-card">

            <div class="details-grid">

                <div class="detail-item">
                    <strong>Book ID</strong>
                    <span><?= (int) $book['book_id'] ?></span>
                </div>

                <div class="detail-item">
                    <strong>ISBN</strong>
                    <span><?= e($book['isbn'] ?? 'Not provided') ?></span>
                </div>

                <div class="detail-item">
                    <strong>Book Title</strong>
                    <span><?= e($book['title']) ?></span>
                </div>

                <div class="detail-item">
                    <strong>Author</strong>
                    <span><?= e($book['author_name'] ?? 'Unknown') ?></span>
                </div>

                <div class="detail-item">
                    <strong>Category</strong>
                    <span><?= e($book['category_name'] ?? 'Uncategorized') ?></span>
                </div>

                <div class="detail-item">
                    <strong>Publisher</strong>
                    <span><?= e($book['publisher'] ?? 'Not provided') ?></span>
                </div>

                <div class="detail-item">
                    <strong>Year Published</strong>
                    <span><?= e($book['year_published'] ?? 'Not provided') ?></span>
                </div>

                <div class="detail-item">
                    <strong>Total Copies</strong>
                    <span><?= (int) $book['total_copies'] ?></span>
                </div>

                <div class="detail-item">
                    <strong>Available Copies</strong>
                    <span><?= (int) $book['available_copies'] ?></span>
                </div>

            </div>

            <div class="details-actions">

                <a href="index.php" class="btn btn-secondary">
                    Back to Books
                </a>

                <a href="edit.php?id=<?= (int) $book['book_id'] ?>"
                   class="btn btn-primary">
                    Edit Book
                </a>

            </div>

        </div>

    <?php else: ?>

        <!-- BOOK LIST -->

        <div class="books-header">

            <div>
                <h2>Book Management</h2>
                <p>Manage all books in your library.</p>
            </div>

            <a href="create.php" class="btn btn-primary">
                + Add New Book
            </a>

        </div>

        <!-- SEARCH -->

        <form method="GET" action="index.php" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by title, ISBN or author..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="btn btn-primary">
                Search
            </button>

            <a href="index.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

        <br>

        <p>Total books found: <?= count($books) ?></p>

        <div class="table-container">

            <table class="books-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ISBN</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Publisher</th>
                        <th>Year</th>
                        <th>Total Copies</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($books): ?>

                        <?php foreach ($books as $item): ?>

                            <tr>

                                <td><?= (int) $item['book_id'] ?></td>
                                <td><?= e($item['isbn']) ?></td>
                                <td><?= e($item['title']) ?></td>
                                <td><?= e($item['author_name'] ?? 'Unknown') ?></td>
                                <td><?= e($item['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= e($item['publisher']) ?></td>
                                <td><?= e($item['year_published']) ?></td>
                                <td><?= (int) $item['total_copies'] ?></td>
                                <td><?= (int) $item['available_copies'] ?></td>

                                <td>

                                    <div class="action-links">

                                        <a href="index.php?view=<?= (int) $item['book_id'] ?>">
                                            View
                                        </a>

                                        <a href="edit.php?id=<?= (int) $item['book_id'] ?>">
                                            Edit
                                        </a>

                                        <form
                                            action="delete.php"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this book?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="book_id"
                                                value="<?= (int) $item['book_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(csrfToken()) ?>"
                                            >

                                            <button type="submit" class="btn btn-danger">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="empty-message">
                                No books found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

    <br>

    <a href="../admin/dashboard.php" class="btn btn-secondary">
        Back to Dashboard
    </a>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
