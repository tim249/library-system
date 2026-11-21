<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireAdmin();

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errorMessage = '';
$successMessage = '';
$editCategory = null;

// Search
$search = trim($_GET['search'] ?? '');

// Fetch category for editing
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId) {
        $stmt = $pdo->prepare("
            SELECT * FROM categories
            WHERE category_id = ?
        ");
        $stmt->execute([$editId]);
        $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editCategory) {
            $errorMessage = 'Category not found.';
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
        $description = trim($_POST['description'] ?? '');

        // ADD CATEGORY
        if ($action === 'create') {

            if ($name === '') {
                $errorMessage = 'Category name is required.';

            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (name, description)
                        VALUES (?, ?)
                    ");

                    $stmt->execute([
                        $name,
                        $description !== '' ? $description : null
                    ]);

                    header('Location: index.php?success=created');
                    exit;

                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errorMessage = 'A category with this name may already exist.';
                    } else {
                        $errorMessage = 'Unable to create category.';
                    }
                }
            }
        }

        // UPDATE CATEGORY
        elseif ($action === 'update') {

            $categoryId = filter_var(
                $_POST['category_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$categoryId || $name === '') {
                $errorMessage = 'Please provide a valid category name.';

            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE categories
                        SET name = ?, description = ?
                        WHERE category_id = ?
                    ");

                    $stmt->execute([
                        $name,
                        $description !== '' ? $description : null,
                        $categoryId
                    ]);

                    header('Location: index.php?success=updated');
                    exit;

                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errorMessage = 'A category with this name may already exist.';
                    } else {
                        $errorMessage = 'Unable to update category.';
                    }
                }
            }
        }

        // DELETE CATEGORY
        elseif ($action === 'delete') {

            $categoryId = filter_var(
                $_POST['category_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$categoryId) {
                $errorMessage = 'Invalid category selected.';

            } else {
                try {
                    $check = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM books
                        WHERE category_id = ?
                    ");

                    $check->execute([$categoryId]);

                    if ((int) $check->fetchColumn() > 0) {
                        $errorMessage = 'This category cannot be deleted because books are assigned to it.';

                    } else {
                        $stmt = $pdo->prepare("
                            DELETE FROM categories
                            WHERE category_id = ?
                        ");

                        $stmt->execute([$categoryId]);

                        header('Location: index.php?success=deleted');
                        exit;
                    }

                } catch (PDOException $exception) {
                    $errorMessage = 'Unable to delete category.';
                }
            }
        }
    }
}

// Success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $successMessage = 'Category successfully added.';
            break;

        case 'updated':
            $successMessage = 'Category successfully updated.';
            break;

        case 'deleted':
            $successMessage = 'Category successfully deleted.';
            break;
    }
}

// Search and fetch categories
$sql = "
    SELECT
        categories.category_id,
        categories.name,
        categories.description,
        COUNT(books.book_id) AS total_books
    FROM categories
    LEFT JOIN books
        ON categories.category_id = books.category_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE categories.name LIKE ?
        OR categories.description LIKE ?
    ";

    $term = "%{$search}%";
    $params = [$term, $term];
}

$sql .= "
    GROUP BY
        categories.category_id,
        categories.name,
        categories.description
    ORDER BY categories.category_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Shared layout settings
$pageTitle = 'Categories Management';

$extraCss = [
    'assets/css/admin-dashboard.css',
    'assets/css/category-management.css'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="categories-container">

    <div class="categories-header">

        <div>
            <h1>Categories Management</h1>
            <p>Organize and manage library book categories.</p>
        </div>

        <a href="../admin/dashboard.php" class="btn btn-secondary">
            Back to Dashboard
        </a>

    </div>

    <?php if ($successMessage): ?>
        <div class="success-message">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- ADD / EDIT CATEGORY FORM -->

    <div class="category-form-card">

        <h2>
            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
        </h2>

        <form method="POST" action="index.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <?php if ($editCategory): ?>

                <input type="hidden" name="action" value="update">

                <input
                    type="hidden"
                    name="category_id"
                    value="<?= e($editCategory['category_id']) ?>"
                >

            <?php else: ?>

                <input type="hidden" name="action" value="create">

            <?php endif; ?>

            <div class="category-form-grid">

                <div class="form-group">

                    <label for="name">Category Name *</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($editCategory['name'] ?? '') ?>"
                        placeholder="Enter category name"
                        maxlength="100"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Enter category description"
                    ><?= e($editCategory['description'] ?? '') ?></textarea>

                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    <?= $editCategory ? 'Update Category' : 'Add Category' ?>
                </button>

                <?php if ($editCategory): ?>

                    <a href="index.php" class="btn btn-secondary">
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>

    <!-- SEARCH -->

    <form method="GET" action="index.php" class="category-search">

        <input
            type="text"
            name="search"
            value="<?= e($search) ?>"
            placeholder="Search categories..."
        >

        <button type="submit" class="btn btn-primary">
            Search
        </button>

        <a href="index.php" class="btn btn-secondary">
            Reset
        </a>

    </form>

    <!-- CATEGORIES TABLE -->

    <div class="category-table-container">

        <h2>All Categories</h2>

        <table class="category-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Total Books</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($categories)): ?>

                <?php foreach ($categories as $category): ?>

                    <tr>

                        <td><?= e($category['category_id']) ?></td>

                        <td><?= e($category['name']) ?></td>

                        <td>
                            <?= e($category['description'] ?: 'No description') ?>
                        </td>

                        <td><?= e($category['total_books']) ?></td>

                        <td class="category-actions">

                            <a
                                href="index.php?edit=<?= e($category['category_id']) ?>"
                                class="btn btn-primary"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="index.php"
                                onsubmit="return confirm('Are you sure you want to delete this category?');"
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
                                    name="category_id"
                                    value="<?= e($category['category_id']) ?>"
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
                        No categories found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
