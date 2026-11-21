<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit;
}

// Validate book ID.
$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    header("Location: index.php");
    exit;
}

// Fetch the selected book.
$stmt = $pdo->prepare(
    "SELECT * FROM books WHERE book_id = ? LIMIT 1"
);

$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header("Location: index.php");
    exit;
}

// Fetch authors and categories.
$authors = $pdo->query(
    "SELECT author_id, name FROM authors ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query(
    "SELECT category_id, name FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {

        $isbn = trim($_POST['isbn'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $authorId = $_POST['author_id'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $publisher = trim($_POST['publisher'] ?? '');
        $year = trim($_POST['year_published'] ?? '');

        $totalCopies = filter_var(
            $_POST['total_copies'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($title === '') {
            $error = "Book title is required.";

        } elseif ($totalCopies === false || $totalCopies < 1) {
            $error = "Total copies must be at least 1.";

        } elseif (
            $year !== '' &&
            (!ctype_digit($year) ||
            (int)$year < 1000 ||
            (int)$year > (int)date('Y'))
        ) {
            $error = "Please enter a valid publication year.";

        } else {

            $authorId = $authorId === '' ? null :
                filter_var($authorId, FILTER_VALIDATE_INT);

            $categoryId = $categoryId === '' ? null :
                filter_var($categoryId, FILTER_VALIDATE_INT);

            if (
                ($authorId !== null &&
                ($authorId === false || $authorId < 1)) ||
                ($categoryId !== null &&
                ($categoryId === false || $categoryId < 1))
            ) {
                $error = "Invalid author or category selected.";
            }

            // Check that selected author exists.
            if ($error === '' && $authorId !== null) {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM authors WHERE author_id = ?"
                );
                $check->execute([$authorId]);

                if ((int)$check->fetchColumn() === 0) {
                    $error = "Selected author does not exist.";
                }
            }

            // Check that selected category exists.
            if ($error === '' && $categoryId !== null) {
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM categories WHERE category_id = ?"
                );
                $check->execute([$categoryId]);

                if ((int)$check->fetchColumn() === 0) {
                    $error = "Selected category does not exist.";
                }
            }

            // Calculate how many copies are currently borrowed.
            $borrowedCopies = (int)$book['total_copies']
                - (int)$book['available_copies'];

            if ($error === '' && $totalCopies < $borrowedCopies) {
                $error = "Total copies cannot be less than the number of borrowed copies.";
            }

            // Update the book.
            if ($error === '') {

                $availableCopies = $totalCopies - $borrowedCopies;

                try {

                    $sql = "
                        UPDATE books SET
                            isbn = :isbn,
                            title = :title,
                            author_id = :author_id,
                            category_id = :category_id,
                            publisher = :publisher,
                            year_published = :year_published,
                            total_copies = :total_copies,
                            available_copies = :available_copies
                        WHERE book_id = :book_id
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        'isbn' => $isbn !== '' ? $isbn : null,
                        'title' => $title,
                        'author_id' => $authorId,
                        'category_id' => $categoryId,
                        'publisher' => $publisher !== '' ? $publisher : null,
                        'year_published' => $year !== '' ? (int)$year : null,
                        'total_copies' => $totalCopies,
                        'available_copies' => $availableCopies,
                        'book_id' => $bookId
                    ]);

                    header("Location: index.php");
                    exit;

                } catch (PDOException $e) {

                    if ($e->getCode() === '23000') {
                        $error = "This ISBN may already belong to another book.";
                    } else {
                        $error = "Unable to update the book. Please try again.";
                    }
                }
            }
        }

        // Keep submitted values if validation fails.
        $book = array_merge($book, [
            'isbn' => $isbn,
            'title' => $title,
            'author_id' => $authorId ?: null,
            'category_id' => $categoryId ?: null,
            'publisher' => $publisher,
            'year_published' => $year,
            'total_copies' => $_POST['total_copies'] ?? $book['total_copies']
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Book | Library System</title>

    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/book-management.css">
</head>

<body>

<div class="form-container">

    <div class="form-card">

        <h2>Edit Book</h2>

        <p>Update the information for this book.</p>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="edit.php?id=<?= (int)$bookId ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrfToken()) ?>"
            >

            <div class="form-grid">

                <div class="form-group">
                    <label>Book Title *</label>
                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars((string)$book['title']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>ISBN</label>
                    <input
                        type="text"
                        name="isbn"
                        value="<?= htmlspecialchars((string)($book['isbn'] ?? '')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Author</label>
                    <select name="author_id">

                        <option value="">Select Author</option>

                        <?php foreach ($authors as $author): ?>
                            <option
                                value="<?= (int)$author['author_id'] ?>"
                                <?= (string)$book['author_id'] === (string)$author['author_id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($author['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">

                        <option value="">Select Category</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int)$category['category_id'] ?>"
                                <?= (string)$book['category_id'] === (string)$category['category_id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Publisher</label>
                    <input
                        type="text"
                        name="publisher"
                        value="<?= htmlspecialchars((string)($book['publisher'] ?? '')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Year Published</label>
                    <input
                        type="number"
                        name="year_published"
                        min="1000"
                        max="<?= date('Y') ?>"
                        value="<?= htmlspecialchars((string)($book['year_published'] ?? '')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Total Copies *</label>
                    <input
                        type="number"
                        name="total_copies"
                        min="1"
                        value="<?= (int)$book['total_copies'] ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Currently Available</label>
                    <input
                        type="number"
                        value="<?= (int)$book['available_copies'] ?>"
                        disabled
                    >
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Update Book
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