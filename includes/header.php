<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the project base URL.
$projectRoot = realpath(__DIR__ . '/..');
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);

$baseUrl = '';

if ($projectRoot !== false && $documentRoot !== false) {
    $baseUrl = str_replace(
        '\\',
        '/',
        substr($projectRoot, strlen($documentRoot))
    );
}

$baseUrl = rtrim($baseUrl, '/');

// Page settings.
$pageTitle = $pageTitle ?? 'Library Management System';
$extraCss = $extraCss ?? [];

// Escape HTML output.
if (!function_exists('layoutEscape')) {
    function layoutEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= layoutEscape($pageTitle) ?></title>

    <!-- Shared Layout CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/layout.css">

    <!-- Page-specific CSS -->
    <?php foreach ($extraCss as $cssFile): ?>
        <link rel="stylesheet"
              href="<?= $baseUrl ?>/<?= layoutEscape($cssFile) ?>">
    <?php endforeach; ?>

</head>

<body>