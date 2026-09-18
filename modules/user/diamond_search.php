<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('user');
$applyPublicTheme = true; // this page's content should reflect the Fonts & Colors selection

/**
 * The actual value submitted for a pill/shape checkbox. Weight/Carat
 * buckets encode their numeric range directly; an option labelled
 * "All" always becomes a reserved marker so the query builder can
 * detect "skip this criteria" regardless of whatever real value that
 * lookup row happens to store; an option labelled "Others" on the
 * Shape/Color sections specifically becomes another reserved marker
 * for the "not present in the lookup table" exclusion.
 */
function ds_option_submit_value(array $section, array $opt): string
{
    if ($section['fldname'] === 'Weight') {
        return ($opt['from'] ?? '') . '|' . ($opt['to'] ?? '');
    }
    if (strcasecmp($opt['label'], 'all') === 0) {
        return '__ALL__';
    }
    if (in_array($section['fldname'], ['Shape', 'Color'], true) && strcasecmp($opt['label'], 'others') === 0) {
        return '__OTHERS__';
    }
    return $opt['value'] ?? $opt['label'];
}

$sections = get_diamond_search_sections();
$advancedSections = get_diamond_search_sections('adv_filter');
// A field already shown in the main search must not also render in
// the Advanced panel — duplicate `name` attributes on two different
// inputs breaks form submission (the browser/PHP only keeps one of
// the values, unpredictably), and it's confusing to show the same
// filter twice anyway.
$mainFldnames = array_column($sections, 'fldname');
$advancedSections = array_values(array_filter(
    $advancedSections,
    fn($s) => !in_array($s['fldname'], $mainFldnames, true)
));
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

/** Renders one filter section (heading + its pill/shape/checkbox/range controls). */
function render_ds_section(array $section, array $priorFilters, bool $hasPriorFilters): void
{
    $fld = $section['fldname'];
    ?>
    <div class="ds-section" data-field="<?= e($fld) ?>">
        <?php if ($fld === 'Weight'): ?>
            <div class="ds-section-heading-row">
                <h2 class="ds-section-title"><?= e(ds_format_label($section['label'])) ?></h2>
                <button type="button" class="ds-section-reset-btn" id="dsCaratResetBtn">Reset</button>
            </div>
        <?php else: ?>
            <h2 class="ds-section-title"><?= e(ds_format_label($section['label'])) ?></h2>
        <?php endif; ?>

        <?php if ($section['kind'] === 'checkbox'): ?>
            <label class="ds-checkbox-row">
                <input type="checkbox" name="f[<?= e($fld) ?>]" value="1" <?= !empty($priorFilters[$fld]) ? 'checked' : '' ?>>
                <?= e(ds_format_label($section['label'])) ?>
            </label>

        <?php elseif ($section['kind'] === 'range' || $section['kind'] === 'generic_range'): ?>
            <div class="ds-range-row ds-stepper-row">
                <div class="ds-stepper">
                    <button type="button" class="ds-stepper-btn" data-step="-1" aria-label="Decrease from">&minus;</button>
                    <input type="number" step="any" placeholder="From" class="ds-range-input" name="f[<?= e($fld) ?>_from]" value="<?= e((string)($priorFilters[$fld . '_from'] ?? '')) ?>">
                    <button type="button" class="ds-stepper-btn" data-step="1" aria-label="Increase from">&plus;</button>
                </div>
                <span class="ds-range-sep">–</span>
                <div class="ds-stepper">
                    <button type="button" class="ds-stepper-btn" data-step="-1" aria-label="Decrease to">&minus;</button>
                    <input type="number" step="any" placeholder="To" class="ds-range-input" name="f[<?= e($fld) ?>_to]" value="<?= e((string)($priorFilters[$fld . '_to'] ?? '')) ?>">
                    <button type="button" class="ds-stepper-btn" data-step="1" aria-label="Increase to">&plus;</button>
                </div>
            </div>

        <?php elseif ($section['kind'] === 'generic_text'): ?>
            <div class="ds-range-row">
                <input type="text" placeholder="Search…" class="ds-range-input" name="f[<?= e($fld) ?>_text]" value="<?= e((string)($priorFilters[$fld . '_text'] ?? '')) ?>">
            </div>

        <?php elseif ($section['options'] === []): ?>
            <p class="ds-empty-note">No options configured yet.</p>

        <?php elseif ($section['kind'] === 'shape'): ?>
            <div class="ds-shape-grid">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                ?>
                    <label class="ds-shape-btn<?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <span class="ds-shape-icon">
                            <?php if (!empty($opt['image'])): ?>
                                <img src="<?= e($opt['image']) ?>" alt="<?= e($opt['label']) ?>">
                            <?php else: ?>
                                <span class="ds-shape-fallback">?</span>
                            <?php endif; ?>
                        </span>
                        <span class="ds-shape-label"><?= e($opt['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php elseif ($section['kind'] === 'checkbox_grid'): ?>
            <div class="ds-checkbox-grid">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                ?>
                    <label class="ds-checkbox-item">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" <?= $checkedAttr ?>>
                        <span><?= e($opt['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="ds-pill-row">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                ?>
                    <label class="ds-pill<?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <?= e($opt['label']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($fld === 'Weight'): ?>
                <div class="ds-range-row ds-stepper-row ds-carat-manual-range">
                    <div class="ds-stepper">
                        <button type="button" class="ds-stepper-btn" data-step="-0.01" aria-label="Decrease from">&minus;</button>
                        <input type="number" step="0.01" min="0" placeholder="From" class="ds-range-input" name="f[Weight_from]" value="<?= e((string)($priorFilters['Weight_from'] ?? '')) ?>">
                        <button type="button" class="ds-stepper-btn" data-step="0.01" aria-label="Increase from">&plus;</button>
                    </div>
                    <span class="ds-range-sep">–</span>
                    <div class="ds-stepper">
                        <button type="button" class="ds-stepper-btn" data-step="-0.01" aria-label="Decrease to">&minus;</button>
                        <input type="number" step="0.01" min="0" placeholder="To" class="ds-range-input" name="f[Weight_to]" value="<?= e((string)($priorFilters['Weight_to'] ?? '')) ?>">
                        <button type="button" class="ds-stepper-btn" data-step="0.01" aria-label="Increase to">&plus;</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

$pageTitle = 'Diamond Search';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$bodyClass = 'ds-search-theme'; // scoped light/teal restyle — only this page

require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page">
        <form method="post" action="<?= e(asset_url('/modules/user/results.php')) ?>" id="dsForm">
            <div class="ds-hero">
                <h1 class="ds-title">Diamond Search</h1>
                <div class="ds-actions">
                    <button type="button" class="btn" id="dsResetBtn">Reset</button>
                    <button type="submit" class="btn btn-accent">Search</button>
                </div>
            </div>

            <div class="ds-section ds-stockno-section">
                <h2 class="ds-section-title">Stock No</h2>
                <input type="text" class="ds-range-input ds-stockno-input" name="f[stockno_search]"
                       placeholder="e.g. 20596 24583 26676 (separate multiple with a space)"
                       value="<?= e((string)($priorFilters['stockno_search'] ?? '')) ?>">
            </div>

            <?php if ($sections === [] && $advancedSections === []): ?>
                <div class="panel">
                    <p class="panel-desc">No filters are currently active. An administrator can enable filter fields from the Diamond Search Fields table, and add options to the relevant lookup tables (Shape, Color, Clarity, Cut, Polish, Symmetry, Fluorescence, Lab).</p>
                </div>
            <?php endif; ?>

            <?php foreach ($sections as $section): ?>
                <?php render_ds_section($section, $priorFilters, $hasPriorFilters); ?>
            <?php endforeach; ?>

            <?php if ($advancedSections !== []): ?>
                <div class="ds-advanced-toggle-row">
                    <button type="button" class="btn" id="dsAdvancedToggleBtn" aria-expanded="<?= $advancedStartsOpen ? 'true' : 'false' ?>">Advanced Filter</button>
                </div>

                <div class="ds-advanced-panel" id="dsAdvancedPanel" <?= $advancedStartsOpen ? '' : 'hidden' ?>>
                    <?php foreach ($advancedSections as $section): ?>
                        <?php render_ds_section($section, $priorFilters, $hasPriorFilters); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($sections !== [] || $advancedSections !== []): ?>
                <div class="ds-bottom-actions">
                    <button type="submit" class="btn btn-accent">Search</button>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_search.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
