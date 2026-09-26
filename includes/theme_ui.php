<?php
/**
 * theme_ui.php
 * Helpers for the Theme Settings admin screens: the right input for each
 * property type (colour picker + text, font dropdown with custom entry,
 * size text, or select), a live preview swatch, flash messages and CSRF.
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/ThemeSettings.php';

function value_control(string $name, string $type, string $value, string $id): string
{
    $v = e($value);
    $n = e($name);
    $i = e($id);

    switch ($type) {
        case 'color':
            $hex = preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value
                 : (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $value, $m) ? "#$m[1]$m[1]$m[2]$m[2]$m[3]$m[3]" : '#000000');
            return '<span class="ts-ctl ts-ctl--color">'
                 . '<input type="color" value="' . e($hex) . '" aria-label="Pick colour" data-sync="' . $i . '">'
                 . '<input type="text" id="' . $i . '" name="' . $n . '" value="' . $v . '" data-type="color" spellcheck="false" autocomplete="off">'
                 . '</span>';

        case 'font_family':
            $known = array_key_exists($value, FONT_LIBRARY);
            $opts = '';
            foreach (FONT_LIBRARY as $stack => [$label]) {
                $opts .= '<option value="' . e($stack) . '"' . ($stack === $value ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            $opts .= '<option value="__custom"' . ($known ? '' : ' selected') . '>Custom font stack…</option>';
            return '<span class="ts-ctl ts-ctl--font">'
                 . '<select data-font-select="' . $i . '" aria-label="Choose font">' . $opts . '</select>'
                 . '<input type="text" id="' . $i . '" name="' . $n . '" value="' . $v . '" data-type="font_family"'
                 . ($known ? ' hidden' : '') . ' placeholder="\'Font Name\', serif" autocomplete="off">'
                 . '</span>';

        case 'font_weight':
        case 'font_style':
        case 'text_transform':
        case 'color_scheme':
            $list = match ($type) {
                'font_weight'    => ThemeSettings::FONT_WEIGHTS,
                'font_style'     => ThemeSettings::FONT_STYLES,
                'text_transform' => ThemeSettings::TEXT_TRANSFORMS,
                'color_scheme'   => ThemeSettings::COLOR_SCHEMES,
            };
            if (!isset($list[$value]) && $value !== '') {
                $list = [$value => $value] + $list;
            }
            $opts = '';
            foreach ($list as $k => $label) {
                $opts .= '<option value="' . e((string) $k) . '"' . ((string) $k === $value ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            return '<select class="ts-ctl" id="' . $i . '" name="' . $n . '" data-type="' . e($type) . '">' . $opts . '</select>';

        default: // font_size, size, number
            $ph = ['font_size' => '16px', 'size' => '24px', 'number' => '1.5'][$type] ?? '';
            return '<input class="ts-ctl ts-ctl--short" type="text" id="' . $i . '" name="' . $n . '" value="' . $v
                 . '" data-type="' . e($type) . '" placeholder="' . $ph . '" autocomplete="off">';
    }
}

/** Small visual sample of the value, updated live by theme_admin.js */
function value_preview(string $type, string $value, string $forId): string
{
    $f = e($forId);
    $value = ThemeSettings::isValidValue($type, $value) ? $value : '';
    return match ($type) {
        'color'          => '<span class="ts-pv ts-pv--swatch" data-preview="' . $f . '" data-kind="color" style="--pv:' . e($value) . '"></span>',
        'font_family'    => '<span class="ts-pv" data-preview="' . $f . '" data-kind="font" style="font-family:' . e($value) . '">Aa Diamonds</span>',
        'font_size'      => '<span class="ts-pv" data-preview="' . $f . '" data-kind="size" style="font-size:' . e($value) . '">Aa</span>',
        'font_weight'    => '<span class="ts-pv" data-preview="' . $f . '" data-kind="weight" style="font-weight:' . e($value) . '">Aa Diamonds</span>',
        'font_style'     => '<span class="ts-pv" data-preview="' . $f . '" data-kind="style" style="font-style:' . e($value) . '">Aa Diamonds</span>',
        'text_transform' => '<span class="ts-pv" data-preview="' . $f . '" data-kind="transform" style="text-transform:' . e($value) . '">round brilliant</span>',
        'size'           => '<span class="ts-pv ts-pv--bar" data-preview="' . $f . '" data-kind="bar" style="--pv:' . e($value) . '"></span>',
        default          => '<span class="ts-pv ts-pv--muted">' . e($value) . '</span>',
    };
}

// ------------------------------------------------------------ page helpers

function theme_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stops the request unless the posted CSRF token is valid */
function theme_csrf_check(): void
{
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Your session expired — please go back and try again.');
    }
}

function theme_flash(string $type, string $msg): void
{
    $_SESSION['theme_flash'][] = ['type' => $type, 'msg' => $msg];
}

function theme_take_flashes(): array
{
    $f = $_SESSION['theme_flash'] ?? [];
    unset($_SESSION['theme_flash']);
    return $f;
}

/** Flash messages rendered with the site's alert styles */
function theme_render_flashes(): string
{
    $map = ['success' => 'alert-success', 'error' => 'alert-error', 'info' => 'alert-warning'];
    $html = '';
    foreach (theme_take_flashes() as $f) {
        $html .= '<div class="alert ' . ($map[$f['type']] ?? 'alert-warning') . ' ts-flash" role="status">' . e($f['msg']) . '</div>';
    }
    return $html;
}

/** Redirect to a path inside modules/admin (never an outside URL) */
function theme_redirect(string $adminPage): void
{
    header('Location: ' . asset_url('/modules/admin/' . ltrim($adminPage, '/')));
    exit;
}
