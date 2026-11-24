<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/registration.php'; // registration_enabled()

// ?next=/some/page.php (e.g. from the Diamond Jewellery pages in
// sample-module/): go back there after signing in. Only a path on this
// site is accepted, never another website.
$next = (string)($_GET['next'] ?? '');
$nextOk = $next !== '' && $next[0] === '/' && !str_starts_with($next, '//')
    && !preg_match('/[\s\\\\]/', $next) && strlen($next) <= 500;

if (is_logged_in()) {
    header('Location: ' . ($nextOk ? $next : asset_url('/inventory.php')));
    exit;
}
if ($nextOk) {
    $_SESSION['redirect_after_login'] = $next;
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
            $redirect = (string)($_SESSION['redirect_after_login'] ?? '');
            unset($_SESSION['redirect_after_login']);
            // Admins (and Super Admins) always start on their dashboard —
            // unless the login interrupted them on an admin page (e.g. the
            // session expired), in which case they go back to that page.
            $level = (int)(current_user()['level'] ?? 0);
            if ($level >= 8
                && !str_starts_with($redirect, asset_url('/modules/admin/'))
                && !str_starts_with($redirect, asset_url('/modules/superadmin/'))) {
                $redirect = asset_url($level >= 9 ? '/modules/superadmin/index.php' : '/modules/admin/index.php');
            }
            if ($redirect === '') {
                $redirect = asset_url('/inventory.php');
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            // Deliberately generic — never reveal whether the username
            // exists, whether the account is locked, etc.
            $error = 'Invalid login ID or password.';
        }
    }
}

// The login screen always uses the default theme, not Admin → Theme Settings
$useDefaultTheme = true;
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
            <div class="forgot-password-row">
                <a href="<?= e(asset_url('/forgot_password.php')) ?>">Forgot password?</a>
            </div>

            <label class="remember-row" for="remember" style="margin-top:2px;">
                <input type="checkbox" id="remember" name="remember" value="1"
                       <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
                Remember me on this device for 30 days
            </label>

            <button type="submit">Log In</button>

            <?php if (registration_enabled()): ?>
                <div class="login-register">
                    <span>New customer?</span>
                    <a class="btn login-register-btn" href="<?= e(asset_url('/register.php')) ?>">Register</a>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/login.js')) ?>"></script>
<?php
require_once __DIR__ . '/includes/footer.php';
