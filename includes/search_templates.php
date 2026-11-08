<?php
/**
 * includes/search_templates.php
 * Templates for the Diamond Search, Results and View Cart / View
 * (Selected) pages — Admin → Search Templates.
 *
 * A template = a LAYOUT (the page markup, a folder under
 * templates/search/<layout>/ holding diamond_search.php, results.php
 * and view_results.php) + its own SETTINGS (colours and font, saved as
 * JSON in search_templates.settings).
 *
 * Built-in layouts:
 *   solitaire — the current design (dark label bars, blue pills,
 *               bordered blue-header results table)
 *   classic   — the design the pages had before that redesign
 *
 * New templates are made from an existing one (same layout, own name
 * and colours); exactly one template is live. An admin can preview any
 * template for themselves only (?tpl_preview=<key>, ended with
 * ?tpl_preview=off) before making it live.
 *
 * The pages in modules/user/ do all the data work (filters, queries,
 * exports, cart/memo actions) and then include the live template's
 * view file, so every template gets the same features.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const SEARCH_TEMPLATE_DIR = __DIR__ . '/../templates/search';
const SEARCH_TEMPLATE_FALLBACK = 'solitaire';

/** Layouts that exist in code, with the colour settings each one uses
 *  (key => [label, CSS default]). */
function search_template_layouts(): array
{
    return [
        'solitaire' => [
            'name' => 'Solitaire Station',
            'settings' => [
                'accent'       => ['Buttons, selected options and Submit', '#2468d8'],
                'label_bg'     => ['Filter label bars and dark buttons', '#3c4150'],
                'table_head'   => ['Results table header row', '#1f6fd1'],
                'row_band'     => ['Results alternate row shading', '#ebebeb'],
                'border'       => ['Results table cell borders', '#a7abb1'],
                'footer_band'  => ['Blue footer band', '#1f6fd1'],
                'font'         => ['Font', 'Arial, Helvetica, sans-serif'],
            ],
        ],
        'classic' => [
            'name' => 'Classic',
            // Classic takes its colours and fonts from Admin → Theme Settings.
            'settings' => [],
        ],
    ];
}

function search_templates_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            get_db()->query('SELECT 1 FROM search_templates LIMIT 1');
            $ready = true;
        } catch (Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

/** All templates, live one first, then by sort order. */
function search_templates_all(): array
{
    if (!search_templates_ready()) {
        return [];
    }
    $rows = get_db()->query('SELECT * FROM search_templates ORDER BY is_active DESC, sort_order, id')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['template_key']] = search_template_normalise($r);
    }
    return $out;
}

function search_template_get(string $key): ?array
{
    if (!search_templates_ready()) {
        return null;
    }
    $st = get_db()->prepare('SELECT * FROM search_templates WHERE template_key = :k');
    $st->execute([':k' => $key]);
    $row = $st->fetch();
    return $row ? search_template_normalise($row) : null;
}

function search_template_normalise(array $r): array
{
    $layouts = search_template_layouts();
    $layout = isset($layouts[$r['layout']]) ? $r['layout'] : SEARCH_TEMPLATE_FALLBACK;
    $saved = json_decode((string)($r['settings'] ?? ''), true);
    $settings = [];
    foreach ($layouts[$layout]['settings'] as $k => [$label, $default]) {
        $settings[$k] = is_array($saved) && isset($saved[$k]) && is_string($saved[$k]) && $saved[$k] !== '' ? $saved[$k] : $default;
    }
    return [
        'key' => (string)$r['template_key'],
        'name' => (string)$r['name'],
        'description' => (string)($r['description'] ?? ''),
        'layout' => $layout,
        'is_builtin' => (int)$r['is_builtin'] === 1,
        'is_active' => (int)$r['is_active'] === 1,
        'settings' => $settings,
    ];
}

/** The template this request should use: an admin's preview, else the live one. */
function search_template_current(): array
{
    static $current = null;
    if ($current !== null) {
        return $current;
    }
    $fallback = [
        'key' => SEARCH_TEMPLATE_FALLBACK, 'name' => 'Solitaire Station', 'description' => '',
        'layout' => SEARCH_TEMPLATE_FALLBACK, 'is_builtin' => true, 'is_active' => true,
        'settings' => array_map(fn($s) => $s[1], search_template_layouts()[SEARCH_TEMPLATE_FALLBACK]['settings']),
    ];
    if (!search_templates_ready()) {
        return $current = $fallback;
    }

    // Admin-only preview, remembered for the session.
    $user = function_exists('current_user') ? current_user() : null;
    $isAdmin = $user !== null && in_array('admin', modules_for_level((int)($user['level'] ?? 0)), true);
    if ($isAdmin && isset($_GET['tpl_preview'])) {
        $want = (string)$_GET['tpl_preview'];
        if ($want === 'off') {
            unset($_SESSION['search_tpl_preview']);
        } elseif (search_template_get($want) !== null) {
            $_SESSION['search_tpl_preview'] = $want;
        }
    }
    if ($isAdmin && !empty($_SESSION['search_tpl_preview'])) {
        $t = search_template_get((string)$_SESSION['search_tpl_preview']);
        if ($t !== null) {
            $t['previewing'] = !$t['is_active'];
            return $current = $t;
        }
    }

    $row = get_db()->query('SELECT * FROM search_templates WHERE is_active = 1 ORDER BY id LIMIT 1')->fetch();
    return $current = ($row ? search_template_normalise($row) : $fallback);
}

/** Path of the view file for $page ('diamond_search', 'results', 'view_results'). */
function search_template_view(string $page): string
{
    $layout = search_template_current()['layout'];
    $file = SEARCH_TEMPLATE_DIR . '/' . $layout . '/' . $page . '.php';
    if (!is_file($file)) {
        $file = SEARCH_TEMPLATE_DIR . '/' . SEARCH_TEMPLATE_FALLBACK . '/' . $page . '.php';
    }
    return $file;
}

function search_template_hex(string $v, string $default): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $default;
}

/** Same colour, $pct darker — for hover states. */
function search_template_darken(string $hex, float $pct = 0.14): string
{
    $hex = ltrim($hex, '#');
    $rgb = array_map(fn($i) => (int)round(hexdec(substr($hex, $i, 2)) * (1 - $pct)), [0, 2, 4]);
    return sprintf('#%02x%02x%02x', ...$rgb);
}

/** <style> applying the template's settings (printed in <head> by
 *  includes/header.php), plus the admin preview notice styling. */
function search_template_head_html(): string
{
    $t = search_template_current();
    $css = '';
    if ($t['layout'] === 'solitaire') {
        $d = array_map(fn($s) => $s[1], search_template_layouts()['solitaire']['settings']);
        $s = $t['settings'];
        $accent = search_template_hex($s['accent'], $d['accent']);
        $label = search_template_hex($s['label_bg'], $d['label_bg']);
        $head = search_template_hex($s['table_head'], $d['table_head']);
        $band = search_template_hex($s['row_band'], $d['row_band']);
        $border = search_template_hex($s['border'], $d['border']);
        $foot = search_template_hex($s['footer_band'], $d['footer_band']);
        $font = preg_replace('/[^A-Za-z0-9 ,\'"\-]/', '', $s['font']) ?: $d['font'];
        $css = "body.ds-v2{--dsv-blue:$accent;--dsv-blue-hover:" . search_template_darken($accent) . ";--dsv-slate:$label;--dsv-slate-hover:" . search_template_darken($label) . ";--dsv-band:$foot;font-family:$font}"
             . "body.rs-v2{--rs-blue:$accent;--rs-blue-hover:" . search_template_darken($accent) . ";--rs-head:$head;--rs-band:$band;--rs-border:$border;--rs-footband:$foot;--checkbox-checked:$accent;--results-table-font:$font;font-family:$font}";
    }
    $css .= '.tpl-preview-bar{background:#fff3cd;color:#5c4400;border-bottom:1px solid #e6d27a;padding:6px 16px;font:14px Arial,Helvetica,sans-serif;text-align:center}'
          . '.tpl-preview-bar a{color:#5c4400;font-weight:700}';
    return '<style id="search-template-style">' . $css . '</style>';
}

/** Notice shown to an admin previewing a template that is not live. */
function search_template_preview_bar(): string
{
    $t = search_template_current();
    if (empty($t['previewing'])) {
        return '';
    }
    return '<div class="tpl-preview-bar" role="status">Previewing the template <strong>' . e($t['name'])
        . '</strong> — only you see this. <a href="' . e(asset_url('/modules/admin/search_templates.php')) . '">Make it live in Search Templates</a> · '
        . '<a href="?tpl_preview=off">Exit preview</a></div>';
}

// ---- Admin actions -------------------------------------------------

function search_template_activate(string $key): bool
{
    if (search_template_get($key) === null) {
        return false;
    }
    $db = get_db();
    $db->beginTransaction();
    $db->exec('UPDATE search_templates SET is_active = 0');
    $st = $db->prepare('UPDATE search_templates SET is_active = 1 WHERE template_key = :k');
    $st->execute([':k' => $key]);
    $db->commit();
    return true;
}

/** New template copied from $fromKey (its layout and settings). Returns the new key. */
function search_template_create(string $fromKey, string $name, string $description): string
{
    $src = search_template_get($fromKey);
    if ($src === null) {
        throw new InvalidArgumentException('Choose an existing template to start from.');
    }
    $name = trim($name);
    if ($name === '') {
        throw new InvalidArgumentException('Give the template a name.');
    }
    $base = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'template';
    $base = substr($base, 0, 30);
    $key = $base;
    for ($i = 2; search_template_get($key) !== null; $i++) {
        $key = substr($base, 0, 26) . '-' . $i;
    }
    $order = (int)get_db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM search_templates')->fetchColumn();
    $st = get_db()->prepare(
        'INSERT INTO search_templates (template_key, name, description, layout, is_builtin, is_active, settings, sort_order)
         VALUES (:k, :n, :d, :l, 0, 0, :s, :o)'
    );
    $st->execute([
        ':k' => $key, ':n' => mb_substr($name, 0, 80), ':d' => mb_substr(trim($description), 0, 255),
        ':l' => $src['layout'], ':s' => json_encode($src['settings']), ':o' => $order,
    ]);
    return $key;
}

/**
 * Colour settings from the edit form. Each colour has a picker
 * (settings[k]), a hex box (settings_hex[k]) and the value the page
 * loaded with (settings_orig[k]). A hex box that was typed into wins;
 * otherwise the picker's value is used — so picking a colour saves
 * correctly whether or not the page's script ran.
 */
function search_template_posted_settings(array $post): array
{
    $picked = (array)($post['settings'] ?? []);
    $typed = (array)($post['settings_hex'] ?? []);
    $orig = (array)($post['settings_orig'] ?? []);
    $out = [];
    foreach ($picked as $k => $v) {
        $k = (string)$k;
        $v = trim((string)$v);
        $t = trim((string)($typed[$k] ?? ''));
        $o = trim((string)($orig[$k] ?? ''));
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $t) && strcasecmp($t, $o) !== 0) {
            $v = $t;
        }
        $out[$k] = $v;
    }
    return $out;
}

/** Save name/description/settings. Built-in templates keep their name and layout but colours can't change. */
function search_template_update(string $key, string $name, string $description, array $settings): void
{
    $t = search_template_get($key);
    if ($t === null) {
        throw new InvalidArgumentException('That template no longer exists.');
    }
    if ($t['is_builtin']) {
        throw new InvalidArgumentException('Built-in templates cannot be changed — create a new template from it instead.');
    }
    $name = trim($name);
    if ($name === '') {
        throw new InvalidArgumentException('Give the template a name.');
    }
    $clean = [];
    foreach (search_template_layouts()[$t['layout']]['settings'] as $k => [$label, $default]) {
        $v = trim((string)($settings[$k] ?? ''));
        $clean[$k] = $k === 'font'
            ? (preg_replace('/[^A-Za-z0-9 ,\'"\-]/', '', $v) ?: $default)
            : search_template_hex($v, $default);
    }
    $st = get_db()->prepare('UPDATE search_templates SET name = :n, description = :d, settings = :s WHERE template_key = :k');
    $st->execute([':n' => mb_substr($name, 0, 80), ':d' => mb_substr(trim($description), 0, 255), ':s' => json_encode($clean), ':k' => $key]);
}

function search_template_delete(string $key): void
{
    $t = search_template_get($key);
    if ($t === null) {
        return;
    }
    if ($t['is_builtin']) {
        throw new InvalidArgumentException('Built-in templates cannot be deleted.');
    }
    if ($t['is_active']) {
        throw new InvalidArgumentException('This template is live — make another template live first.');
    }
    $st = get_db()->prepare('DELETE FROM search_templates WHERE template_key = :k AND is_builtin = 0');
    $st->execute([':k' => $key]);
}
