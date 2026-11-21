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

$error = '';
$success = '';

// Fetch authors.
$authors = $pdo->query(
    "SELECT author_id, name FROM authors ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories.
$categories = $pdo->query(
    "SELECT category_id, name FROM categories ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Form submission.
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

        // Validate inputs.
        if ($title === '') {
            $error = "Book title is required.";

        } elseif ($totalCopies === false || $totalCopies < 1) {
            $error = "Total copies must be at least 1.";

        } elseif (
            $year !== '' &&
            (!ctype_digit($year) || (int)$year < 1000 ||
            (int)$year > (int)date('Y'))
        ) {
            $error = "Please enter a valid publication year.";

        } else {

            $authorId = $authorId === '' ? null : filter_var(
                $authorId,
                FILTER_VALIDATE_INT
            );

            $categoryId = $categoryId === '' ? null : filter_var(
                $categoryId,
                FILTER_VALIDATE_INT
            );

            if (
                ($authorId !== null && ($authorId === false || $authorId < 1)) ||
                ($categoryId !== null && ($categoryId === false || $categoryId < 1))
            ) {
                $error = "Please select valid author and category values.";
            } else {

                // Confirm selected author and category exist.
                if ($authorId !== null) {
                    $check = $pdo->prepare(
                        "SELECT COUNT(*) FROM authors WHERE author_id = ?"
                    );
                    $check->execute([$authorId]);

                    if ((int)$check->fetchColumn() === 0) {
                        $error = "Selected author does not exist.";
                    }
                }

                if ($error === '' && $categoryId !== null) {
                    $check = $pdo->prepare(
                        "SELECT COUNT(*) FROM categories WHERE category_id = ?"
                    );
                    $check->execute([$categoryId]);

                    if ((int)$check->fetchColumn() === 0) {
                        $error = "Selected category does not exist.";
                    }
                }

                if ($error === '') {
                    try {

                        $sql = "
                            INSERT INTO books (
                                isbn,
                                title,
                                author_id,
                                category_id,
                                publisher,
                                year_published,
                                total_copies,
                                available_copies
                            ) VALUES (
                                :isbn,
                                :title,
                                :author_id,
                                :category_id,
                                :publisher,
                                :year_published,
                                :total_copies,
                                :available_copies
                            )
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
                            'available_copies' => $totalCopies
                        ]);

                        header("Location: index.php");
                        exit;

                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            $error = "This ISBN may already exist. Please check it.";
                        } else {
                            $error = "Unable to save the book. Please check your database fields.";
                        }
                    }
                }
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

    <title>Add New Book | Library System</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
   <link rel="stylesheet" href="../assets/css/book-management.css">
</head>

<body>

<div class="form-container">

    <div class="form-card">

        <h2>Add New Book</h2>

        <p>Enter the details of the book you want to register.</p>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="create.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrfToken()) ?>"
            >

            <div class="form-grid">

                <div class="form-group">
                    <label>Book Title <span class="required">*</span></label>
                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>ISBN</label>
                    <input
                        type="text"
                        name="isbn"
                        value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Author</label>
                    <select name="author_id">
                        <option value="">Select Author</option>

                        <?php foreach ($authors as $author): ?>
                            <option
                                value="<?= (int)$author['author_id'] ?>"
                                <?= (($_POST['author_id'] ?? '') == $author['author_id']) ? 'selected' : '' ?>
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
                                <?= (($_POST['category_id'] ?? '') == $category['category_id']) ? 'selected' : '' ?>
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
                        value="<?= htmlspecialchars($_POST['publisher'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Year Published</label>
                    <input
                        type="number"
                        name="year_published"
                        min="1000"
                        max="<?= date('Y') ?>"
                        value="<?= htmlspecialchars($_POST['year_published'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Total Copies <span class="required">*</span></label>
                    <input
                        type="number"
                        name="total_copies"
                        min="1"
                        value="<?= htmlspecialchars($_POST['total_copies'] ?? '1') ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Save Book
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