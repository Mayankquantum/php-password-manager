<?php

require __DIR__ . '/bootstrap.php';

use App\User;

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = $_POST['login']    ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    try {
        if ($password !== $confirm) {
            throw new RuntimeException('Passwords do not match.');
        }
        (new User())->register($login, $password);
        $_SESSION['flash'] = 'Account created. You can now log in.';
        header('Location: login.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Sign up';
require __DIR__ . '/partials/header.php';
?>

<div class="card narrow">
    <h1>Create an account</h1>

    <?php if ($error): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <label>Login
            <input type="text" name="login" required maxlength="64" value="<?= e($_POST['login'] ?? '') ?>">
        </label>
        <label>Master password
            <input type="password" name="password" required>
        </label>
        <label>Confirm password
            <input type="password" name="confirm" required>
        </label>
        <button type="submit">Sign up</button>
    </form>

    <p class="muted">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
