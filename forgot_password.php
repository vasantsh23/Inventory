<?php
/**
 * forgot_password.php
 * "Forgot password?" from the login screen: asks for a username or
 * email and e-mails a one-time reset link (see includes/password_reset.php).
 * Always shows the same confirmation, whether or not the account exists.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/password_reset.php';

if (is_logged_in()) {
    header('Location: ' . asset_url('/inventory.php'));
    exit;
}

$error = '';
$sent = false;
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($identifier === '') {
        $error = 'Please enter your username or email address.';
    } else {
        // Bots filling the hidden field get the same answer, but nothing is sent.
        if (trim((string)($_POST['website'] ?? '')) === '') {
            password_reset_request($identifier);
        }
        $sent = true;
    }
}

$useDefaultTheme = true; // same look as the login screen
require_once __DIR__ . '/includes/header.php';
?>
    <section class="login-section">
        <h1>Forgot password</h1>

        <?php if ($sent): ?>
            <div class="alert alert-success" role="status">
                If an account matches what you entered, we’ve sent a link to reset your password to the email address on that account.
                The link works for <?= PASSWORD_RESET_TTL_MINUTES ?> minutes. If it doesn’t arrive within a few minutes, please check your spam folder.
            </div>
            <p style="margin-top:18px;"><a class="btn" href="<?= e(asset_url('/login.php')) ?>">Back to sign in</a></p>
        <?php else: ?>
            <p>Enter your username or the email address on your account, and we’ll email you a link to choose a new password.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(asset_url('/forgot_password.php')) ?>" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="register-hp" aria-hidden="true">
                    <label for="website">Leave this empty</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <label for="identifier">Username or email</label>
                <input type="text" id="identifier" name="identifier" maxlength="254" required autofocus
                       autocomplete="username" value="<?= e($identifier) ?>">

                <button type="submit">Send reset link</button>
                <p class="login-back-link"><a href="<?= e(asset_url('/login.php')) ?>">Back to sign in</a></p>
            </form>
        <?php endif; ?>
    </section>
<?php
require_once __DIR__ . '/includes/footer.php';
