<?php

require __DIR__ . '/bootstrap.php';

use App\User;

require_login();

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm']      ?? '';

    try {
        if ($new !== $confirm) {
            throw new RuntimeException('New passwords do not match.');
        }
        (new User())->changeMasterPassword((int) $_SESSION['user_id'], $old, $new);
        $success = 'Master password changed. Your data KEY was re-wrapped — all saved passwords are intact.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Change password';
require __DIR__ . '/partials/header.php';
?>

<div class="card narrow">
    <h1>Change master password</h1>

    <?php if ($success): ?>
        <p class="alert success"><?= e($success) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <label>Current password
            <input type="password" name="old_password" required>
        </label>
        <label>New password
            <input type="password" name="new_password" required>
        </label>
        <label>Confirm new password
            <input type="password" name="confirm" required>
        </label>
        <button type="submit">Change password</button>
    </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
