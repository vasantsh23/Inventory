<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . asset_url('/inventory.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please enter both your login ID and password.';
        } elseif (attempt_login($username, $password, !empty($_POST['remember']))) {
            $redirect = $_SESSION['redirect_after_login'] ?? asset_url('/inventory.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            // Deliberately generic — never reveal whether the username
            // exists, whether the account is locked, etc.
            $error = 'Invalid login ID or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
    <section class="login-section">
        <h1>Sign in</h1>
        <p>Enter your login ID to access the Inventory module.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(asset_url('/login.php')) ?>" class="login-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="username">Login ID</label>
            <input type="text" id="username" name="username" required autofocus
                   value="<?= e($_POST['username'] ?? '') ?>">

            <label for="password">Password</label>
            <div class="password-field-wrap">
                <input type="password" id="password" name="password" required>
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

            <label class="remember-row" for="remember" style="margin-top:2px;">
                <input type="checkbox" id="remember" name="remember" value="1"
                       <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
                Remember me on this device for 30 days
            </label>

            <button type="submit">Log In</button>
        </form>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/login.js')) ?>"></script>
<?php
require_once __DIR__ . '/includes/footer.php';
