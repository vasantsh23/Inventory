<?php
/**
 * mailer.php
 * Plain-text e-mail for registration and Admin > Email Test.
 *
 * Two ways to send, chosen automatically:
 *   - Postmark (https://postmarkapp.com) when POSTMARK_SERVER_TOKEN is
 *     set in config/config.php — reliable delivery through Postmark's
 *     API, with Postmark's own error message if it refuses.
 *   - Otherwise PHP's mail() through this server's mail program, the
 *     way the website contact form sends.
 * Header values are stripped of line breaks so user input can't inject
 * extra headers.
 *
 * Settings (config/config.php; all optional):
 *   define('POSTMARK_SERVER_TOKEN', '...');     // Postmark > Server > API Tokens
 *   define('POSTMARK_MESSAGE_STREAM', 'outbound'); // stream ID; 'outbound' = default transactional stream
 *   define('MAIL_FROM_ADDRESS', 'info@yourdomain.com'); // must be a verified Sender Signature / domain in Postmark
 */

declare(strict_types=1);

if (!defined('POSTMARK_SERVER_TOKEN')) {
    define('POSTMARK_SERVER_TOKEN', (string)(getenv('POSTMARK_SERVER_TOKEN') ?: ''));
}
if (!defined('POSTMARK_MESSAGE_STREAM')) {
    define('POSTMARK_MESSAGE_STREAM', 'outbound');
}
if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', '');
}
if (!defined('POSTMARK_API_URL')) {
    define('POSTMARK_API_URL', 'https://api.postmarkapp.com/email');
}

/** 'postmark' or 'php_mail' — how send_plain_mail() will send. */
function mail_transport(): string
{
    return trim((string)POSTMARK_SERVER_TOKEN) !== '' ? 'postmark' : 'php_mail';
}

/** Remove CR/LF/tab so a value is safe inside a mail header. */
function mail_header_clean(string $value): string
{
    return trim((string)preg_replace('/[\r\n\t]+/', ' ', $value));
}

/**
 * The From address: MAIL_FROM_ADDRESS if set (required for Postmark —
 * it must be a verified Sender Signature or on a verified domain),
 * otherwise "no-reply@<this site's domain>".
 */
function mail_from_address(): string
{
    $configured = mail_header_clean((string)MAIL_FROM_ADDRESS);
    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
        return $configured;
    }
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = (string)preg_replace('/:\d+$/', '', $host); // drop any ":port"
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $host);
    $host = preg_replace('/^www\./i', '', (string)$host) ?: 'localhost';
    return 'no-reply@' . $host;
}

/** Reason the last send_plain_mail() call failed ('' if it succeeded). */
function mail_last_error(): string
{
    return (string)($GLOBALS['__mail_last_error'] ?? '');
}

/**
 * Send a plain-text e-mail. Returns true if the server's mail program
 * accepted it (which doesn't guarantee delivery), false otherwise.
 * Never throws — failures are written to the error log and are
 * available from mail_last_error().
 *
 * The envelope sender (-f) is set to the From address so the message
 * doesn't go out as the hosting account's system user (e.g.
 * "cpaneluser@server-name"), which makes Gmail and others reject it or
 * file it as spam. If the server refuses -f, it retries without.
 */
function send_plain_mail(string $to, string $subject, string $body, string $fromName = '', ?string $replyTo = null): bool
{
    $GLOBALS['__mail_last_error'] = '';
    $to = mail_header_clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['__mail_last_error'] = 'Invalid recipient address.';
        error_log('send_plain_mail: invalid recipient address');
        return false;
    }
    if (!function_exists('mail')) {
        $GLOBALS['__mail_last_error'] = 'PHP mail() is disabled on this server.';
        error_log('send_plain_mail: PHP mail() is disabled on this server');
        return false;
    }

    if (mail_transport() === 'postmark') {
        return send_via_postmark($to, $subject, $body, $fromName, $replyTo);
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
    $encodedSubject = '=?UTF-8?B?' . base64_encode(mail_header_clean($subject)) . '?=';
    $body = str_replace("\r\n", "\n", $body);
    $headerText = implode("\r\n", $headers);

    $ok = false;
    try {
        error_clear_last();
        $ok = @mail($to, $encodedSubject, $body, $headerText, '-f' . $from);
        if (!$ok) {
            // Some hosts don't allow setting the envelope sender.
            error_clear_last();
            $ok = @mail($to, $encodedSubject, $body, $headerText);
        }
    } catch (Throwable $e) {
        $GLOBALS['__mail_last_error'] = $e->getMessage();
        error_log('send_plain_mail failed: ' . $e->getMessage());
        return false;
    }
    if (!$ok) {
        $last = error_get_last();
        $GLOBALS['__mail_last_error'] = 'The server\'s mail program refused the message'
            . ($last !== null ? ': ' . $last['message'] : '.');
        error_log('send_plain_mail: ' . $GLOBALS['__mail_last_error'] . ' (to ' . $to . ')');
    }
    return (bool)$ok;
}

/**
 * Send through Postmark's HTTP API (POST /email). Returns true when
 * Postmark answers ErrorCode 0; otherwise false, with Postmark's own
 * message in mail_last_error() (e.g. sender not verified, account
 * pending approval, inactive recipient).
 */
function send_via_postmark(string $to, string $subject, string $body, string $fromName = '', ?string $replyTo = null): bool
{
    $from = mail_from_address();
    $fromName = str_replace(['"', '<', '>'], '', mail_header_clean($fromName));
    $payload = [
        'From'          => $fromName !== '' ? '"' . $fromName . '" <' . $from . '>' : $from,
        'To'            => $to,
        'Subject'       => mail_header_clean($subject),
        'TextBody'      => str_replace("\r\n", "\n", $body),
        'MessageStream' => trim((string)POSTMARK_MESSAGE_STREAM) ?: 'outbound',
    ];
    if ($replyTo !== null && filter_var(mail_header_clean($replyTo), FILTER_VALIDATE_EMAIL)) {
        $payload['ReplyTo'] = mail_header_clean($replyTo);
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . trim((string)POSTMARK_SERVER_TOKEN),
    ];

    $status = 0;
    $response = false;
    $transportError = '';
    if (function_exists('curl_init')) {
        $ch = curl_init(POSTMARK_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            $transportError = curl_error($ch);
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $json,
            'timeout'       => 20,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents(POSTMARK_API_URL, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
        if ($response === false) {
            $last = error_get_last();
            $transportError = $last['message'] ?? 'connection failed';
        }
    }

    if ($response === false) {
        $GLOBALS['__mail_last_error'] = 'Could not connect to Postmark: ' . $transportError;
        error_log('send_via_postmark: ' . $GLOBALS['__mail_last_error'] . ' (to ' . $to . ')');
        return false;
    }

    $data = json_decode((string)$response, true);
    $errorCode = is_array($data) ? (int)($data['ErrorCode'] ?? -1) : -1;
    if ($status === 200 && $errorCode === 0) {
        return true;
    }
    $message = is_array($data) ? (string)($data['Message'] ?? '') : '';
    if ($status === 401) {
        $message = 'the server token is wrong or has been deleted (check POSTMARK_SERVER_TOKEN). ' . $message;
    }
    $GLOBALS['__mail_last_error'] = 'Postmark refused the message (HTTP ' . $status
        . ($errorCode > 0 ? ', error ' . $errorCode : '') . '): ' . ($message !== '' ? $message : substr((string)$response, 0, 300));
    error_log('send_via_postmark: ' . $GLOBALS['__mail_last_error'] . ' (to ' . $to . ')');
    return false;
}
