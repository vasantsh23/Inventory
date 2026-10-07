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

/** Renders one filter section (heading + its pill/shape/checkbox/range controls). */
function render_ds_section(array $section, array $priorFilters, bool $hasPriorFilters): void
{
    $fld = $section['fldname'];
    ?>
    <div class="ds-section" data-fldname="<?= e($fld) ?>">
        <div class="ds-section-header-row">
            <h2 class="ds-section-title"><?= e(ds_format_label($section['label'])) ?></h2>
            <?php if ($fld === 'Weight'): ?>
                <button type="button" class="ds-section-reset-btn" data-reset-section="Weight">Reset</button>
            <?php endif; ?>
        </div>

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
                    // "Fancy" is a specific Color pill (see the query
                    // builder / diamond_search.js): choosing it is
                    // mutually exclusive with every other Color option,
                    // "Others" included.
                    $isColorFancyOpt = ($fld === 'Color' && strcasecmp($opt['label'], 'fancy') === 0);
                ?>
                    <label class="ds-pill<?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?> <?= $isColorFancyOpt ? 'data-color-fancy="1"' : '' ?>>
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
// "Data last updated" info, right-aligned in the footer — the most
// recent Diamond Data Upload's file date/time and run date/time (see
// get_latest_upload_log() / record_diamond_upload_log() in
// includes/functions.php). Silently omitted if no upload has ever
// been logged (e.g. before this feature existed, or the migration
// hasn't been run yet).
$latestUpload = get_latest_upload_log();
if ($latestUpload !== null) {
    $fmtDt = function (?string $date, ?string $time): string {
        if (!$date) {
            return '—';
        }
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . ($time ?? '00:00:00'));
        return $dt ? $dt->format('d M Y, H:i') : e($date);
    };
    $footerExtraRight =
        '<p><strong>File date/time:</strong> ' . e($fmtDt($latestUpload['upldfile_date'] ?? null, $latestUpload['upldfile_time'] ?? null)) . '</p>'
        . '<p><strong>Data uploaded:</strong> ' . e($fmtDt($latestUpload['data_uplddate'] ?? null, $latestUpload['data_upldtime'] ?? null)) . '</p>';
}
require_once __DIR__ . '/../../includes/footer.php';
