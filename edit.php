<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only logged-in members can access this page.
requireMember();

// Get the reading-list entry ID.
$readingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $readingId = filter_input(INPUT_POST, 'reading_id', FILTER_VALIDATE_INT);
}

if (!$readingId || $readingId < 1) {
    $_SESSION['error'] = 'Invalid reading-list entry.';
    header('Location: index.php');
    exit;
}

$error = '';
$entry = null;

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

    // Confirm that this entry belongs to the logged-in member.
    $stmt = $pdo->prepare(
        'SELECT
            rl.reading_id,
            rl.reading_status,
            rl.notes,
            b.title,
            a.name AS author_name
         FROM reading_lists rl
         INNER JOIN books b ON rl.book_id = b.book_id
         LEFT JOIN authors a ON b.author_id = a.author_id
         WHERE rl.reading_id = :reading_id
           AND rl.member_id = :member_id
         LIMIT 1'
    );

    $stmt->execute([
        'reading_id' => $readingId,
        'member_id' => $memberId
    ]);

    $entry = $stmt->fetch();

    if (!$entry) {
        $_SESSION['error'] = 'Reading-list entry not found.';
        header('Location: index.php');
        exit;
    }

    // Process submitted changes.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';

        $readingStatus = trim($_POST['reading_status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Preserve submitted values.
        $entry['reading_status'] = $readingStatus;
        $entry['notes'] = $notes;

        if (!verifyCsrfToken($token)) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $allowedStatuses = [
                'Want to Read',
                'Reading',
                'Completed'
            ];

            if (!in_array($readingStatus, $allowedStatuses, true)) {
                $error = 'Please select a valid reading status.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE reading_lists
                     SET reading_status = :reading_status,
                         notes = :notes
                     WHERE reading_id = :reading_id
                       AND member_id = :member_id'
                );

                $update->execute([
                    'reading_status' => $readingStatus,
                    'notes' => $notes !== '' ? $notes : null,
                    'reading_id' => $readingId,
                    'member_id' => $memberId
                ]);

                $_SESSION['success'] = 'Your reading-list entry was updated successfully.';

                header('Location: index.php');
                exit;
            }
        }
    }

} catch (PDOException $e) {
    error_log('Reading list edit error: ' . $e->getMessage());
    $error = 'A database error occurred. Please try again.';
}

// Shared layout settings.
$pageTitle = 'Edit Reading List';
$extraCss = ['assets/css/reading-list.css'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">

    <div class="header">
        <h1>Edit Reading List</h1>

        <a href="index.php" class="back-link">
            Back to My Reading List
        </a>
    </div>

    <div class="panel">

        <?php if ($error !== ''): ?>
            <div class="message error">
                <?= clean($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($entry): ?>

            <h2><?= clean($entry['title']) ?></h2>

            <p class="welcome">
                Author: <?= clean($entry['author_name'] ?? 'Unknown author') ?>
            </p>

            <form
                method="POST"
                action="edit.php?id=<?= (int) $readingId ?>"
                class="edit-form"
            >

                <input
                    type="hidden"
                    name="reading_id"
                    value="<?= (int) $readingId ?>"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= clean(csrfToken()) ?>"
                >

                <label for="reading_status">Reading Status</label>

                <select name="reading_status" id="reading_status" required>

                    <option
                        value="Want to Read"
                        <?= $entry['reading_status'] === 'Want to Read' ? 'selected' : '' ?>
                    >
                        Want to Read
                    </option>

                    <option
                        value="Reading"
                        <?= $entry['reading_status'] === 'Reading' ? 'selected' : '' ?>
                    >
                        Reading
                    </option>

                    <option
                        value="Completed"
                        <?= $entry['reading_status'] === 'Completed' ? 'selected' : '' ?>
                    >
                        Completed
                    </option>

                </select>

                <label for="notes">Personal Notes</label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="5"
                    placeholder="Write your thoughts or notes about this book..."
                ><?= clean($entry['notes'] ?? '') ?></textarea>

                <button type="submit" class="update-btn">
                    Save Changes
                </button>

                <a href="index.php" class="back-link">
                    Cancel
                </a>

            </form>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
