<?php
/**
 * apikey.php
 * Admin > API: generate an API key for a customer and e-mail it to them.
 * Replaces the old admin-panel genapikey.php / updateapikey.php.
 *
 * Rules:
 * - The customer is chosen from the `customer` table.
 * - The customer's email (customer.email) must be filled in and valid,
 *   otherwise no key is generated.
 * - One key per customer: if `apikey` already has a row for this
 *   customer (same custid, or — for keys made before custid existed —
 *   the same email address), no key is generated.
 * - The key is made with a cryptographically secure generator (the old
 *   code used rand(), which is predictable) in the same format as
 *   before (15 letters/digits), so getapikey.php needs no change.
 * - On success the customer is e-mailed the endpoint URL and the key.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/registration.php'; // registration_admin_email()

/**
 * Where customers use their key — shown in the e-mail. Can be overridden
 * in config/config.php with define('APIKEY_ENDPOINT_URL', '...').
 */
if (!defined('APIKEY_ENDPOINT_URL')) {
    define('APIKEY_ENDPOINT_URL', 'https://veeradimon.be/admin-panel/getapikey.php');
}

const APIKEY_LENGTH = 15;
const APIKEY_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

/** A new random key (secure generator; same format as the old keys). */
function apikey_generate(): string
{
    $max = strlen(APIKEY_ALPHABET) - 1;
    $key = '';
    for ($i = 0; $i < APIKEY_LENGTH; $i++) {
        $key .= APIKEY_ALPHABET[random_int(0, $max)];
    }
    return $key;
}

/** All customers for the dropdown: [custid, custnm, email], by name. */
function apikey_customers(): array
{
    return get_db()->query('SELECT custid, custnm, email FROM customer ORDER BY custnm ASC, custid ASC')->fetchAll();
}

/** Existing keys with their customer name (if linked), newest first. */
function apikey_list(): array
{
    return get_db()->query(
        'SELECT a.id, a.custid, a.name, a.company, a.emailid, a.apikey, a.created_at, c.custnm
         FROM apikey a
         LEFT JOIN customer c ON c.custid = a.custid
         ORDER BY a.id DESC'
    )->fetchAll();
}

/** Show only the start of a key in lists, e.g. "OKYN•••••••••••". */
function apikey_mask(string $key): string
{
    if ($key === '') {
        return '(empty)';
    }
    return substr($key, 0, 4) . str_repeat('•', max(0, strlen($key) - 4));
}

/**
 * Generate a key for customer $custid and e-mail it.
 * Returns ['ok' => bool, 'message' => text for the admin,
 *          'apikey' => the new key or '', 'mailed' => bool, 'email' => string].
 */
function apikey_create_for_customer(int $custid): array
{
    $fail = fn(string $msg) => ['ok' => false, 'message' => $msg, 'apikey' => '', 'mailed' => false, 'email' => ''];
    $db = get_db();

    $stmt = $db->prepare('SELECT custid, custnm, email FROM customer WHERE custid = :id LIMIT 1');
    $stmt->execute([':id' => $custid]);
    $customer = $stmt->fetch();
    if (!$customer) {
        return $fail('Please select a customer.');
    }
    $name = trim((string)$customer['custnm']);
    $email = trim((string)$customer['email']);

    // 4. Email must be present (and valid).
    if ($email === '') {
        return $fail('Customer "' . $name . '" has no email address, so no API key was generated. '
            . 'Add the email in Manage Tables → Customers first.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $fail('Customer "' . $name . '" has an invalid email address ("' . $email . '"), so no API key was generated. '
            . 'Correct it in Manage Tables → Customers first.');
    }

    // 5. One key per customer (by custid, or by email for older keys).
    $dup = $db->prepare('SELECT id FROM apikey WHERE custid = :id OR LOWER(TRIM(emailid)) = LOWER(:email) LIMIT 1');
    $dup->execute([':id' => $custid, ':email' => $email]);
    if ($dup->fetchColumn()) {
        return $fail('Customer "' . $name . '" (' . $email . ') already has an API key, so no new key was generated.');
    }

    // Generate and insert; retry on the (very unlikely) chance of a clash.
    $key = '';
    $ins = $db->prepare('INSERT INTO apikey (custid, name, company, emailid, apikey) VALUES (:cid, :n, :c, :e, :k)');
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = apikey_generate();
        $exists = $db->prepare('SELECT 1 FROM apikey WHERE apikey = :k LIMIT 1');
        $exists->execute([':k' => $candidate]);
        if ($exists->fetchColumn()) {
            continue;
        }
        try {
            $ins->execute([
                ':cid' => $custid,
                ':n'   => mb_substr($name, 0, 100),
                ':c'   => mb_substr($name, 0, 100),
                ':e'   => mb_substr($email, 0, 100),
                ':k'   => $candidate,
            ]);
            $key = $candidate;
            break;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // Same custid inserted a moment ago by another request.
                return $fail('Customer "' . $name . '" already has an API key, so no new key was generated.');
            }
            throw $e;
        }
    }
    if ($key === '') {
        return $fail('Could not generate a unique API key. Please try again.');
    }

    // 7. Email the customer.
    $setup = get_setup() ?? [];
    $company = trim((string)($setup['company'] ?? '')) ?: 'our company';
    $replyTo = registration_admin_email();
    $body = "Dear {$name},\n\n"
        . "Your API key for {$company} has been created.\n\n"
        . 'URL:     ' . APIKEY_ENDPOINT_URL . "\n"
        . "API key: {$key}\n\n"
        . "Please keep this key confidential: anyone who has it can use it on your behalf. "
        . "If you think it has been shared, contact us and we will replace it.\n\n"
        . "Kind regards,\n{$company}\n";
    $mailed = send_plain_mail($email, 'Your API key for ' . $company, $body, $company, $replyTo !== '' ? $replyTo : null);
    if (!$mailed) {
        error_log('apikey: email to ' . $email . ' failed: ' . mail_last_error());
    }

    return [
        'ok'      => true,
        'message' => $mailed
            ? 'API key generated for "' . $name . '" and emailed to ' . $email . '.'
            : 'API key generated for "' . $name . '", but the email to ' . $email . ' could not be sent ('
              . rtrim(mail_last_error(), '. ') . '). Please give the customer the key below yourself.',
        'apikey'  => $key,
        'mailed'  => $mailed,
        'email'   => $email,
    ];
}

/** Delete a key (so the customer can be given a new one). Returns the deleted row or null. */
function apikey_revoke(int $id): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT id, name, emailid FROM apikey WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $db->prepare('DELETE FROM apikey WHERE id = :id')->execute([':id' => $id]);
    return $row;
}
