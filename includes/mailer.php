<?php
/**
 * mailer.php
 * Plain-text e-mail via PHP's mail(), the same way the website's
 * contact form already sends its notifications. Header values are
 * stripped of line breaks so user input can't inject extra headers.
 */

declare(strict_types=1);

/** Remove CR/LF/tab so a value is safe inside a mail header. */
function mail_header_clean(string $value): string
{
    return trim((string)preg_replace('/[\r\n\t]+/', ' ', $value));
}

/** "no-reply@<this site's domain>" — used as the From address. */
function mail_from_address(): string
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = (string)preg_replace('/:\d+$/', '', $host); // drop any ":port"
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $host);
    $host = preg_replace('/^www\./i', '', (string)$host) ?: 'localhost';
    return 'no-reply@' . $host;
}

/**
 * Send a plain-text e-mail. Returns true if PHP handed it to the mail
 * system (which doesn't guarantee delivery), false otherwise. Never
 * throws — failures are written to the error log.
 */
function send_plain_mail(string $to, string $subject, string $body, string $fromName = '', ?string $replyTo = null): bool
{
    $to = mail_header_clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        error_log('send_plain_mail: invalid recipient or mail() unavailable');
        return false;
    }

    $from = mail_from_address();
    $fromName = mail_header_clean($fromName);
    $headers = [
        'From: ' . ($fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . '?= ' : '') . '<' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo !== null && filter_var(mail_header_clean($replyTo), FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . mail_header_clean($replyTo);
    }

    try {
        $ok = @mail(
            $to,
            '=?UTF-8?B?' . base64_encode(mail_header_clean($subject)) . '?=',
            str_replace("\r\n", "\n", $body),
            implode("\r\n", $headers)
        );
    } catch (Throwable $e) {
        error_log('send_plain_mail failed: ' . $e->getMessage());
        return false;
    }
    if (!$ok) {
        error_log('send_plain_mail: mail() returned false for a message to ' . $to);
    }
    return (bool)$ok;
}
