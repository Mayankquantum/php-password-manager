<?php /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Password Manager') ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <span class="brand">🔐 Password Manager</span>
    <?php if (is_logged_in()): ?>
        <nav>
            <span class="user">Signed in as <strong><?= e($_SESSION['login']) ?></strong></span>
            <a href="dashboard.php">Vault</a>
            <a href="change_password.php">Change password</a>
            <a href="logout.php">Log out</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
