<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'guest';
$fullName = $_SESSION['full_name'] ?? 'Guest';
?>

<nav class="main-navbar">

    <!-- Brand -->
    <a href="<?= $baseUrl ?>/index.php" class="navbar-brand">
        Library System
    </a>

    <!-- Navigation Links -->
    <ul class="navbar-links">

        <?php if ($role === 'admin'): ?>

            <li>
                <a href="<?= $baseUrl ?>/admin/dashboard.php">Dashboard</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/books/index.php">Books</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/authors/index.php">Authors</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/categories/index.php">Categories</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/loans/index.php">Loans</a>
            </li>

        <?php elseif ($role === 'member'): ?>

            <li>
                <a href="<?= $baseUrl ?>/dashboard/index.php">Dashboard</a>
            </li>

            <li>
            <a href="<?= $baseUrl ?>/books/browse.php">Browse Books</a></li>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/members/index.php">My Reading List</a>
            </li>

        <?php else: ?>

            <li>
                <a href="<?= $baseUrl ?>/index.php">Home</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/auth/login.php">Login</a>
            </li>

            <li>
                <a href="<?= $baseUrl ?>/auth/register.php">Register</a>
            </li>

        <?php endif; ?>

    </ul>

    <!-- User Information -->
    <?php if ($role !== 'guest'): ?>

        <div class="navbar-user">

            <span>
                Welcome, <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="<?= $baseUrl ?>/auth/logout.php" class="navbar-logout">
                Logout
            </a>

        </div>

    <?php endif; ?>

</nav>