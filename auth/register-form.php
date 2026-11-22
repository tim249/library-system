<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Account | Library Management System</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link rel="stylesheet" href="../assets/css/register.css">

</head>

<body>

<div class="register-card">

<div class="app-logo">
    <i class="fa-solid fa-book-open"></i>
</div>

<h1>Create Your Account</h1>

<p class="subtitle">
    Join the Library Management System
</p>

<?php if ($error !== ''): ?>
    <div class="error-msg">
        <?= clean($error) ?>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>

    <div class="success-msg">
        <?= clean($success) ?>
    </div>

    <p class="login-link">
        <a href="login.php">Continue to Login</a>
    </p>

<?php else: ?>

    <form method="POST" action="register.php" id="registerForm">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= clean(csrfToken()) ?>"
        >

        <div class="input-group">
            <label for="full_name">Full Name *</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= clean($_POST['full_name'] ?? '') ?>"
                placeholder="Enter your full name"
                maxlength="150"
                required
            >
        </div>

        <div class="input-group">
            <label for="member_number">Student / Member Number *</label>
            <input
                type="text"
                id="member_number"
                name="member_number"
                value="<?= clean($_POST['member_number'] ?? '') ?>"
                placeholder="Enter your membership number"
                required
            >
        </div>

        <div class="input-group">
            <label for="email">Email Address *</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= clean($_POST['email'] ?? '') ?>"
                placeholder="Enter your email"
                required
            >
        </div>

        <div class="input-group">
            <label for="phone">Phone Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= clean($_POST['phone'] ?? '') ?>"
                placeholder="Enter your phone number"
            >
        </div>

        <div class="input-group">
            <label for="username">Username *</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= clean($_POST['username'] ?? '') ?>"
                placeholder="Choose a username"
                maxlength="100"
                required
            >
        </div>

        <div class="input-group">
            <label for="password">Password *</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 8 characters"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="input-group">
            <label for="confirm_password">Confirm Password *</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm your password"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="register-btn">
            <i class="fa-solid fa-user-plus"></i>
            Create Account
        </button>

    </form>

    <p class="login-link">
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

<?php endif; ?>

</div>

<script src="../assets/js/register.js"></script>

</body>
</html>
