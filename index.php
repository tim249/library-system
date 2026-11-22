<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Administrator access only
requireAdmin();

// Escape output
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errorMessage = '';
$successMessage = '';
$editAuthor = null;

// Search
$search = trim($_GET['search'] ?? '');

// Fetch author for editing
if (isset($_GET['edit'])) {

    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId) {

        $stmt = $pdo->prepare("
            SELECT * FROM authors
            WHERE author_id = ?
        ");

        $stmt->execute([$editId]);
        $editAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editAuthor) {
            $errorMessage = 'Author not found.';
        }

    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $errorMessage = 'Invalid form submission. Please try again.';

    } else {

        $action = $_POST['action'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // ADD AUTHOR
        if ($action === 'create') {

            if ($name === '') {

                $errorMessage = 'Author name is required.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        INSERT INTO authors (name, country)
                        VALUES (?, ?)
                    ");

                    $stmt->execute([
                        $name,
                        $country !== '' ? $country : null
                    ]);

                    header('Location: index.php?success=created');
                    exit;

                } catch (PDOException $exception) {

                    $errorMessage = 'Unable to add author. Please try again.';

                }

            }

        }

        // UPDATE AUTHOR
        elseif ($action === 'update') {

            $authorId = filter_var(
                $_POST['author_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$authorId || $name === '') {

                $errorMessage = 'Please provide a valid author name.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE authors
                        SET name = ?, country = ?
                        WHERE author_id = ?
                    ");

                    $stmt->execute([
                        $name,
                        $country !== '' ? $country : null,
                        $authorId
                    ]);

                    header('Location: index.php?success=updated');
                    exit;

                } catch (PDOException $exception) {

                    $errorMessage = 'Unable to update author. Please try again.';

                }

            }

        }

        // DELETE AUTHOR
        elseif ($action === 'delete') {

            $authorId = filter_var(
                $_POST['author_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$authorId) {

                $errorMessage = 'Invalid author selected.';

            } else {

                try {

                    // Check whether books are assigned to this author
                    $check = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM books
                        WHERE author_id = ?
                    ");

                    $check->execute([$authorId]);

                    if ((int) $check->fetchColumn() > 0) {

                        $errorMessage = 'This author cannot be deleted because books are assigned to them.';

                    } else {

                        $stmt = $pdo->prepare("
                            DELETE FROM authors
                            WHERE author_id = ?
                        ");

                        $stmt->execute([$authorId]);

                        header('Location: index.php?success=deleted');
                        exit;

                    }

                } catch (PDOException $exception) {

                    $errorMessage = 'Unable to delete author.';

                }

            }

        }

    }

}

// Success messages
if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case 'created':
            $successMessage = 'Author successfully added.';
            break;

        case 'updated':
            $successMessage = 'Author successfully updated.';
            break;

        case 'deleted':
            $successMessage = 'Author successfully deleted.';
            break;

    }

}

// Fetch authors and count their books
$sql = "
    SELECT
        authors.author_id,
        authors.name,
        authors.country,
        COUNT(books.book_id) AS total_books
    FROM authors
    LEFT JOIN books
        ON authors.author_id = books.author_id
";

$params = [];

if ($search !== '') {

    $sql .= "
        WHERE authors.name LIKE ?
        OR authors.country LIKE ?
    ";

    $term = "%{$search}%";
    $params = [$term, $term];

}

$sql .= "
    GROUP BY
        authors.author_id,
        authors.name,
        authors.country
    ORDER BY authors.author_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Shared layout settings
$pageTitle = 'Authors Management | Library System';

$extraCss = [
    'assets/css/admin-dashboard.css',
    'assets/css/author-management.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="main-content">

    <div class="authors-container">

        <!-- PAGE HEADER -->
        <div class="authors-header">

            <div>
                <h1>Authors Management</h1>
                <p>Add, update and manage library authors.</p>
            </div>

            <a href="../admin/dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>

        </div>

        <!-- SUCCESS MESSAGE -->
        <?php if ($successMessage): ?>

            <div class="success-message">
                <?= e($successMessage) ?>
            </div>

        <?php endif; ?>

        <!-- ERROR MESSAGE -->
        <?php if ($errorMessage): ?>

            <div class="error-message">
                <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- ADD / EDIT AUTHOR FORM -->
        <div class="author-form-card">

            <h2>
                <?= $editAuthor ? 'Edit Author' : 'Add New Author' ?>
            </h2>

            <form method="POST" action="index.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <?php if ($editAuthor): ?>

                    <input type="hidden" name="action" value="update">

                    <input
                        type="hidden"
                        name="author_id"
                        value="<?= e($editAuthor['author_id']) ?>"
                    >

                <?php else: ?>

                    <input type="hidden" name="action" value="create">

                <?php endif; ?>

                <div class="author-form-grid">

                    <div class="form-group">

                        <label for="name">Author Name *</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($editAuthor['name'] ?? '') ?>"
                            placeholder="Enter author's full name"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="country">Country</label>

                        <input
                            type="text"
                            id="country"
                            name="country"
                            value="<?= e($editAuthor['country'] ?? '') ?>"
                            placeholder="Enter author's country"
                            maxlength="100"
                        >

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn btn-primary">
                        <?= $editAuthor ? 'Update Author' : 'Add Author' ?>
                    </button>

                    <?php if ($editAuthor): ?>

                        <a href="index.php" class="btn btn-secondary">
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

        <!-- SEARCH AUTHORS -->
        <form method="GET" action="index.php" class="author-search">

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search by author name or country..."
            >

            <button type="submit" class="btn btn-primary">
                Search
            </button>

            <a href="index.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

        <!-- AUTHORS TABLE -->
        <div class="author-table-container">

            <h2>All Authors</h2>

            <table class="author-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Author Name</th>
                        <th>Country</th>
                        <th>Total Books</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($authors)): ?>

                        <?php foreach ($authors as $author): ?>

                            <tr>

                                <td><?= e($author['author_id']) ?></td>

                                <td><?= e($author['name']) ?></td>

                                <td>
                                    <?= e($author['country'] ?: 'Not specified') ?>
                                </td>

                                <td><?= e($author['total_books']) ?></td>

                                <td class="author-actions">

                                    <a
                                        href="index.php?edit=<?= e($author['author_id']) ?>"
                                        class="btn btn-primary"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="index.php"
                                        onsubmit="return confirm('Are you sure you want to delete this author?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="author_id"
                                            value="<?= e($author['author_id']) ?>"
                                        >

                                        <button type="submit" class="btn btn-danger">
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty-message">
                                No authors found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>