<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

// Redirect users who are already logged in.
if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'member') {
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Reject unknown roles.
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

// Process login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {

        $error = 'Invalid request. Please refresh the page.';

    } elseif ($username === '' || $password === '') {

        $error = 'Please fill in all fields.';

    } else {

        try {

            // Find the user.
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE username = ? LIMIT 1"
            );

            $stmt->execute([$username]);

            $user = $stmt->fetch();

            // Verify password.
            if ($user && password_verify($password, $user['password_hash'])) {

                // Regenerate session ID.
                session_regenerate_id(true);

                // Store user information.
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect according to account role.
                if ($user['role'] === 'admin') {

                    header('Location: ../admin/dashboard.php');
                    exit;

                } elseif ($user['role'] === 'member') {

                    header('Location: ../dashboard/index.php');
                    exit;

                } else {

                    session_unset();
                    session_destroy();

                    header('Location: login.php');
                    exit;
                }

            } else {

                $error = 'Invalid username or password.';

            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'Unable to process login. Please try again.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Management System | Login</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="bg-decor"></div>

<div class="login-card">

    <div class="app-logo">
        <i class="fa-solid fa-book-open"></i>
    </div>

    <h1>Welcome Back!</h1>

    <p>Library Management System</p>

    <?php if ($error !== ''): ?>

        <div class="error-msg">
            <?= clean($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="" style="width:100%;">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= clean(csrfToken()) ?>"
        >

        <div class="input-group">

            <i class="fa-solid fa-user"></i>

            <input
                type="text"
                name="username"
                placeholder="Username"
                autocomplete="username"
                required
            >

        </div>

        <div class="input-group">

            <i class="fa-solid fa-lock"></i>

            <input
                type="password"
                name="password"
                id="password"
                placeholder="Password"
                autocomplete="current-password"
                required
            >

            <i
                class="fa-solid fa-eye-slash toggle-password"
                id="togglePassword"
            ></i>

        </div>

        <button type="submit" class="login-btn">

            <i class="fa-solid fa-right-to-bracket"></i>

            Login

        </button>

    </form>

    <div class="register-link">

        <p>
            Don't have an account?
            <a href="register.php">Create Account</a>
        </p>

    </div>

    <div class="footer-text">

        Library Management System

        <br><br>

        Secure access for authorized librarians.

    </div>

</div>

<script src="../assets/js/login.js"></script>

</body>
</html>