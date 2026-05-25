<?php

require __DIR__ . '/bootstrap.php';

use App\PasswordGenerator;
use App\PasswordEntry;

require_login();

$userId   = (int) $_SESSION['user_id'];
$dataKey  = session_data_key();
$entries  = new PasswordEntry();

$error      = null;
$generated  = $_POST['generated'] ?? '';   // carried into the save form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'generate') {
            $gen = new PasswordGenerator(
                (int) ($_POST['lowercase'] ?? 0),
                (int) ($_POST['uppercase'] ?? 0),
                (int) ($_POST['digits']    ?? 0),
                (int) ($_POST['special']   ?? 0),
                ($_POST['length'] ?? '') !== '' ? (int) $_POST['length'] : null
            );
            $generated = $gen->generate();
        } elseif ($action === 'save') {
            $entries->add(
                $userId,
                $_POST['service_name'] ?? '',
                $_POST['secret'] ?? '',
                $dataKey
            );
            header('Location: dashboard.php');
            exit;
        } elseif ($action === 'delete') {
            $entries->delete((int) ($_POST['id'] ?? 0), $userId);
            header('Location: dashboard.php');
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$saved = $entries->listForUser($userId, $dataKey);

$title = 'Vault';
require __DIR__ . '/partials/header.php';
?>

<?php if ($error): ?>
    <p class="alert error"><?= e($error) ?></p>
<?php endif; ?>

<div class="grid">

    <!-- ----------------------------------------------------------------- -->
    <!-- Password generator                                                -->
    <!-- ----------------------------------------------------------------- -->
    <div class="card">
        <h2>Generate a password</h2>
        <form method="post">
            <input type="hidden" name="action" value="generate">
            <div class="row">
                <label>Lowercase <input type="number" name="lowercase" min="0" value="<?= e($_POST['lowercase'] ?? '2') ?>"></label>
                <label>Uppercase <input type="number" name="uppercase" min="0" value="<?= e($_POST['uppercase'] ?? '3') ?>"></label>
            </div>
            <div class="row">
                <label>Digits <input type="number" name="digits" min="0" value="<?= e($_POST['digits'] ?? '2') ?>"></label>
                <label>Special <input type="number" name="special" min="0" value="<?= e($_POST['special'] ?? '2') ?>"></label>
            </div>
            <label>Total length (optional — pads if larger than the sum)
                <input type="number" name="length" min="1" value="<?= e($_POST['length'] ?? '') ?>">
            </label>
            <button type="submit">Generate</button>
        </form>

        <?php if ($generated !== ''): ?>
            <div class="generated">
                <span class="mono"><?= e($generated) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ----------------------------------------------------------------- -->
    <!-- Save a credential                                                 -->
    <!-- ----------------------------------------------------------------- -->
    <div class="card">
        <h2>Save a credential</h2>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <label>Service / website name
                <input type="text" name="service_name" required maxlength="128" placeholder="e.g. Gmail, Facebook">
            </label>
            <label>Password (paste the generated one or type your own)
                <input type="text" name="secret" required value="<?= e($generated) ?>">
            </label>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<!-- --------------------------------------------------------------------- -->
<!-- Saved credentials                                                     -->
<!-- --------------------------------------------------------------------- -->
<div class="card">
    <h2>Saved passwords</h2>

    <?php if (empty($saved)): ?>
        <p class="muted">Nothing saved yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Service</th><th>Password</th><th>Saved at</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($saved as $row): ?>
                <tr>
                    <td><?= e($row['service_name']) ?></td>
                    <td class="mono"><?= e($row['password']) ?></td>
                    <td class="muted"><?= e($row['created_at']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this entry?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <button type="submit" class="link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
