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

/** Why the website tables look missing (database error text), for the notice */
$GLOBALS['site_admin_ready_error'] = '';

/** true when the website tables exist (migration has been run) */
function site_admin_ready(): bool
{
    try {
        foreach (['website_templates', 'website_blocks', 'website_content', 'website_enquiries'] as $t) {
            get_db()->query("SELECT 1 FROM `$t` LIMIT 1");
        }
        get_db()->query('SELECT settings_prefix, parts FROM website_templates LIMIT 1'); // current table layout
        return true;
    } catch (Throwable $e) {
        $GLOBALS['site_admin_ready_error'] = $e->getMessage();
        return false;
    }
}

/**
 * Create the website tables by running sql/migration_website_templates.sql
 * through the app's own database connection (same as running it in
 * phpMyAdmin). Safe to repeat. Returns a list of error messages ([] = ok).
 */
function site_admin_install(): array
{
    $file = dirname(__DIR__) . '/sql/migration_website_templates.sql';
    if (!is_readable($file)) {
        return ['The file sql/migration_website_templates.sql is missing on the server. Upload it and try again.'];
    }
    $db = get_db();
    $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
    $errors = [];
    foreach (preg_split('/;\s*(?:\R|$)/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        try {
            $db->exec($stmt);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage() . ' — in: ' . mb_strimwidth(preg_replace('/\s+/', ' ', $stmt), 0, 90, '…');
        }
    }
    if (!$errors) {
        try {
            Site::ensureBuiltins();
            Site::syncContent();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    return $errors;
}

// "Install website tables now" button on any Website screen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_install'])) {
    theme_csrf_check();
    $installErrors = site_admin_install();
    if ($installErrors) {
        foreach (array_slice($installErrors, 0, 5) as $err) {
            theme_flash('error', 'Install problem: ' . $err);
        }
    } else {
        theme_flash('success', 'Website tables installed. The Atelier, Heritage and Noir templates are ready.');
    }
    header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'), true, 303);
    exit;
}

function site_admin_missing_notice(): string
{
    $why = (string) ($GLOBALS['site_admin_ready_error'] ?? '');
    return '<div class="panel"><h2>Website tables not installed yet</h2>'
         . '<p class="panel-desc">The templates, page content and enquiries are stored in four new database tables. '
         . 'Take a backup first in <a href="' . e(asset_url('/modules/admin/backup.php')) . '">Backup &amp; Restore</a>, then install them:</p>'
         . '<form method="post" style="margin:16px 0">' . theme_csrf_field()
         . '<button class="btn btn-accent" name="site_install" value="1">Install website tables now</button></form>'
         . '<p class="panel-desc">This runs <code>sql/migration_website_templates.sql</code> for you. It only adds new tables and settings, and is safe to repeat. '
         . 'You can also run that file yourself in phpMyAdmin (SQL tab, with this site\'s database selected).</p>'
         . ($why !== '' ? '<p class="hint">Database said: <code>' . e($why) . '</code></p>' : '')
         . '</div>';
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
