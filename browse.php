<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireMember();

function e($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['search'] ?? '');
$viewId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

$book = null;
$books = [];

try {

    // View individual book.
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

    } else {

        // Search and retrieve books.
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

        $sql .= " ORDER BY b.title ASC";

        $stmt = $pdo->prepare($sql);

        if ($search !== '') {
            $stmt->bindValue(':search', '%' . $search . '%');
        }

        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {

    error_log('Member book catalogue error: ' . $e->getMessage());

    $errorMessage = 'Unable to load books. Please try again later.';
}

$pageTitle = 'Browse Books';

$extraCss = [
    'assets/css/book-management.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="books-container">

    <div class="books-header">

        <div>
            <h2><?= $book ? 'Book Details' : 'Library Catalogue' ?></h2>

            <p>
                <?= $book
                    ? 'View information about this book.'
                    : 'Discover books available in your library.' ?>
            </p>
        </div>

    </div>

    <?php if (!empty($errorMessage)): ?>

        <div class="error-message">
            <?= e($errorMessage) ?>
        </div>

    <?php elseif ($book): ?>

        <!-- BOOK DETAILS -->

        <div class="details-card">

            <div class="details-grid">

                <div class="detail-item">
                    <strong>Book Title</strong>
                    <span><?= e($book['title']) ?></span>
                </div>

                <div class="detail-item">
                    <strong>ISBN</strong>
                    <span><?= e($book['isbn'] ?? 'Not provided') ?></span>
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

                <div class="detail-item">
                    <strong>Availability</strong>

                    <span>
                        <?= (int) $book['available_copies'] > 0
                            ? 'Available'
                            : 'Currently Unavailable' ?>
                    </span>
                </div>

            </div>

            <div class="details-actions">

                <a href="browse.php" class="btn btn-secondary">
                    Back to Catalogue
                </a>

            </div>

        </div>

    <?php else: ?>

        <!-- SEARCH -->

        <form method="GET" action="browse.php" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by title, ISBN or author..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="btn btn-primary">
                Search
            </button>

            <a href="browse.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

        <br>

        <p>Total books found: <?= count($books) ?></p>

        <!-- BOOK TABLE -->

        <div class="table-container">

            <table class="books-table">

                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Publisher</th>
                        <th>Year</th>
                        <th>Available Copies</th>
                        <th>Availability</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($books): ?>

                        <?php foreach ($books as $item): ?>

                            <tr>

                                <td><?= e($item['isbn']) ?></td>

                                <td><?= e($item['title']) ?></td>

                                <td><?= e($item['author_name'] ?? 'Unknown') ?></td>

                                <td><?= e($item['category_name'] ?? 'Uncategorized') ?></td>

                                <td><?= e($item['publisher']) ?></td>

                                <td><?= e($item['year_published']) ?></td>

                                <td><?= (int) $item['available_copies'] ?></td>

                                <td>
                                    <?php if ((int) $item['available_copies'] > 0): ?>
                                        <span class="status-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-unavailable">Unavailable</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a
                                        href="browse.php?view=<?= (int) $item['book_id'] ?>"
                                        class="btn btn-primary"
                                    >
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="empty-message">
                                No books found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
