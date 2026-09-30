<?php
/**
 * admin_helpers.php — shared by the Website screens in the admin module
 * (Templates, Template form, Page Content, Enquiries).
 * Reuses the existing admin conventions: require_module_access('admin'),
 * CSRF + flash helpers from includes/theme_ui.php, admin_header/footer.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/theme_ui.php';
require_once __DIR__ . '/Site.php';

require_module_access('admin');

const SITE_UPLOAD_DIR = '/assets/site/uploads';
const SITE_UPLOAD_MAX_BYTES = 6 * 1024 * 1024;
const SITE_UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

/** true when the website tables exist (migration has been run) */
function site_admin_ready(): bool
{
    try {
        get_db()->query('SELECT 1 FROM site_templates LIMIT 1');
        get_db()->query('SELECT 1 FROM site_content LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function site_admin_missing_notice(): string
{
    return '<div class="panel"><h2>Website tables not installed yet</h2>'
         . '<p class="panel-desc">Run <code>sql/migration_website_templates.sql</code> once in phpMyAdmin (SQL tab, with your database selected), then reload this page. '
         . 'Take a backup first in Backup &amp; Restore.</p></div>';
}

/** Extra stylesheet for the Website admin screens (admin_header has no head hook, so it is linked in the body) */
function site_admin_css(): string
{
    return '<link rel="stylesheet" href="' . e(asset_url_versioned('/assets/site/css/admin-site.css')) . '">';
}

/**
 * Store one uploaded image. Returns the site path (/assets/site/uploads/…)
 * or throws RuntimeException with a message for the admin.
 * SVG is not accepted (it can carry scripts); the file is renamed randomly
 * and its real type is checked from its content, not its name.
 */
function site_store_upload(array $file): string
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('The image is larger than the server allows. Use an image under 6 MB.');
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('The image could not be uploaded. Try again.');
    }
    if ((int) $file['size'] > SITE_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('The image is larger than 6 MB. Save it smaller (for example as WebP or JPEG) and try again.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!isset(SITE_UPLOAD_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Only JPEG, PNG, WebP and GIF images can be uploaded.');
    }
    $dir = dirname(__DIR__) . SITE_UPLOAD_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('The upload folder assets/site/uploads could not be created. Check its permissions.');
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . SITE_UPLOAD_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The image could not be saved. Check that assets/site/uploads is writable.');
    }
    @chmod($dir . '/' . $name, 0644);
    return SITE_UPLOAD_DIR . '/' . $name;
}

/** $_FILES entry for name="up[a][b]" style nested fields */
function site_uploaded_file(string $top, string ...$path): ?array
{
    if (!isset($_FILES[$top])) {
        return null;
    }
    $f = $_FILES[$top];
    $out = [];
    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
        $v = $f[$k] ?? null;
        foreach ($path as $p) {
            $v = is_array($v) ? ($v[$p] ?? null) : null;
        }
        $out[$k] = $v;
    }
    return ((int) ($out['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $out['tmp_name'] === null) ? null : $out;
}

/** Images available to pick: shipped images + uploads, newest uploads first */
function site_image_library(): array
{
    $root = dirname(__DIR__);
    $out = [];
    foreach ([SITE_UPLOAD_DIR => 'Uploaded', '/assets/site/img' => 'Built-in'] as $dir => $group) {
        $files = glob($root . $dir . '/*.{webp,png,jpg,jpeg,gif}', GLOB_BRACE) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach ($files as $f) {
            $out[$dir . '/' . basename($f)] = $group . ' — ' . basename($f);
        }
    }
    return $out;
}

function site_admin_url(string $page, array $query = []): string
{
    return asset_url('/modules/admin/' . $page) . ($query ? '?' . http_build_query($query) : '');
}
