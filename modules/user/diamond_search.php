<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_form.php';

require_module_access('user');

// Sections (and ds_option_submit_value()) live in
// includes/diamond_search_form.php, shared with the mobile app's API.
[$sections, $advancedSections] = ds_build_form_sections();

// "Back to Search" links here with ?restore=1 (no filter values in the
// URL) — the actual prior selections are read back from the session,
// where results.php stored them on the last search submission.
$priorFilters = (!empty($_GET['restore']) && isset($_SESSION['ds_last_filters']))
    ? $_SESSION['ds_last_filters']
    : [];
$hasPriorFilters = !empty($priorFilters);

/** Whether this option should start checked: restore the exact prior
 * selection when returning from Results, otherwise default "All"
 * options to checked on a genuinely fresh visit. */
function ds_option_was_checked(array $section, array $opt, array $priorFilters, bool $hasPriorFilters): bool
{
    $fld = $section['fldname'];
    if ($hasPriorFilters) {
        $submitValue = ds_option_submit_value($section, $opt);
        return in_array($submitValue, (array)($priorFilters[$fld] ?? []), true);
    }
    return strcasecmp($opt['label'], 'all') === 0;
}

/** Whether any advanced-filter field currently has a restored value
 * — if so, the advanced panel should start expanded rather than
 * hidden, so the user's prior "Back to Search" selections are visible. */
function ds_advanced_has_prior_selection(array $advancedSections, array $priorFilters): bool
{
    foreach ($advancedSections as $section) {
        $fld = $section['fldname'];
        if ($section['kind'] === 'checkbox' && !empty($priorFilters[$fld])) {
            return true;
        }
        if (in_array($section['kind'], ['range', 'generic_range'], true) && (
            trim((string)($priorFilters[$fld . '_from'] ?? '')) !== ''
            || trim((string)($priorFilters[$fld . '_to'] ?? '')) !== ''
        )) {
            return true;
        }
        if ($section['kind'] === 'generic_text' && trim((string)($priorFilters[$fld . '_text'] ?? '')) !== '') {
            return true;
        }
        if (!empty($priorFilters[$fld]) && is_array($priorFilters[$fld])) {
            return true;
        }
    }
    return false;
}
$advancedStartsOpen = ds_advanced_has_prior_selection($advancedSections, $priorFilters);

// ---- Page markup comes from the live template (Admin → Search Templates) ----
require_once __DIR__ . '/../../includes/search_templates.php';
require search_template_view('diamond_search');
