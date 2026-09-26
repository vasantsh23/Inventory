<?php
/** POST-only endpoint for Theme Settings: delete, reset one, reset section, reset all. */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';

require_module_access('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    theme_redirect('theme_settings.php');
}
theme_csrf_check();

// Only ever return to a Theme Settings page inside this app
$back = 'theme_settings.php';
$ref  = (string) ($_SERVER['HTTP_REFERER'] ?? '');
if (preg_match('#/modules/admin/(theme_settings\.php(\?[^\s"<>]*)?)$#', $ref, $m)) {
    $back = $m[1];
}

if (!empty($_POST['delete'])) {
    $row = ThemeSettings::find((int) $_POST['delete']);
    if ($row) {
        ThemeSettings::delete((int) $row['id']);
        theme_flash('success', 'Deleted “' . $row['label'] . '” (--' . $row['setting_key'] . ').');
    }
    theme_redirect($back);
}

if (!empty($_POST['reset'])) {
    $row = ThemeSettings::find((int) $_POST['reset']);
    if ($row) {
        ThemeSettings::resetToDefault((int) $row['id']);
        theme_flash('success', '“' . $row['label'] . '” is back to ' . $row['default_value'] . '.');
    }
    theme_redirect($back);
}

if (!empty($_POST['reset_section'])) {
    $n = ThemeSettings::resetToDefault(null, (int) $_POST['reset_section']);
    theme_flash('success', $n ? "Reset $n settings in this section to their defaults." : 'This section already uses its default values.');
    theme_redirect($back);
}

if (!empty($_POST['reset_all'])) {
    $n = ThemeSettings::resetToDefault();
    theme_flash('success', $n ? "Reset $n settings to their defaults." : 'Every setting already uses its default value.');
    theme_redirect('theme_settings.php');
}

theme_redirect('theme_settings.php');
