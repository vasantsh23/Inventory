<?php
/**
 * contact_form.php — the website's enquiry form.
 *
 * Protection: CSRF token, hidden honeypot field, minimum fill time,
 * per-IP rate limit, strict length/format validation, header-safe e-mail.
 * Messages are stored in website_enquiries (Admin -> Website -> Enquiries)
 * and, when possible, e-mailed to the company address.
 */

declare(strict_types=1);

const SITE_FORM_MIN_SECONDS = 3;
const SITE_FORM_MAX_PER_HOUR = 5;

/** Fields shown on the form: name => [label, required, type, maxlength] */
function site_contact_fields(): array
{
    return [
        'name'     => ['Name', true, 'text', 120, 'name'],
        'company'  => ['Company name', false, 'text', 150, 'organization'],
        'email'    => ['Email', true, 'email', 150, 'email'],
        'phone'    => ['Phone', false, 'tel', 50, 'tel'],
        'location' => ['Country / city', false, 'text', 120, 'country-name'],
        'message'  => ['Message', true, 'textarea', 5000, 'off'],
    ];
}

/** Call while rendering the form: returns ['sent'=>bool,'errors'=>[],'old'=>[]] and arms the timer */
function site_contact_state(): array
{
    $state = $_SESSION['site_contact_state'] ?? ['sent' => false, 'errors' => [], 'old' => []];
    unset($_SESSION['site_contact_state']);
    $_SESSION['site_contact_t'] = time();
    return $state;
}

function site_contact_handle(string $page): void
{
    $back = Site::pageUrl($page) . '#contact-form';
    $fail = function (array $errors, array $old) use ($back): never {
        $_SESSION['site_contact_state'] = ['sent' => false, 'errors' => $errors, 'old' => $old];
        header('Location: ' . $back, true, 303);
        exit;
    };

    $old = [];
    foreach (site_contact_fields() as $k => $f) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $fail(['form' => 'Your session expired. Please send the form again.'], $old);
    }

    // Bots: honeypot filled or submitted too quickly -> pretend success, store nothing
    $started = (int) ($_SESSION['site_contact_t'] ?? 0);
    if (trim((string) ($_POST['website'] ?? '')) !== '' || $started === 0 || time() - $started < SITE_FORM_MIN_SECONDS) {
        $_SESSION['site_contact_state'] = ['sent' => true, 'errors' => [], 'old' => []];
        header('Location: ' . $back, true, 303);
        exit;
    }

    $errors = [];
    foreach (site_contact_fields() as $k => [$label, $required, $type, $max]) {
        $v = $old[$k];
        if ($required && $v === '') {
            $errors[$k] = "Enter your " . strtolower($label) . '.';
        } elseif (mb_strlen($v) > $max) {
            $errors[$k] = "$label can be at most $max characters.";
        }
    }
    if (!isset($errors['email']) && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter an email address like name@company.com.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9 +().\-\/]{5,50}$/', $old['phone'])) {
        $errors['phone'] = 'Use digits, spaces and + ( ) - only.';
    }
    if (!isset($errors['message']) && mb_strlen($old['message']) < 10) {
        $errors['message'] = 'Tell us a little more — at least 10 characters.';
    }
    if (empty($_POST['consent'])) {
        $errors['consent'] = 'Tick the box so we can reply to you.';
    }
    if ($errors) {
        $fail($errors, $old);
    }

    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    try {
        $st = get_db()->prepare('SELECT COUNT(*) FROM website_enquiries WHERE ipadd = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $st->execute([$ip]);
        if ((int) $st->fetchColumn() >= SITE_FORM_MAX_PER_HOUR) {
            $fail(['form' => 'You have sent several messages in the last hour. Please try again later, or call us.'], $old);
        }
        get_db()->prepare(
            'INSERT INTO website_enquiries (name, company, email, phone, location, message, page_key, ipadd) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$old['name'], $old['company'] ?: null, $old['email'], $old['phone'] ?: null,
                    $old['location'] ?: null, $old['message'], $page, $ip]);
    } catch (Throwable $e) {
        error_log('site enquiry save failed: ' . $e->getMessage());
        $fail(['form' => 'Your message could not be sent right now. Please email or call us instead.'], $old);
    }

    site_contact_notify($old);

    $_SESSION['site_contact_state'] = ['sent' => true, 'errors' => [], 'old' => []];
    unset($_SESSION['site_contact_t']);
    header('Location: ' . $back, true, 303);
    exit;
}

/** Best-effort e-mail notification; never blocks the enquiry from being saved */
function site_contact_notify(array $d): void
{
    $to = trim(Site::global('form', 'notify_email')) ?: primary_email(Site::setup());
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return;
    }
    $clean = fn (string $s) => trim(preg_replace('/[\r\n\t]+/', ' ', $s));
    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/^www\./i', '', $host) ?: 'localhost';
    $subject = 'Website enquiry from ' . $clean($d['name']);
    $body = "New enquiry from the website\n\n";
    foreach (site_contact_fields() as $k => [$label]) {
        if ($d[$k] !== '') {
            $body .= $label . ': ' . ($k === 'message' ? "\n" . $d[$k] : $clean($d[$k])) . "\n";
        }
    }
    $body .= "\nView all enquiries in Admin > Website > Enquiries.\n";
    $headers = [
        'From: ' . $clean(Site::company()) . ' <no-reply@' . $host . '>',
        'Reply-To: ' . $clean($d['email']),
        'Content-Type: text/plain; charset=UTF-8',
    ];
    try {
        @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    } catch (Throwable $e) {
        error_log('site enquiry mail failed: ' . $e->getMessage());
    }
}
