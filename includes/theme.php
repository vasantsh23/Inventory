<?php
/**
 * theme.php
 * theme_head_tags() prints the <link> tags every page needs to pick up the
 * theme from the database: the Google Fonts stylesheet for the fonts chosen
 * in theme_settings, and /theme.css.php which turns every setting into a
 * CSS custom property. Used by includes/header.php, includes/admin_header.php
 * and the standalone memo print page, so every page shares one theme.
 */

declare(strict_types=1);

require_once __DIR__ . '/ThemeSettings.php';
require_once __DIR__ . '/security.php';

/**
 * @param bool $allLibraryFonts Also load every font in the dropdown library
 *                              (Theme Settings admin, so previews render).
 */
function theme_head_tags(bool $allLibraryFonts = false): string
{
    $version = 'offline';
    $fontValues = [];
    try {
        $version = ThemeSettings::version();
        foreach (ThemeSettings::map() as $row) {
            if ($row['property_type'] === 'font_family') {
                $fontValues[] = $row['setting_value'];
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
    $html .= '<link rel="stylesheet" href="' . e(asset_url('/theme.css.php') . '?v=' . $version) . '">';
    return $html;
}
