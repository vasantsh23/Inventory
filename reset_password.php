<?php
/**
 * reset_password.php
 * Opened from the link in the "reset your password" email. Lets the
 * user choose a new password (same rule as registration). See
 * includes/password_reset.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/password_reset.php';

// The token is in the URL: never pass it on to other sites.
header('Referrer-Policy: no-referrer');

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$token = preg_match('/^[a-f0-9]{64}$/', $token) ? $token : '';
$error = '';
$done = isset($_GET['done']);

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $result = password_reset_complete($token, (string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
        if ($result === null) {
            // Signed out everywhere — including this browser, if it was signed in.
            if (is_logged_in()) {
                logout();
            }
            header('Location: ' . asset_url('/reset_password.php?done=1'), true, 303);
            exit;
        }
        $error = $result;
    }
}

$valid = !$done && $token !== '' && password_reset_find($token) !== null;

$useDefaultTheme = true; // same look as the login screen
require_once __DIR__ . '/includes/header.php';
?>
    <section class="login-section">
        <h1>Choose a new password</h1>

        <?php if ($done): ?>
            <div class="alert alert-success" role="status">
                Your password has been changed. You can now sign in with your new password.
            </div>
            <p style="margin-top:18px;"><a class="btn" href="<?= e(asset_url('/login.php')) ?>">Sign in</a></p>

        <?php elseif (!$valid): ?>
            <div class="alert alert-error" role="alert">
                This reset link is invalid, has expired or has already been used. Reset links work once, for <?= PASSWORD_RESET_TTL_MINUTES ?> minutes.
            </div>
            <p style="margin-top:18px;">
                <a class="btn" href="<?= e(asset_url('/forgot_password.php')) ?>">Request a new link</a>
            </p>

        <?php else: ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(asset_url('/reset_password.php')) ?>" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <label for="password">New password</label>
                <div class="password-field-wrap">
                    <input type="password" id="password" name="password" maxlength="1024" required autofocus
                           autocomplete="new-password" aria-describedby="pw-hint">
                    <button type="button" class="password-toggle-btn" id="togglePassword" aria-label="Show password">
                        <svg id="eyeIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                        <svg id="eyeOffIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display:none;">
                            <path d="M3 3l18 18M10.6 10.7a3.2 3.2 0 0 0 4.5 4.5M7.4 7.5C4.6 9.1 3 12 3 12s4 7 11 7c1.9 0 3.5-.5 4.9-1.2M17.9 17.1C20.1 15.4 21 12 21 12s-2.2-3.9-6.2-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <small class="register-hint" id="pw-hint">At least <?= REGISTRATION_PASSWORD_MIN ?> characters, with a letter and a number.</small>

                <label for="password_confirm" style="margin-top:12px;">Confirm new password</label>
                <input type="password" id="password_confirm" name="password_confirm" maxlength="1024" required autocomplete="new-password">

                <button type="submit">Change password</button>
            </form>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/login.js')) ?>"></script>
<?php
require_once __DIR__ . '/includes/footer.php';
