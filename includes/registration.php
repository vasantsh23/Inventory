<?php
/**
 * registration.php
 * Self-registration from the login screen (register.php).
 *
 * - Shown only when setup.registration = 'yes'.
 * - Required: username, email, password, company, first name, last
 *   name, address 1, and telephone OR GSM.
 * - Email is validated and checked against the user table (via the
 *   emailid_hash lookup column); the username is checked too.
 * - On success a row is inserted into `user` (approval = 'pending',
 *   default user type), stored exactly like Admin > User stores it:
 *   bcrypt password, AES-encrypted email/addresses/phones, HMAC email
 *   hash. The user type is set explicitly (see
 *   registration_default_usertype_id()). Then one e-mail goes to the new user and one to the admin
 *   address in setup.`Admin emailid`.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

const REGISTRATION_PASSWORD_MIN = 8;

/**
 * Form fields, in screen order: name => [label, required, max length, input type].
 * 'required' for telephone/gsm is handled as "one of the two".
 */
function registration_fields(): array
{
    return [
        'username'  => ['Username',   true,  100, 'text'],
        'emailid'   => ['Email',      true,  254, 'email'],
        'password'  => ['Password',   true,  1024, 'password'],
        'company'   => ['Company',    true,  150, 'text'],
        'fname'     => ['First Name', true,  100, 'text'],
        'lname'     => ['Last Name',  true,  100, 'text'],
        'address-1' => ['Address 1',  true,  200, 'text'],
        'address-2' => ['Address 2',  false, 200, 'text'],
        'address-3' => ['Address 3',  false, 200, 'text'],
        'address-4' => ['Address 4',  false, 200, 'text'],
        'address-5' => ['Address 5',  false, 200, 'text'],
        'telephone' => ['Telephone',  false, 30,  'tel'],
        'gsm'       => ['GSM',        false, 30,  'tel'],
    ];
}

/** Whether self-registration is switched on (setup.registration = 'yes'). */
function registration_enabled(): bool
{
    $setup = get_setup() ?? [];
    // Column missing = migration not run yet = keep it switched off.
    return array_key_exists('registration', $setup)
        && strcasecmp(trim((string)$setup['registration']), 'yes') === 0;
}

/**
 * Where the "new registration" e-mail goes: setup.`Admin emailid`.
 * Returns '' if it's blank or not a valid address.
 */
function registration_admin_email(): string
{
    $setup = get_setup() ?? [];
    $admin = trim((string)($setup['Admin emailid'] ?? ''));
    return ($admin !== '' && filter_var($admin, FILTER_VALIDATE_EMAIL)) ? $admin : '';
}

/**
 * Why registration can't run right now even though it's switched on,
 * as a message for the person registering — or null if all is well.
 * Currently: setup.`Admin emailid` must be filled in with a valid
 * address, otherwise nobody would be told about new registrations.
 */
function registration_setup_error(): ?string
{
    if (registration_admin_email() === '') {
        error_log('registration: setup.`Admin emailid` is blank or invalid; registration refused.');
        return 'Registration is not available at the moment because the administrator email address has not been set up. Please contact us to open an account.';
    }
    return null;
}

/**
 * The site's password rule, shared by registration and password reset:
 * at least REGISTRATION_PASSWORD_MIN characters, with at least one
 * letter and one number. Returns an error message, or null if it's OK.
 */
function password_rule_error(string $password): ?string
{
    if (strlen($password) < REGISTRATION_PASSWORD_MIN) {
        return 'Password must be at least ' . REGISTRATION_PASSWORD_MIN . ' characters.';
    }
    if (strlen($password) > 1024) {
        return 'Password must be 1024 characters or fewer.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return null;
}

/**
 * Validate submitted values. Returns [cleanValues, errors] where
 * errors is field => message ('form' for errors not tied to a field).
 */
function registration_validate(array $post): array
{
    $fields = registration_fields();
    $v = [];
    $errors = [];

    foreach ($fields as $name => [$label, $required, $max]) {
        $raw = $post[$name] ?? '';
        $raw = is_string($raw) ? $raw : '';
        // Passwords are kept exactly as typed; everything else is trimmed
        // and has internal line breaks / tabs collapsed.
        $val = $name === 'password' ? $raw : trim((string)preg_replace('/\s+/u', ' ', $raw));
        $v[$name] = $val;

        if ($required && $val === '') {
            $errors[$name] = $label . ' is required.';
        } elseif (mb_strlen($val) > $max) {
            $errors[$name] = $label . ' must be ' . $max . ' characters or fewer.';
        }
    }

    if (!isset($errors['username']) && !preg_match('/^[A-Za-z0-9._@\-]{3,100}$/', $v['username'])) {
        $errors['username'] = 'Username must be 3-100 characters: letters, numbers and . _ @ - only (no spaces).';
    }

    if (!isset($errors['emailid'])) {
        $v['emailid'] = mb_strtolower($v['emailid']);
        if (!filter_var($v['emailid'], FILTER_VALIDATE_EMAIL)) {
            $errors['emailid'] = 'Please enter a valid email address.';
        }
    }

    if (!isset($errors['password'])) {
        $pwError = password_rule_error($v['password']);
        if ($pwError !== null) {
            $errors['password'] = $pwError;
        }
    }

    // Telephone OR GSM.
    foreach (['telephone', 'gsm'] as $phone) {
        if ($v[$phone] !== '' && !isset($errors[$phone])
            && (!preg_match('/^[0-9+\-().\/ ]+$/', $v[$phone]) || preg_match_all('/\d/', $v[$phone]) < 6)) {
            $errors[$phone] = 'Please enter a valid number (digits, spaces and + - ( ) only, at least 6 digits).';
        }
    }
    if ($v['telephone'] === '' && $v['gsm'] === '') {
        $errors['telephone'] = 'Enter a Telephone or GSM number (at least one is required).';
        $errors['gsm'] = 'Enter a Telephone or GSM number (at least one is required).';
    }

    // Duplicates in the user table.
    if (!isset($errors['username']) || !isset($errors['emailid'])) {
        $db = get_db();
        if (!isset($errors['username'])) {
            $stmt = $db->prepare('SELECT 1 FROM user WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $v['username']]);
            if ($stmt->fetchColumn()) {
                $errors['username'] = 'This username is already taken. Please choose another.';
            }
        }
        if (!isset($errors['emailid'])) {
            $stmt = $db->prepare('SELECT 1 FROM user WHERE emailid_hash = :h LIMIT 1');
            $stmt->execute([':h' => hmac_lookup($v['emailid'])]);
            if ($stmt->fetchColumn()) {
                $errors['emailid'] = 'An account with this email address already exists. Please log in instead.';
            }
        }
    }

    return [$v, $errors];
}

/**
 * user_types.id given to self-registered accounts: the "Standard User"
 * type if it exists, otherwise the lowest-level type that isn't an
 * admin (level < 8). Set explicitly on insert because databases
 * upgraded with migration_user_types.sql have no DEFAULT on
 * user.usertype (fresh schema.sql installs do).
 */
function registration_default_usertype_id(): int
{
    $db = get_db();
    $id = $db->query("SELECT id FROM user_types WHERE usertype = 'Standard User' LIMIT 1")->fetchColumn();
    if ($id === false) {
        $id = $db->query('SELECT id FROM user_types WHERE level < 8 ORDER BY level ASC, id ASC LIMIT 1')->fetchColumn();
    }
    if ($id === false) {
        throw new RuntimeException('No non-admin user type exists in user_types for new registrations.');
    }
    return (int)$id;
}

/**
 * Insert the new account. Returns the new user id. Throws
 * InvalidArgumentException with a user-facing message if the username
 * or email was taken in the moment between validation and insert.
 */
function registration_create_user(array $v): int
{
    $db = get_db();
    $cols = [
        'username'     => $v['username'],
        'emailid'      => encrypt_value($v['emailid']),
        'emailid_hash' => hmac_lookup($v['emailid']),
        'password'     => password_hash($v['password'], PASSWORD_BCRYPT),
        'company'      => $v['company'],
        'fname'        => $v['fname'],
        'lname'        => $v['lname'],
        'address-1'    => encrypt_value($v['address-1']),
        'address-2'    => encrypt_value($v['address-2']),
        'address-3'    => encrypt_value($v['address-3']),
        'address-4'    => encrypt_value($v['address-4']),
        'address-5'    => encrypt_value($v['address-5']),
        'telephone'    => encrypt_value($v['telephone']),
        'gsm'          => encrypt_value($v['gsm']),
        'usertype'     => registration_default_usertype_id(),
        'approval'     => 'pending',
    ];
    $names = [];
    $phs = [];
    $params = [];
    $i = 0;
    foreach ($cols as $col => $val) {
        $names[] = '`' . $col . '`';
        $phs[] = ':p' . $i;
        $params[':p' . $i] = $val;
        $i++;
    }
    try {
        $stmt = $db->prepare('INSERT INTO user (' . implode(', ', $names) . ') VALUES (' . implode(', ', $phs) . ')');
        $stmt->execute($params);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            throw new InvalidArgumentException('That username or email address was registered a moment ago. Please choose another.');
        }
        throw $e;
    }
    return (int)$db->lastInsertId();
}

/**
 * Send the two e-mails. Returns ['user' => bool, 'admin' => bool]
 * (true = handed to the server's mail program; false = rejected
 * straight away — the reason is written to the error log).
 */
function registration_send_mails(array $v): array
{
    $setup = get_setup() ?? [];
    $company = trim((string)($setup['company'] ?? '')) ?: 'our website';
    $loginUrl = (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://')
        . preg_replace('/[^a-z0-9.\-:]/i', '', (string)$_SERVER['HTTP_HOST']) : '') . asset_url('/login.php');
    $name = trim($v['fname'] . ' ' . $v['lname']);

    $userBody = "Dear {$name},\n\n"
        . "Thank you for registering with {$company}.\n\n"
        . "Your username is: {$v['username']}\n\n"
        . "Your account is now awaiting approval. You will be able to log in once it has been approved.\n"
        . "Login page: {$loginUrl}\n\n"
        . "If you did not register, please ignore this email.\n\n"
        . "Kind regards,\n{$company}\n";
    $userSent = send_plain_mail($v['emailid'], 'Your registration with ' . $company, $userBody, $company);

    $adminSent = false;
    $adminTo = registration_admin_email();
    if ($adminTo !== '') {
        $lines = [];
        foreach (registration_fields() as $field => [$label]) {
            if ($field === 'password' || $v[$field] === '') {
                continue;
            }
            $lines[] = str_pad($label . ':', 12) . ' ' . $v[$field];
        }
        $adminBody = "A new user has registered and is waiting for approval.\n\n"
            . implode("\n", $lines) . "\n"
            . str_pad('Registered:', 12) . ' ' . date('Y-m-d H:i') . "\n"
            . str_pad('IP address:', 12) . ' ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n"
            . "To let this user log in, open Admin > User, find \"{$v['username']}\" and set Approval to \"approved\".\n";
        $adminSent = send_plain_mail($adminTo, 'New registration: ' . $v['username'] . ' (' . $v['company'] . ')',
            $adminBody, $company, $v['emailid']);
    } else {
        error_log('registration: setup.`Admin emailid` is blank or invalid; admin was not notified.');
    }

    return ['user' => $userSent, 'admin' => $adminSent];
}

/**
 * E-mail a user that their account has been approved. Called by
 * crud_save() when Admin > User (or an Excel import) changes an
 * account's Approval to "approved". Replies go to setup.`Admin emailid`,
 * since the From address is normally a no-reply one.
 *
 * Returns ['ok' => bool, 'email' => recipient or '', 'error' => reason].
 * Never throws.
 */
function user_send_approval_mail(int|string $userId): array
{
    try {
        $stmt = get_db()->prepare('SELECT username, emailid, fname, lname FROM user WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return ['ok' => false, 'email' => '', 'error' => 'user not found'];
        }
        $email = trim((string)decrypt_value($user['emailid'] ?? null));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'email' => $email, 'error' => 'the account has no valid email address'];
        }

        $setup = get_setup() ?? [];
        $company = trim((string)($setup['company'] ?? '')) ?: 'our website';
        $name = trim((string)$user['fname'] . ' ' . (string)$user['lname']) ?: (string)$user['username'];
        $loginUrl = function_exists('full_url') ? full_url('/login.php') : asset_url('/login.php');

        $body = "Dear {$name},\n\n"
            . "Good news: your account with {$company} has been approved. You can now log in.\n\n"
            . "Username:   {$user['username']}\n"
            . "Login page: {$loginUrl}\n\n"
            . "If you have forgotten your password, please contact us.\n\n"
            . "Kind regards,\n{$company}\n";
        $replyTo = registration_admin_email();

        $ok = send_plain_mail($email, 'Your account with ' . $company . ' has been approved', $body, $company,
            $replyTo !== '' ? $replyTo : null);
        return ['ok' => $ok, 'email' => $email, 'error' => $ok ? '' : mail_last_error()];
    } catch (Throwable $e) {
        error_log('user_send_approval_mail failed: ' . $e->getMessage());
        return ['ok' => false, 'email' => '', 'error' => 'unexpected error'];
    }
}
