<?php
/**
 * security.php
 * Encryption-at-rest, CSRF protection and output-escaping helpers.
 *
 * Encryption: AES-256-GCM (authenticated encryption — protects both
 * confidentiality and integrity). The nonce is stored alongside the
 * ciphertext so each value carries what it needs to be decrypted.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/** Encrypt a plaintext string for storage (e.g. email, phone, address). */
function encrypt_value(?string $plaintext): ?string
{
    if ($plaintext === null || $plaintext === '') {
        return null;
    }
    $key   = base64_decode(APP_ENCRYPTION_KEY, true);
    $nonce = random_bytes(12); // 96-bit nonce required by GCM
    $tag   = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed.');
    }

    // Store as nonce || tag || ciphertext, base64-wrapped for binary safety.
    return base64_encode($nonce . $tag . $ciphertext);
}

/** Decrypt a value previously produced by encrypt_value(). */
function decrypt_value(?string $stored): ?string
{
    if ($stored === null || $stored === '') {
        return null;
    }
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) < 28) {
        return null; // corrupt / not encrypted data
    }

    $key        = base64_decode(APP_ENCRYPTION_KEY, true);
    $nonce      = substr($raw, 0, 12);
    $tag        = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag
    );

    return $plaintext === false ? null : $plaintext;
}

/**
 * Deterministic HMAC of a value (e.g. email) so it can be looked up
 * with an exact-match WHERE clause without ever storing it in plaintext.
 */
function hmac_lookup(string $plaintext): string
{
    return hash_hmac('sha256', mb_strtolower(trim($plaintext)), base64_decode(APP_HMAC_KEY, true));
}

/** Generate/return a per-session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Validate a submitted CSRF token using a timing-safe comparison. */
function csrf_verify(?string $submitted): bool
{
    return is_string($submitted)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

/** Shorthand for safe HTML output escaping (prevents stored/reflected XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
