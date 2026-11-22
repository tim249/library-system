<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../member/dashboard.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $memberNumber = trim($_POST['member_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $error = "Invalid request. Please refresh the page.";

    } elseif (
        $fullName === '' ||
        $memberNumber === '' ||
        $email === '' ||
        $username === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif ($password !== $confirmPassword) {
        $error = "Your passwords do not match.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $error = "This username is already taken.";

            } else {

                $stmt = $pdo->prepare("
                    SELECT member_id
                    FROM members
                    WHERE member_number = ? OR email = ?
                    LIMIT 1
                ");

                $stmt->execute([$memberNumber, $email]);

                if ($stmt->fetch()) {
                    $error = "This member number or email is already registered.";

                } else {

                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO members
                        (
                            member_number,
                            full_name,
                            email,
                            phone,
                            status,
                            date_joined
                        )
                        VALUES (?, ?, ?, ?, 'active', CURDATE())
                    ");

                    $stmt->execute([
                        $memberNumber,
                        $fullName,
                        $email,
                        $phone
                    ]);

                    $memberId = (int) $pdo->lastInsertId();

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $pdo->prepare("
                        INSERT INTO users
                        (
                            username,
                            password_hash,
                            full_name,
                            role,
                            member_id
                        )
                        VALUES (?, ?, ?, 'member', ?)
                    ");

                    $stmt->execute([
                        $username,
                        $passwordHash,
                        $fullName,
                        $memberId
                    ]);

                    $pdo->commit();

                    $success = "Account created successfully! You can now log in.";
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = "Unable to create your account. Please try again.";
        }
    }
}

require 'register-form.php';
?>

