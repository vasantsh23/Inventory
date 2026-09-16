<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_verify($_POST['csrf_token'] ?? null)) {
        $keepDevice = ($_POST['action'] ?? '') === 'keep_device';
        logout(!$keepDevice);
    }
    header('Location: ' . asset_url('/index.php'));
    exit;
}

if (!is_logged_in()) {
    header('Location: ' . asset_url('/index.php'));
    exit;
}

$username = current_user()['username'];

require_once __DIR__ . '/includes/header.php';
?>
    <section class="login-section">
        <h1>Log out</h1>
        <p>Choose how you'd like to sign out of <strong><?= e($username) ?></strong>'s account on this device.</p>

        <form method="post" action="<?= e(asset_url('/logout.php')) ?>" style="margin-top:20px;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="full">
            <button type="submit" class="btn-primary" style="width:100%;">Log out</button>
            <p class="hint">Ends your session and forgets this device. You'll need your password next time.</p>
        </form>

        <form method="post" action="<?= e(asset_url('/logout.php')) ?>" style="margin-top:22px;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="keep_device">
            <button type="submit" class="btn" style="width:100%;">Log out, but keep me signed in on this device</button>
            <p class="hint">Ends this session, but you'll be signed back in automatically next time you visit here — until you clear cookies.</p>
        </form>

        <p style="margin-top:22px; text-align:center;">
            <a href="<?= e(asset_url('/index.php')) ?>">Cancel and go back</a>
        </p>
    </section>
<?php
require_once __DIR__ . '/includes/footer.php';
