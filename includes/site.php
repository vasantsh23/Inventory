<?php
/**
 * site.php
 * Engine for the public website (Home, About, Services, Diamonds,
 * Responsible Practices, Sustainability, Contact).
 *
 *  - Templates live in /templates/<key>/. Each one has:
 *      template.php  manifest + the section layout of every page
 *      sections.php  the section renderers used by that layout
 *      header.php / footer.php
 *    and its stylesheet in /assets/css/site/<key>.css.
 *  - The active template is stored in site_options.active_template and
 *    chosen in Admin → Appearance → Website Template.
 *  - Page text, images and icons come from the site_content table
 *    (Admin → Manage Tables → Website Content). Every template renders
 *    the same content, so switching templates never loses anything.
 *  - Colours and fonts come from theme_settings (one section per
 *    template), served as CSS custom properties by /theme.css.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/theme.php';
require_once __DIR__ . '/site_icons.php';

/** Public pages: key => file, menu label and default browser title. */
const SITE_PAGES = [
    'home'           => ['file' => 'index.php',                 'label' => 'Home',                  'title' => 'Home'],
    'about'          => ['file' => 'about.php',                 'label' => 'About',                 'title' => 'About Us'],
    'services'       => ['file' => 'services.php',              'label' => 'Services',              'title' => 'Services'],
    'diamonds'       => ['file' => 'diamonds.php',              'label' => 'Diamonds',              'title' => 'Diamonds'],
    'responsible'    => ['file' => 'responsible-practices.php', 'label' => 'Responsible Practices', 'title' => 'Responsible Practices'],
    'sustainability' => ['file' => 'sustainability.php',        'label' => 'Sustainability',        'title' => 'Sustainability'],
    'contact'        => ['file' => 'contact.php',               'label' => 'Contact Us',            'title' => 'Contact Us'],
];

const SITE_DEFAULT_TEMPLATE = 'maison';

// ======================================================= template registry

final class SiteTemplates
{
    private static ?array $registry = null;

    /** @return array<string,array> every template manifest, keyed by template key */
    public static function all(): array
    {
        if (self::$registry !== null) {
            return self::$registry;
        }
        self::$registry = [];
        foreach (glob(dirname(__DIR__) . '/templates/*/template.php') ?: [] as $file) {
            $key = basename(dirname($file));
            if (!preg_match('/^[a-z][a-z0-9_-]{1,30}$/', $key)) {
                continue;
            }
            $manifest = require $file;
            if (is_array($manifest)) {
                $manifest['key'] = $key;
                self::$registry[$key] = $manifest;
            }
        }
        uasort(self::$registry, fn($a, $b) => ($a['sort'] ?? 99) <=> ($b['sort'] ?? 99));
        return self::$registry;
    }

    public static function exists(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? self::all()[SITE_DEFAULT_TEMPLATE] ?? reset(self::$registry);
    }

    /** The template visitors see, as chosen in the admin. */
    public static function activeKey(): string
    {
        $key = site_option('active_template', SITE_DEFAULT_TEMPLATE);
        return self::exists($key) ? $key : SITE_DEFAULT_TEMPLATE;
    }

    /**
     * The template for this request: an admin previewing another
     * template sees that one; everyone else sees the active template.
     */
    public static function currentKey(): string
    {
        $preview = self::previewKey();
        return $preview ?? self::activeKey();
    }

    public static function previewKey(): ?string
    {
        if (!site_user_is_admin()) {
            return null;
        }
        $key = (string) ($_SESSION['site_preview_template'] ?? '');
        return ($key !== '' && self::exists($key) && $key !== self::activeKey()) ? $key : null;
    }

    public static function setActive(string $key): void
    {
        if (!self::exists($key)) {
            throw new InvalidArgumentException('Unknown template: ' . $key);
        }
        site_option_set('active_template', $key);
    }

    /** theme_sections.id holding this template's colours and fonts (or null). */
    public static function themeSectionId(string $key): ?int
    {
        $sectionKey = self::get($key)['theme_section'] ?? '';
        try {
            $st = get_db()->prepare('SELECT id FROM theme_sections WHERE section_key = ?');
            $st->execute([$sectionKey]);
            $id = $st->fetchColumn();
            return $id !== false ? (int) $id : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

function site_user_is_admin(): bool
{
    return is_logged_in() && (int) (current_user()['level'] ?? 0) >= 8;
}

// ============================================================ site options

function site_option(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = get_db()->query('SELECT option_key, option_value FROM site_options')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $e) {
            error_log('site_options unavailable (run sql/migration_site_templates.sql): ' . $e->getMessage());
            $cache = [];
        }
    }
    $v = $cache[$key] ?? null;
    return ($v === null || $v === '') ? $default : (string) $v;
}

function site_option_set(string $key, string $value): void
{
    get_db()->prepare(
        'INSERT INTO site_options (option_key, option_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
    )->execute([$key, $value]);
}

// ================================================================= content

/** All active site_content rows, grouped [page][block][] in display order. */
function site_content_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = get_db()->query(
            "SELECT * FROM site_content WHERE active = 'yes' ORDER BY page, block, sort_order, id"
        )->fetchAll();
        foreach ($rows as $r) {
            $cache[$r['page']][$r['block']][] = $r;
        }
    } catch (Throwable $e) {
        error_log('site_content unavailable (run sql/migration_site_templates.sql): ' . $e->getMessage());
    }
    return $cache;
}

/** Heading row for a list block: the "{block}_intro" block (title, subtitle, body, link). */
function site_intro(string $page, string $block): array
{
    return site_block($page, $block . '_intro');
}

/** Every row of one block (lists such as services, steps, FAQs). */
function site_list(string $page, string $block): array
{
    return site_content_all()[$page][$block] ?? [];
}

/** First row of a block (single sections such as the hero or intro). */
function site_block(string $page, string $block): array
{
    return site_list($page, $block)[0] ?? [];
}

/** Replace {company}, {year}, {email}, {phone} and {address} in admin-entered text. */
function site_text(?string $text): string
{
    $setup = get_setup() ?? [];
    return strtr((string) $text, [
        '{company}' => (string) ($setup['company'] ?? APP_NAME),
        '{year}'    => date('Y'),
        '{email}'   => primary_email($setup),
        '{phone}'   => primary_phone($setup),
        '{address}' => format_address($setup),
    ]);
}

/** Escaped single-line text. */
function st(?string $text): string
{
    return e(site_text($text));
}

/** Escaped multi-paragraph text: blank lines start a paragraph, single line breaks become <br>. */
function site_paras(?string $text, string $class = ''): string
{
    $text = trim(str_replace("\r\n", "\n", site_text($text)));
    if ($text === '') {
        return '';
    }
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
    $html = '';
    foreach (preg_split("/\n\s*\n/", $text) as $p) {
        $html .= '<p' . $cls . '>' . nl2br(e(trim($p)), false) . '</p>';
    }
    return $html;
}

/** URL for an admin-entered link: site paths get BASE_URL; only http(s)/mailto/tel/# are allowed. */
function site_href(?string $url): string
{
    $url = trim(site_text($url));
    if ($url === '') {
        return '#';
    }
    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        return asset_url($url);
    }
    if (preg_match('#^(https?://|mailto:|tel:|\#)#i', $url)) {
        return $url;
    }
    // Bare page names such as "about.php"
    return preg_match('#^[a-z0-9_\-./]+$#i', $url) ? asset_url('/' . $url) : '#';
}

/** Public URL of an image path, or '' when empty / not a local image. */
function site_img(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path; // note: the site's CSP only allows images from this domain
    }
    return asset_url('/' . ltrim($path, '/'));
}

/** Built-in artwork used when a section has no image set. */
function site_art(string $name): string
{
    $allowed = ['brilliant', 'profile', 'rough'];
    return asset_url('/assets/img/site/' . (in_array($name, $allowed, true) ? $name : 'brilliant') . '.svg');
}

/**
 * <img> for a content row: its own image when set, otherwise the
 * template's fallback artwork.
 */
function site_picture(array $row, string $fallbackArt, string $class = '', string $alt = ''): string
{
    $src = site_img($row['image'] ?? '');
    $isArt = $src === '';
    if ($isArt) {
        $src = site_art($fallbackArt);
    }
    $alt = $alt !== '' ? $alt : site_text($row['title'] ?? '');
    return '<img class="' . e(trim($class . ($isArt ? ' is-art' : ' is-photo'))) . '" src="' . e($src) . '" alt="'
         . e($isArt ? '' : $alt) . '" loading="lazy" decoding="async">';
}

// ======================================================== company details

function site_setup(): array
{
    return get_setup() ?? [];
}

function site_company(): string
{
    return (string) (site_setup()['company'] ?? APP_NAME);
}

/**
 * Logo URL, or '' if the file is missing (templates then show the
 * company name as a wordmark). $onDark picks setup.`Logo-2` — a light
 * version of the logo — for dark headers and footers when one is set.
 */
function site_logo(bool $onDark = false): string
{
    $setup = site_setup();
    $path = '';
    if ($onDark && !empty($setup['Logo-2'])) {
        $path = (string) $setup['Logo-2'];
    } else {
        try {
            $path = get_logo_path();
        } catch (Throwable $e) {
            $path = '';
        }
    }
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return is_file(dirname(__DIR__) . '/' . ltrim($path, '/')) ? asset_url($path) : '';
}

/** Brand block: logo image, or the company name when there is no logo file. */
function site_brand(bool $onDark = false, string $class = 'brand'): string
{
    $logo = site_logo($onDark);
    $name = site_company();
    $inner = $logo !== ''
        ? '<img src="' . e($logo) . '" alt="' . e($name) . '" class="' . e($class) . '__logo">'
        : '<span class="' . e($class) . '__name">' . e($name) . '</span>';
    return '<a class="' . e($class) . '" href="' . e(site_page_url('home')) . '">' . $inner . '</a>';
}

function site_social_links(): array
{
    try {
        return get_social_media_links(site_setup());
    } catch (Throwable $e) {
        return [];
    }
}

function site_social_html(string $class = 'social'): string
{
    $links = site_social_links();
    if ($links === []) {
        return '';
    }
    $html = '<ul class="' . e($class) . '">';
    foreach ($links as $l) {
        $img = preg_match('#^https?://#i', $l['image']) ? $l['image'] : asset_url($l['image']);
        $html .= '<li><a href="' . e($l['url'] !== '' ? $l['url'] : '#') . '" target="_blank" rel="noopener noreferrer" title="'
               . e($l['title']) . '"><img src="' . e($img) . '" alt="' . e($l['title']) . '" width="22" height="22"></a></li>';
    }
    return $html . '</ul>';
}

function site_hours(): array
{
    try {
        return get_business_hours();
    } catch (Throwable $e) {
        return [];
    }
}

/** Address lines from setup (address-1 … address-6), non-empty only. */
function site_address_lines(): array
{
    $setup = site_setup();
    $lines = [];
    for ($i = 1; $i <= 6; $i++) {
        if (!empty($setup["address-$i"])) {
            $lines[] = (string) $setup["address-$i"];
        }
    }
    return $lines;
}

function site_phones(): array
{
    $setup = site_setup();
    return array_values(array_filter([$setup['telno-1'] ?? '', $setup['telno-2'] ?? '', $setup['telno-3'] ?? ''],
        fn($v) => trim((string) $v) !== ''));
}

function site_tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

// ============================================================== navigation

function site_page_url(string $page): string
{
    return asset_url('/' . (SITE_PAGES[$page]['file'] ?? 'index.php'));
}

function site_inventory_url(): string
{
    return asset_url('/inventory.php');
}

/** Menu items with the current page marked. */
function site_nav(string $current): array
{
    $items = [];
    foreach (SITE_PAGES as $key => $p) {
        $items[] = ['key' => $key, 'label' => $p['label'], 'url' => site_page_url($key), 'active' => $key === $current];
    }
    return $items;
}

function site_nav_html(string $current, string $class = 'nav'): string
{
    $html = '<ul class="' . e($class) . '__list">';
    foreach (site_nav($current) as $item) {
        $html .= '<li><a class="' . e($class) . '__link' . ($item['active'] ? ' is-active' : '') . '" href="' . e($item['url']) . '"'
               . ($item['active'] ? ' aria-current="page"' : '') . '>' . e($item['label']) . '</a></li>';
    }
    return $html . '</ul>';
}

// =================================================================== head

/** Google Fonts link covering only the active template's font settings. */
function site_fonts_link(string $prefix): string
{
    $fonts = [];
    try {
        foreach (ThemeSettings::map() as $key => $row) {
            if ($row['property_type'] === 'font_family' && str_starts_with((string) $key, $prefix . '-')) {
                $fonts[] = $row['setting_value'];
            }
        }
    } catch (Throwable $e) {
        return '';
    }
    $url = google_fonts_url($fonts);
    if (!$url) {
        return '';
    }
    return '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
         . '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
         . '    <link rel="stylesheet" href="' . e($url) . '">';
}

function site_head(string $page, array $tpl): string
{
    $setup = site_setup();
    $company = site_company();
    $hero = site_block($page, 'hero');
    $pageTitle = SITE_PAGES[$page]['title'] ?? '';
    $title = $page === 'home'
        ? (string) ($setup['Meta Title'] ?? $setup['Page title'] ?? $company)
        : $pageTitle . ' | ' . $company;
    if (trim($title) === '') {
        $title = $company;
    }
    $desc = $page === 'home'
        ? (string) ($setup['Meta Desc'] ?? $setup['Page Desc'] ?? '')
        : site_text($hero['body'] ?? '');
    if (trim($desc) === '') {
        $desc = (string) ($setup['Page Desc'] ?? '');
    }

    $version = 'offline';
    try {
        $version = ThemeSettings::version();
    } catch (Throwable $e) {
    }

    $h  = '<meta charset="UTF-8">' . "\n";
    $h .= '    <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    $h .= '    <title>' . e($title) . '</title>' . "\n";
    if (trim($desc) !== '') {
        $h .= '    <meta name="description" content="' . e(mb_substr(trim($desc), 0, 300)) . '">' . "\n";
    }
    if (!empty($setup['Meta Keyword'])) {
        $h .= '    <meta name="keywords" content="' . e($setup['Meta Keyword']) . '">' . "\n";
    }
    $h .= '    <meta property="og:title" content="' . e((string) ($setup['OG Title'] ?? '') ?: $title) . '">' . "\n";
    if (!empty($setup['OG Desc']) || trim($desc) !== '') {
        $h .= '    <meta property="og:description" content="' . e((string) ($setup['OG Desc'] ?? '') ?: $desc) . '">' . "\n";
    }
    if (!empty($setup['OG image'])) {
        $h .= '    <meta property="og:image" content="' . e(site_img($setup['OG image'])) . '">' . "\n";
    }
    if (!empty($setup['Favicon'])) {
        $h .= '    <link rel="icon" href="' . e(site_img($setup['Favicon'])) . '">' . "\n";
    }
    $h .= '    ' . site_fonts_link((string) $tpl['prefix']) . "\n";
    $h .= '    <link rel="stylesheet" href="' . e(asset_url('/theme.css.php') . '?v=' . $version) . '">' . "\n";
    $h .= '    <link rel="stylesheet" href="' . e(asset_url_versioned('/assets/css/site/base.css')) . '">' . "\n";
    $h .= '    <link rel="stylesheet" href="' . e(asset_url_versioned('/assets/css/site/' . $tpl['key'] . '.css')) . '">';
    return $h;
}

// ================================================================= render

/**
 * Render one public page with the current template. The template's
 * manifest lists, per page, which content blocks appear and which of
 * its section layouts renders each one — so the same content gets a
 * different page structure in every template.
 */
function site_render(string $page): void
{
    if (!isset(SITE_PAGES[$page])) {
        http_response_code(404);
        $page = 'home';
    }
    site_handle_preview_request();

    $tpl = SiteTemplates::get(SiteTemplates::currentKey());
    $dir = dirname(__DIR__) . '/templates/' . $tpl['key'];
    require_once $dir . '/sections.php';

    $prefix = (string) $tpl['prefix'];
    $layout = $tpl['pages'][$page] ?? [];

    // Variables available to header.php / footer.php
    $siteHead = site_head($page, $tpl);
    $currentPage = $page;
    // Headers that sit over a dark hero need to know whether the page starts with one
    $hasHero = ($layout[0]['block'] ?? '') === 'hero' && site_list($page, 'hero') !== [];

    require $dir . '/header.php';
    echo '<main id="main" class="site-main">' . "\n";
    foreach ($layout as $i => $section) {
        $block = (string) ($section['block'] ?? '');
        $fn = $prefix . '_' . ($section['as'] ?? $block);
        $always = !empty($section['always']);
        if (!$always && site_list($page, $block) === []) {
            continue; // nothing entered for this block: leave the section out
        }
        if (!function_exists($fn)) {
            echo '<!-- missing section renderer: ' . e($fn) . ' -->';
            continue;
        }
        $fn($page, $block, $section + ['index' => $i]);
    }
    echo "</main>\n";
    require $dir . '/footer.php';
}

/** ?preview=<key> (admins only) switches this browser to another template until ?preview=off. */
function site_handle_preview_request(): void
{
    if (!isset($_GET['preview']) || !site_user_is_admin()) {
        return;
    }
    $key = (string) $_GET['preview'];
    if ($key === 'off' || $key === SiteTemplates::activeKey()) {
        unset($_SESSION['site_preview_template']);
    } elseif (SiteTemplates::exists($key)) {
        $_SESSION['site_preview_template'] = $key;
    }
}

/** Bar shown only to an admin who is previewing a template that is not live. */
function site_preview_bar(): string
{
    $key = SiteTemplates::previewKey();
    if ($key === null) {
        return '';
    }
    $tpl = SiteTemplates::get($key);
    $live = SiteTemplates::get(SiteTemplates::activeKey());
    $self = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: site_page_url('home');
    return '<div class="site-preview-bar" role="status">'
        . '<span>Previewing <strong>' . e($tpl['name']) . '</strong>. Visitors still see ' . e($live['name']) . '.</span>'
        . '<a href="' . e(asset_url('/modules/admin/site_template.php')) . '">Choose template</a>'
        . '<a href="' . e($self . '?preview=off') . '">Exit preview</a>'
        . '</div>';
}

function site_scripts(): string
{
    return '<script src="' . e(asset_url_versioned('/assets/js/site.js')) . '" defer></script>';
}

// ============================================================ contact form

const SITE_CONTACT_FIELDS = ['name', 'company', 'email', 'phone', 'location', 'subject', 'message'];

/** Handle a POST from the contact form, then redirect (post/redirect/get). */
function site_contact_submit(): void
{
    $back = site_page_url('contact') . '#contact-form';
    $input = [];
    foreach (SITE_CONTACT_FIELDS as $f) {
        $input[$f] = trim((string) ($_POST[$f] ?? ''));
    }

    $errors = [];
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please send the form again.';
    }
    // Honeypot: real visitors never see or fill this field
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        $_SESSION['site_contact'] = ['sent' => true];
        header('Location: ' . $back, true, 303);
        exit;
    }
    // Simple rate limit: one message per 30 seconds per session
    if (isset($_SESSION['site_contact_last']) && time() - (int) $_SESSION['site_contact_last'] < 30) {
        $errors['form'] = 'Please wait a moment before sending another message.';
    }
    if ($input['name'] === '' || mb_strlen($input['name']) > 120) {
        $errors['name'] = 'Enter your name.';
    }
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($input['email']) > 150) {
        $errors['email'] = 'Enter an email address like name@company.com.';
    }
    if ($input['phone'] !== '' && !preg_match('/^[0-9+().\-\s]{6,30}$/', $input['phone'])) {
        $errors['phone'] = 'Use digits, spaces and + ( ) - only.';
    }
    if ($input['message'] === '' || mb_strlen($input['message']) < 5) {
        $errors['message'] = 'Tell us what you are looking for.';
    } elseif (mb_strlen($input['message']) > 5000) {
        $errors['message'] = 'Keep the message under 5,000 characters.';
    }
    foreach (['company' => 150, 'location' => 120, 'subject' => 150] as $f => $max) {
        if (mb_strlen($input[$f]) > $max) {
            $errors[$f] = 'Keep this under ' . $max . ' characters.';
        }
    }

    if ($errors) {
        $_SESSION['site_contact'] = ['errors' => $errors, 'old' => $input];
        header('Location: ' . $back, true, 303);
        exit;
    }

    try {
        get_db()->prepare(
            'INSERT INTO contact_messages (name, company, email, phone, location, subject, message, ip_address, status)
             VALUES (?,?,?,?,?,?,?,?, \'new\')'
        )->execute([
            $input['name'], $input['company'] ?: null, encrypt_value($input['email']), encrypt_value($input['phone'] ?: null),
            $input['location'] ?: null, $input['subject'] ?: null, $input['message'], substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ]);
    } catch (Throwable $e) {
        error_log('Contact form save failed: ' . $e->getMessage());
        $_SESSION['site_contact'] = ['errors' => ['form' => 'Your message could not be sent. Please email or call us instead.'], 'old' => $input];
        header('Location: ' . $back, true, 303);
        exit;
    }

    // Notify the company inbox when the server can send mail. Visitor
    // input only ever goes into the body and a validated Reply-To.
    $to = primary_email(site_setup());
    if ($to !== '' && function_exists('mail')) {
        $body = "New enquiry from the website\n\n";
        foreach (SITE_CONTACT_FIELDS as $f) {
            if ($input[$f] !== '') {
                $body .= ucfirst($f) . ': ' . $input[$f] . "\n";
            }
        }
        $headers = 'Reply-To: ' . str_replace(["\r", "\n"], '', $input['email']) . "\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($to, 'Website enquiry from ' . str_replace(["\r", "\n"], ' ', $input['name']), $body, $headers);
    }

    $_SESSION['site_contact_last'] = time();
    $_SESSION['site_contact'] = ['sent' => true];
    header('Location: ' . $back, true, 303);
    exit;
}

/** State for rendering the form (errors, previous input, success), consumed once. */
function site_contact_state(): array
{
    static $state = null;
    if ($state === null) {
        $state = $_SESSION['site_contact'] ?? [];
        unset($_SESSION['site_contact']);
    }
    return $state + ['errors' => [], 'old' => [], 'sent' => false];
}

/** The contact form markup (shared by every template; each styles .site-form its own way). */
function site_contact_form(string $submitLabel = 'Send message'): string
{
    ob_start();
    require dirname(__DIR__) . '/templates/_shared/contact_form.php';
    return (string) ob_get_clean();
}
