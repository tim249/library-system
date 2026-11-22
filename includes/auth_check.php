<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check whether a user is logged in.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
}

/**
 * Allow administrators only.
 */
function requireAdmin(): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ../dashboard/index.php');
        exit;
    }
}

/**
 * Allow members only.
 */
function requireMember(): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== 'member') {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}