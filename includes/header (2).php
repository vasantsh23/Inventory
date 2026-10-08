<?php
/**
 * theme.php
 * theme_head_tags() prints the <link> tags every page needs to pick up the
 * theme from the database: the Google Fonts stylesheet for the fonts chosen
 * in theme_settings, and /theme.css.php which turns every setting into a
 * CSS custom property. Used by includes/header.php, includes/admin_header.php
 * and the standalone memo print page, so every page shares one theme.
 *
 * The admin dashboard and the login screen pass $useDefaults = true: they
 * always use each setting's default_value (the default theme), so changes
 * made in Admin → Theme Settings never alter them.
 */

declare(strict_types=1);

require_once __DIR__ . '/ThemeSettings.php';
require_once __DIR__ . '/security.php';

/**
 * @param bool $allLibraryFonts Also load every font in the dropdown library
 *                              (Theme Settings admin, so previews render).
 * @param bool $useDefaults     Use default_value instead of setting_value
 *                              (admin dashboard and login screen).
 */
function theme_head_tags(bool $allLibraryFonts = false, bool $useDefaults = false): string
{
    $version = 'offline';
    $fontValues = [];
    try {
        $version = ThemeSettings::version();
        foreach (ThemeSettings::map() as $row) {
            if ($row['property_type'] === 'font_family') {
                $fontValues[] = $row[$useDefaults ? 'default_value' : 'setting_value'];
            }
        }
    } catch (Throwable $e) {
        // Theme tables not reachable (e.g. migration not run yet) — the page
        // still renders; theme.css.php reports the problem in a CSS comment.
        error_log('Theme settings unavailable: ' . $e->getMessage());
    }
    if ($allLibraryFonts) {
        $fontValues = array_merge($fontValues, array_keys(FONT_LIBRARY));
    }

    $html = '';
    $fontsUrl = google_fonts_url($fontValues);
    if ($fontsUrl) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
               . '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
               . '    <link rel="stylesheet" href="' . e($fontsUrl) . '">' . "\n    ";
    }
    $html .= '<link rel="stylesheet" href="' . e(asset_url('/theme.css.php') . '?v=' . $version
                                            . ($useDefaults ? '&defaults=1' : '')) . '">';
    return $html;
}

/**
 * Theme for the shared header/footer bars on the website pages (Home,
 * About, Contact — site/bootstrap.php).
 *
 * Those pages have their own template stylesheet, which uses some of
 * the same CSS variable names as the theme (--accent, --radius,
 * --text-muted, …). So instead of linking /theme.css.php (which sets
 * the variables on :root and would restyle the whole page), the
 * variables are set only on $selector — the wrapper around the bars —
 * and inherited by the bars alone. Also loads the theme's Google Fonts
 * and assets/css/site-chrome.css (the bars' own styles).
 */
function theme_scoped_head_tags(string $selector): string
{
    $vars = '';
    $fontValues = [];
    try {
        foreach (ThemeSettings::map() as $key => $row) {
            $value = trim((string) $row['setting_value']);
            if (!preg_match('/^[a-z][a-z0-9_-]*$/', (string) $key)
                || !ThemeSettings::isValidValue($row['property_type'], $value)) {
                continue;
            }
            if ($row['property_type'] === 'font_family') {
                $fontValues[] = $value;
            }
            $vars .= '--' . $key . ':' . $value . ';';
        }
    } catch (Throwable $e) {
        error_log('Theme settings unavailable: ' . $e->getMessage());
    }

    $html = '';
    $fontsUrl = google_fonts_url($fontValues);
    if ($fontsUrl) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
               . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
               . '<link rel="stylesheet" href="' . e($fontsUrl) . '">' . "\n";
    }
    // Values are validated above; '<' can't appear in them, so this can't
    // break out of the <style> element.
    $html .= '<style>' . $selector . '{' . str_replace('<', '', $vars) . '}</style>' . "\n";
    $html .= '<link rel="stylesheet" href="' . e(asset_url_versioned('/assets/css/site-chrome.css')) . '">' . "\n";
    return $html;
}
