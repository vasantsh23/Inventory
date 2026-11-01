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
 * - On success the customer is e-mailed the download page's link
 *   (api/stock_csv.php — no key in it) and, separately, the key, which
 *   they type into the form on that page.
 * - Regenerate replaces a key: the old one stops working at once and
 *   the customer is e-mailed the new one.
 *
 * The CSV (api/stock_csv.php) holds the stones and columns a user of
 * type APIKEY_CSV_USERTYPE_ID sees on the Results page: that user
 * type's active `results` columns (with their labels as headings),
 * held stones left out according to setup.hold_restrict, sorted like
 * the Results page.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/registration.php'; // registration_admin_email()

require_once __DIR__ . '/diamond_search_query.php'; // get_maindata_columns(), build_rsetup_order_by(), HOLD_EXEMPT_LEVELS

/**
 * User type whose Results columns (and hold rules) the CSV follows.
 * Override in config/config.php: define('APIKEY_CSV_USERTYPE_ID', 2);
 */
if (!defined('APIKEY_CSV_USERTYPE_ID')) {
    define('APIKEY_CSV_USERTYPE_ID', defined('GUEST_RESULTS_USERTYPE_ID') ? (int)GUEST_RESULTS_USERTYPE_ID : 1);
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

/** The download page (api/stock_csv.php). The key is never part of the URL. */
function apikey_download_url(): string
{
    return function_exists('full_url') ? full_url('/api/stock_csv.php') : asset_url('/api/stock_csv.php');
}

/**
 * The apikey row for $key, or null. Compared exactly (case-sensitive)
 * in PHP, because the table's collation may ignore case. Empty keys
 * never match.
 */
function apikey_find(string $key): ?array
{
    $key = trim($key);
    if ($key === '' || strlen($key) > 25 || !preg_match('/^[A-Za-z0-9]+$/', $key)) {
        return null;
    }
    $stmt = get_db()->prepare('SELECT id, custid, name, emailid, apikey FROM apikey WHERE apikey = :k');
    $stmt->execute([':k' => $key]);
    foreach ($stmt->fetchAll() as $row) {
        if (hash_equals((string)$row['apikey'], $key)) {
            return $row;
        }
    }
    return null;
}

/**
 * Columns for the CSV: [['label' => heading, 'field' => maindata column], …]
 * from the active `results` rows of APIKEY_CSV_USERTYPE_ID, validated
 * against maindata's real columns.
 */
function apikey_csv_columns(): array
{
    $stmt = get_db()->prepare("SELECT colname, fldname FROM results WHERE usertype = :ut AND active = 'yes' ORDER BY orderid ASC");
    $stmt->execute([':ut' => (int)APIKEY_CSV_USERTYPE_ID]);
    $valid = get_maindata_columns();
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        if (in_array($row['fldname'], $valid, true)) {
            $out[] = ['label' => (string)$row['colname'], 'field' => (string)$row['fldname']];
        }
    }
    return $out;
}

/**
 * WHERE clause for the CSV's rows: held stones are left out when
 * setup.hold_restrict = 'yes' and the CSV's user type isn't one of the
 * HOLD_EXEMPT_LEVELS (same rule as the Results page).
 */
function apikey_csv_where(): string
{
    if (!in_array('hold', get_maindata_columns(), true)) {
        return '1';
    }
    $setup = get_setup() ?? [];
    if (array_key_exists('hold_restrict', $setup)
        && strcasecmp(trim((string)$setup['hold_restrict']), 'yes') !== 0) {
        return '1';
    }
    $stmt = get_db()->prepare('SELECT level FROM user_types WHERE id = :id');
    $stmt->execute([':id' => (int)APIKEY_CSV_USERTYPE_ID]);
    $level = (int)($stmt->fetchColumn() ?: 0);
    return in_array($level, HOLD_EXEMPT_LEVELS, true) ? '1' : "(`hold` IS NULL OR `hold` <> 'yes')";
}

/** E-mail a customer their (new) key and download link. Returns true if sent. */
function apikey_send_key_mail(string $name, string $email, string $key, bool $replaced = false): bool
{
    $setup = get_setup() ?? [];
    $company = trim((string)($setup['company'] ?? '')) ?: 'our company';
    $replyTo = registration_admin_email();
    $body = "Dear {$name},\n\n"
        . ($replaced
            ? "Your API key for {$company} has been replaced. Your previous key no longer works.\n\n"
            : "Your API key for {$company} has been created.\n\n")
        . "To download our current stock list as a CSV file (opens in Excel):\n"
        . "1. Open this page: " . apikey_download_url() . "\n"
        . "2. Enter your API key: {$key}\n"
        . "3. Click Download.\n\n"
        . "The page always gives you the latest stock, so you can bookmark it.\n"
        . "Please keep your API key confidential: anyone who has it can download the list. "
        . "If you think it has been shared, contact us and we will replace it.\n\n"
        . "Kind regards,\n{$company}\n";
    return send_plain_mail(
        $email,
        ($replaced ? 'Your new API key for ' : 'Your API key for ') . $company,
        $body,
        $company,
        $replyTo !== '' ? $replyTo : null
    );
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

    // Generate and insert.
    $key = apikey_unique_key();
    try {
        $db->prepare('INSERT INTO apikey (custid, name, company, emailid, apikey) VALUES (:cid, :n, :c, :e, :k)')->execute([
            ':cid' => $custid,
            ':n'   => mb_substr($name, 0, 100),
            ':c'   => mb_substr($name, 0, 100),
            ':e'   => mb_substr($email, 0, 100),
            ':k'   => $key,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            // Same custid inserted a moment ago by another request.
            return $fail('Customer "' . $name . '" already has an API key, so no new key was generated.');
        }
        throw $e;
    }

    // 7. Email the customer.
    $mailed = apikey_send_key_mail($name, $email, $key);
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

/** A new key that isn't in use yet. */
function apikey_unique_key(): string
{
    $exists = get_db()->prepare('SELECT 1 FROM apikey WHERE apikey = :k LIMIT 1');
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = apikey_generate();
        $exists->execute([':k' => $candidate]);
        if (!$exists->fetchColumn()) {
            return $candidate;
        }
    }
    throw new RuntimeException('Could not generate a unique API key.');
}

/**
 * Replace the key in apikey row $id with a new one (the old key stops
 * working at once) and e-mail it. Uses the customer's current email
 * from the customer table when the key is linked to a customer,
 * otherwise the email stored with the key.
 * Returns the same shape as apikey_create_for_customer().
 */
function apikey_regenerate(int $id): array
{
    $fail = fn(string $msg) => ['ok' => false, 'message' => $msg, 'apikey' => '', 'mailed' => false, 'email' => ''];
    $db = get_db();
    $stmt = $db->prepare(
        'SELECT a.id, a.custid, a.name, a.emailid, c.custnm, c.email AS cust_email
         FROM apikey a LEFT JOIN customer c ON c.custid = a.custid
         WHERE a.id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return $fail('That API key no longer exists.');
    }
    $name = trim((string)($row['custnm'] ?? '')) !== '' ? trim((string)$row['custnm']) : trim((string)$row['name']);
    $email = trim((string)($row['custid'] !== null && $row['cust_email'] !== null ? $row['cust_email'] : $row['emailid']));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $fail('"' . $name . '" has no valid email address' . ($email !== '' ? ' ("' . $email . '")' : '')
            . ', so the key was not regenerated. '
            . ($row['custid'] !== null ? 'Add the email in Manage Tables → Customers first.' : 'Revoke this older key and generate a new one for the customer instead.'));
    }

    $key = apikey_unique_key();
    $db->prepare('UPDATE apikey SET apikey = :k, emailid = :e, created_at = NOW() WHERE id = :id')
        ->execute([':k' => $key, ':e' => mb_substr($email, 0, 100), ':id' => $id]);

    $mailed = apikey_send_key_mail($name, $email, $key, true);
    if (!$mailed) {
        error_log('apikey: regenerate email to ' . $email . ' failed: ' . mail_last_error());
    }
    return [
        'ok'      => true,
        'message' => $mailed
            ? 'New API key generated for "' . $name . '" and emailed to ' . $email . '. The old key no longer works.'
            : 'New API key generated for "' . $name . '" (the old key no longer works), but the email to ' . $email
              . ' could not be sent (' . rtrim(mail_last_error(), '. ') . '). Please give the customer the key below yourself.',
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
