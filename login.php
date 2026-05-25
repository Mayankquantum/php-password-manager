<?php

require __DIR__ . '/bootstrap.php';

use App\User;

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = $_POST['login']    ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $result = (new User())->authenticate($login, $password);
        if ($result === null) {
            throw new RuntimeException('Invalid login or password.');
        }

        // Store identity and the unwrapped data KEY for this session.
        session_regenerate_id(true);
        $_SESSION['user_id']  = $result['id'];
        $_SESSION['login']    = $result['login'];
        $_SESSION['data_key'] = base64_encode($result['data_key']);

        header('Location: dashboard.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Log in';
require __DIR__ . '/partials/header.php';
?>

<div class="card narrow">
    <h1>Log in</h1>

    <?php if ($flash): ?>
        <p class="alert success"><?= e($flash) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <label>Login
            <input type="text" name="login" required value="<?= e($_POST['login'] ?? '') ?>">
        </label>
        <label>Master password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Log in</button>
    </form>

    <p class="muted">No account yet? <a href="register.php">Sign up</a></p>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
