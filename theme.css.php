<?php
/**
 * theme.css.php
 * Emits every row of theme_settings as a CSS custom property:
 *   setting_key "header-bg" + value "#fff"  ->  --header-bg: #fff;
 * assets/css/style.css only ever refers to these variables, so
 * changing a row in Admin → Theme Settings changes every page.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/ThemeSettings.php';

header('Content-Type: text/css; charset=utf-8');

try {
    $settings = ThemeSettings::map();
} catch (Throwable $e) {
    error_log('theme.css.php: ' . $e->getMessage());
    http_response_code(500);
    echo "/* Theme could not be loaded from the database: run sql/migration_theme_settings.sql */\n";
    exit;
}

// Long cache is safe: pages link this file with ?v=<settings version>
if (isset($_GET['v'])) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: no-cache');
}

echo "/* Generated from MySQL table theme_settings — edit values in Admin → Theme Settings */\n:root {\n";
foreach ($settings as $key => $row) {
    // Re-validate on output: a hand-edited DB row can never inject CSS
    if (!preg_match('/^[a-z][a-z0-9-]*$/', (string) $key)
        || !ThemeSettings::isValidValue($row['property_type'], (string) $row['setting_value'])) {
        echo '  /* skipped invalid setting: ' . preg_replace('/[^a-z0-9-]/', '', (string) $key) . " */\n";
        continue;
    }
    echo '  --' . $key . ': ' . trim((string) $row['setting_value']) . ";\n";
}
echo "}\n";
