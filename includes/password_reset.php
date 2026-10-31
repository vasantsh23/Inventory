<?php
/**
 * password_reset.php
 * "Forgot password?" from the login screen.
 *
 * 1. forgot_password.php: the user enters their username or email.
 *    If it matches an approved account with a valid email, a one-time
 *    link is e-mailed to that account's address. The page always shows
 *    the same message, so it can't be used to find out which usernames
 *    or emails exist.
 * 2. reset_password.php?token=…: the user picks a new password.
 *
 * Security:
 * - The link holds 32 random bytes; only their SHA-256 hash is stored
 *   (password_resets.token_hash), so a leaked database can't be used.
 * - Links expire after PASSWORD_RESET_TTL_MINUTES and work once.
 *   Requesting a new link cancels older ones.
 * - At most PASSWORD_RESET_MAX_PER_HOUR links per account per hour.
 * - A successful reset clears any login lockout, signs the account out
 *   of every "remember me" device (website and app), and e-mails the
 *   user that the password was changed.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/registration.php'; // password_rule_error(), registration_admin_email()

const PASSWORD_RESET_TTL_MINUTES = 60;
const PASSWORD_RESET_MAX_PER_HOUR = 3;

/** SHA-256 of a reset token, as stored in password_resets.token_hash. */
function password_reset_hash(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Handle a "forgot password" request for $identifier (username or
 * email). Sends a reset link if it matches an approved account with a
 * valid email. Returns nothing on purpose: the caller must show the
 * same message whatever happened. Problems go to the error log.
 */
function password_reset_request(string $identifier): void
{
    $identifier = trim($identifier);
    if ($identifier === '' || mb_strlen($identifier) > 254) {
        return;
    }

    try {
        $db = get_db();
        $stmt = $db->prepare(
            'SELECT id, username, emailid, fname, lname, approval
             FROM user
             WHERE username = :u OR emailid_hash = :h
             LIMIT 1'
        );
        $stmt->execute([':u' => $identifier, ':h' => hmac_lookup(mb_strtolower($identifier))]);
        $user = $stmt->fetch();
        if (!$user) {
            return;
        }
        if ($user['approval'] !== 'approved') {
            error_log('password reset: not sent for "' . $user['username'] . '" (approval = ' . $user['approval'] . ')');
            return;
        }
        $email = trim((string)decrypt_value($user['emailid'] ?? null));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('password reset: not sent for "' . $user['username'] . '" (no valid email on the account)');
            return;
        }

        $recent = $db->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = :id AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $recent->execute([':id' => $user['id']]);
        if ((int)$recent->fetchColumn() >= PASSWORD_RESET_MAX_PER_HOUR) {
            error_log('password reset: rate limit reached for "' . $user['username'] . '"');
            return;
        }

        // New link replaces any older unused ones. Expired rather than
        // deleted, so they still count towards the hourly limit above.
        $db->prepare('UPDATE password_resets SET expires_at = NOW() WHERE user_id = :id AND used_at IS NULL AND expires_at > NOW()')
            ->execute([':id' => $user['id']]);

        $token = bin2hex(random_bytes(32));
        $db->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, ipadd)
             VALUES (:id, :h, NOW() + INTERVAL ' . PASSWORD_RESET_TTL_MINUTES . ' MINUTE, :ip)'
        )->execute([
            ':id' => $user['id'],
            ':h'  => password_reset_hash($token),
            ':ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ]);

        $setup = get_setup() ?? [];
        $company = trim((string)($setup['company'] ?? '')) ?: 'our website';
        $name = trim((string)$user['fname'] . ' ' . (string)$user['lname']) ?: (string)$user['username'];
        $link = (function_exists('full_url') ? full_url('/reset_password.php') : asset_url('/reset_password.php'))
            . '?token=' . $token;
        $replyTo = registration_admin_email();

        $body = "Dear {$name},\n\n"
            . "We received a request to reset the password for your {$company} account.\n\n"
            . "Username: {$user['username']}\n\n"
            . "To choose a new password, open this link within " . PASSWORD_RESET_TTL_MINUTES . " minutes:\n"
            . "{$link}\n\n"
            . "The link works once. If you didn't ask to reset your password, you can ignore this email; "
            . "your password will not change.\n\n"
            . "Kind regards,\n{$company}\n";

        if (!send_plain_mail($email, 'Reset your password for ' . $company, $body, $company, $replyTo !== '' ? $replyTo : null)) {
            error_log('password reset: email to "' . $user['username'] . '" failed: ' . mail_last_error());
        }
    } catch (Throwable $e) {
        error_log('password reset request failed: ' . $e->getMessage());
    }
}

/**
 * The user a reset token belongs to, if the token is valid (exists, not
 * used, not expired, account still approved). Returns ['reset_id',
 * 'user_id', 'username'] or null.
 */
function password_reset_find(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $stmt = get_db()->prepare(
        'SELECT pr.id AS reset_id, u.id AS user_id, u.username
         FROM password_resets pr
         JOIN user u ON u.id = pr.user_id
         WHERE pr.token_hash = :h
           AND pr.used_at IS NULL
           AND pr.expires_at > NOW()
           AND u.approval = \'approved\'
         LIMIT 1'
    );
    $stmt->execute([':h' => password_reset_hash($token)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Set a new password using a reset token. Returns null on success, or a
 * message to show the user. On success the token is used up, lockouts
 * are cleared, remember-me devices are signed out, and the user is
 * e-mailed that their password changed.
 */
function password_reset_complete(string $token, string $password, string $confirm): ?string
{
    $reset = password_reset_find($token);
    if ($reset === null) {
        return 'This reset link is invalid or has expired. Please request a new one.';
    }
    $ruleError = password_rule_error($password);
    if ($ruleError !== null) {
        return $ruleError;
    }
    if (!hash_equals($password, $confirm)) {
        return 'The two passwords don’t match.';
    }

    $db = get_db();
    $db->beginTransaction();
    try {
        // Claim the token first; if another request used it a moment
        // ago, nothing is changed.
        $claim = $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id AND used_at IS NULL');
        $claim->execute([':id' => $reset['reset_id']]);
        if ($claim->rowCount() !== 1) {
            $db->rollBack();
            return 'This reset link has already been used. Please request a new one.';
        }
        $db->prepare(
            'UPDATE user SET password = :p, failed_attempts = 0, locked_until = NULL WHERE id = :id'
        )->execute([':p' => password_hash($password, PASSWORD_BCRYPT), ':id' => $reset['user_id']]);
        $db->prepare('UPDATE password_resets SET expires_at = NOW() WHERE user_id = :id AND used_at IS NULL AND expires_at > NOW()')
            ->execute([':id' => $reset['user_id']]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('password reset failed: ' . $e->getMessage());
        return 'Your password could not be changed right now. Please try again later.';
    }

    revoke_remember_tokens((int)$reset['user_id']);
    password_reset_send_changed_mail((int)$reset['user_id']);
    return null;
}

/** "Your password was changed" notice, so the owner notices a reset they didn't ask for. */
function password_reset_send_changed_mail(int $userId): void
{
    try {
        $stmt = get_db()->prepare('SELECT username, emailid, fname, lname FROM user WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        $email = $user ? trim((string)decrypt_value($user['emailid'] ?? null)) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $setup = get_setup() ?? [];
        $company = trim((string)($setup['company'] ?? '')) ?: 'our website';
        $name = trim((string)$user['fname'] . ' ' . (string)$user['lname']) ?: (string)$user['username'];
        $replyTo = registration_admin_email();
        $body = "Dear {$name},\n\n"
            . "The password for your {$company} account ({$user['username']}) was changed on " . date('Y-m-d H:i') . ".\n\n"
            . "If you made this change, no action is needed.\n"
            . "If you did NOT change your password, please contact us immediately by replying to this email.\n\n"
            . "Kind regards,\n{$company}\n";
        if (!send_plain_mail($email, 'Your password for ' . $company . ' was changed', $body, $company, $replyTo !== '' ? $replyTo : null)) {
            error_log('password reset: "changed" email failed: ' . mail_last_error());
        }
    } catch (Throwable $e) {
        error_log('password reset: "changed" email failed: ' . $e->getMessage());
    }
}
