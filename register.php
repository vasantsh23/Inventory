<?php
/**
 * register.php
 * Self-registration form, opened from the Register button on the
 * login screen. Only available while setup.registration = 'yes'.
 * Logic lives in includes/registration.php.
 *
 * Spam protection (same approach as the website contact form): CSRF
 * token, a hidden honeypot field, and a minimum fill time.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/registration.php';

if (is_logged_in()) {
    header('Location: ' . asset_url('/inventory.php'));
    exit;
}

const REGISTRATION_MIN_FILL_SECONDS = 3;

$enabled = registration_enabled();
$fields = registration_fields();
$errors = [];
$old = [];
$done = null;

// Result of a successful registration, shown once after the redirect.
if (isset($_SESSION['registration_done'])) {
    $done = $_SESSION['registration_done'];
    unset($_SESSION['registration_done']);
}

if ($enabled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $started = (int)($_SESSION['registration_t'] ?? 0);
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
        $old = $_POST;
    } elseif (trim((string)($_POST['website'] ?? '')) !== '' || $started === 0 || time() - $started < REGISTRATION_MIN_FILL_SECONDS) {
        // Bot: honeypot filled or submitted too fast. Pretend success, store nothing.
        unset($_SESSION['registration_t']);
        $_SESSION['registration_done'] = ['user' => true];
        header('Location: ' . asset_url('/register.php'), true, 303);
        exit;
    } else {
        [$values, $errors] = registration_validate($_POST);
        $old = $values;
        if ($errors === []) {
            try {
                registration_create_user($values);
                $mail = registration_send_mails($values);
                unset($_SESSION['registration_t']);
                $_SESSION['registration_done'] = ['user' => $mail['user']];
                header('Location: ' . asset_url('/register.php'), true, 303);
                exit;
            } catch (InvalidArgumentException $e) {
                $errors['form'] = $e->getMessage();
            } catch (Throwable $e) {
                error_log('registration failed: ' . $e->getMessage());
                $errors['form'] = 'Your registration could not be saved right now. Please try again later.';
            }
        } else {
            $errors['form'] = 'Please correct the highlighted fields.';
        }
    }
}
unset($old['password']); // never echo a password back into the form

// Start the fill-time clock when the form is first shown. Not restarted
// after a failed submit, so correcting a mistake and resubmitting
// quickly isn't mistaken for a bot.
if ($enabled && $done === null && empty($_SESSION['registration_t'])) {
    $_SESSION['registration_t'] = time();
}

$useDefaultTheme = true; // same look as the login screen
require_once __DIR__ . '/includes/header.php';
?>
    <section class="login-section register-section">
        <h1>Register</h1>

        <?php if (!$enabled): ?>
            <p>Online registration is currently closed. Please contact us to open an account.</p>
            <p><a href="<?= e(asset_url('/login.php')) ?>">Back to sign in</a></p>

        <?php elseif ($done !== null): ?>
            <div class="alert alert-success" role="status">
                Thank you, your registration has been received. Your account is now awaiting approval, and you’ll be able to log in once it has been approved.
                <?php if (!empty($done['user'])): ?>
                    A confirmation has been sent to your email address.
                <?php endif; ?>
            </div>
            <p style="margin-top:18px;"><a class="btn" href="<?= e(asset_url('/login.php')) ?>">Back to sign in</a></p>

        <?php else: ?>
            <p>Fields marked <span class="req" aria-hidden="true">*</span> are required. Enter a Telephone or GSM number (at least one).</p>

            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(asset_url('/register.php')) ?>" class="login-form register-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="register-hp" aria-hidden="true">
                    <label for="website">Leave this empty</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="register-grid">
                    <?php foreach ($fields as $name => [$label, $required, $max, $type]):
                        $id = 'reg-' . $name;
                        $isPhone = in_array($name, ['telephone', 'gsm'], true);
                        $err = $errors[$name] ?? '';
                        $autocomplete = [
                            'username' => 'username', 'emailid' => 'email', 'password' => 'new-password',
                            'company' => 'organization', 'fname' => 'given-name', 'lname' => 'family-name',
                            'address-1' => 'address-line1', 'address-2' => 'address-line2', 'address-3' => 'address-line3',
                            'telephone' => 'tel', 'gsm' => 'tel',
                        ][$name] ?? 'off';
                    ?>
                        <div class="register-field<?= $err !== '' ? ' has-error' : '' ?>">
                            <label for="<?= e($id) ?>">
                                <?= e($label) ?><?php if ($required): ?> <span class="req" aria-hidden="true">*</span><?php elseif ($isPhone): ?> <span class="req" aria-hidden="true">†</span><?php endif; ?>
                            </label>
                            <?php if ($name === 'password'): ?>
                                <div class="password-field-wrap">
                                    <input type="password" id="password" name="password" maxlength="<?= (int)$max ?>"
                                           autocomplete="new-password" required
                                           aria-describedby="reg-password-hint<?= $err !== '' ? ' reg-password-err' : '' ?>">
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
                                <small class="register-hint" id="reg-password-hint">At least <?= REGISTRATION_PASSWORD_MIN ?> characters, with a letter and a number.</small>
                            <?php else: ?>
                                <input type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>"
                                       maxlength="<?= (int)$max ?>" autocomplete="<?= e($autocomplete) ?>"
                                       value="<?= e((string)($old[$name] ?? '')) ?>"
                                       <?= $required ? 'required' : '' ?>
                                       <?= $err !== '' ? 'aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>>
                            <?php endif; ?>
                            <?php if ($err !== ''): ?>
                                <small class="register-error" id="<?= e($name === 'password' ? 'reg-password-err' : $id . '-err') ?>"><?= e($err) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="register-actions">
                    <button type="submit">Register</button>
                    <a class="btn" href="<?= e(asset_url('/login.php')) ?>">Back to sign in</a>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/login.js')) ?>"></script>
<?php
require_once __DIR__ . '/includes/footer.php';
