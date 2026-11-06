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

/** Whether a section spans the full card width (Shape, Carat, and any
 * pill row long enough to need it — e.g. Color, Clarity). Everything
 * else is "half" width and flows into the two-column block. */
function ds_section_is_wide(array $section): bool
{
    if ($section['kind'] === 'shape' || $section['fldname'] === 'Weight') {
        return true;
    }
    if (in_array($section['kind'], ['pill', 'checkbox_grid'], true) && count($section['options'] ?? []) > 8) {
        return true;
    }
    return false;
}

/** Renders sections in page order: wide sections as full-width rows,
 * each run of consecutive half-width sections as a two-column block
 * (first half down the left column, the rest down the right). */
function render_ds_layout(array $sections, array $priorFilters, bool $hasPriorFilters, array $pageFldnames): void
{
    $run = [];
    $flush = function () use (&$run, $priorFilters, $hasPriorFilters, $pageFldnames): void {
        if ($run === []) {
            return;
        }
        $leftCount = (int)ceil(count($run) / 2);
        $columns = [array_slice($run, 0, $leftCount), array_slice($run, $leftCount)];
        echo '<div class="ds-cols">';
        foreach ($columns as $col) {
            if ($col === []) {
                continue;
            }
            echo '<div class="ds-col">';
            foreach ($col as $section) {
                render_ds_section($section, $priorFilters, $hasPriorFilters, $pageFldnames);
            }
            echo '</div>';
        }
        echo '</div>';
        $run = [];
    };
    foreach ($sections as $section) {
        if (ds_section_is_wide($section)) {
            $flush();
            render_ds_section($section, $priorFilters, $hasPriorFilters, $pageFldnames);
        } else {
            $run[] = $section;
        }
    }
    $flush();
}

/** Renders one filter section: dark label on the left, its
 * pill/shape/range controls on the right. */
function render_ds_section(array $section, array $priorFilters, bool $hasPriorFilters, array $pageFldnames = []): void
{
    $fld = $section['fldname'];
    $label = ds_format_label($section['label']);
    $wide = ds_section_is_wide($section);
    ?>
    <div class="ds-section ds-row<?= $wide ? ' ds-row--wide' : '' ?><?= $fld === 'Weight' ? ' ds-row--carat' : '' ?><?= $section['kind'] === 'shape' ? ' ds-row--shape' : '' ?>" data-fldname="<?= e($fld) ?>">
        <div class="ds-label-col">
            <h2 class="ds-section-title"><?= e($label) ?></h2>
            <?php if ($fld === 'Weight'): ?>
                <button type="button" class="ds-section-reset-btn" data-reset-section="Weight">Reset <?= e($label) ?>s</button>
            <?php endif; ?>
        </div>

        <div class="ds-control-col">
        <?php if ($section['kind'] === 'checkbox'): ?>
            <div class="ds-pill-row">
                <label class="ds-pill<?= !empty($priorFilters[$fld]) ? ' ds-selected' : '' ?>">
                    <input type="checkbox" name="f[<?= e($fld) ?>]" value="1" class="ds-visually-hidden-input" <?= !empty($priorFilters[$fld]) ? 'checked' : '' ?>>
                    YES
                </label>
            </div>

        <?php elseif ($section['kind'] === 'range' || $section['kind'] === 'generic_range'): ?>
            <div class="ds-range-row">
                <input type="number" step="any" placeholder="0.00" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[<?= e($fld) ?>_from]" value="<?= e((string)($priorFilters[$fld . '_from'] ?? '')) ?>">
                <input type="number" step="any" placeholder="99.99" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[<?= e($fld) ?>_to]" value="<?= e((string)($priorFilters[$fld . '_to'] ?? '')) ?>">
            </div>

        <?php elseif ($section['kind'] === 'generic_text'): ?>
            <div class="ds-range-row">
                <input type="text" class="ds-range-input ds-text-input" aria-label="<?= e($label) ?>" name="f[<?= e($fld) ?>_text]" value="<?= e((string)($priorFilters[$fld . '_text'] ?? '')) ?>">
            </div>

        <?php elseif ($section['options'] === []): ?>
            <p class="ds-empty-note">No options configured yet.</p>

        <?php elseif ($section['kind'] === 'shape'): ?>
            <div class="ds-shape-grid">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    $isAllOpt = strcasecmp($opt['label'], 'all') === 0;
                ?>
                    <label class="ds-shape-btn<?= $checkedAttr ? ' ds-selected' : '' ?><?= $isAllOpt ? ' ds-shape-btn--all' : '' ?><?= (!$isAllOpt && !empty($opt['image'])) ? ' ds-shape-btn--img' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <?php if ($isAllOpt): ?>
                            <span class="ds-shape-all-text">ALL</span>
                        <?php else: ?>
                            <span class="ds-shape-icon">
                                <?php if (!empty($opt['image'])): ?>
                                    <img src="<?= e($opt['image']) ?>" alt="">
                                <?php else: ?>
                                    <span class="ds-shape-fallback">?</span>
                                <?php endif; ?>
                            </span>
                            <span class="ds-shape-label"><?= e($opt['label']) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php else: /* pill and checkbox_grid both render as pills */ ?>
            <div class="ds-pill-row<?= $fld === 'Weight' ? ' ds-carat-grid' : '' ?>">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    // "Fancy" is a specific Color pill (see the query
                    // builder / diamond_search.js): choosing it is
                    // mutually exclusive with every other Color option,
                    // "Others" included.
                    $isColorFancyOpt = ($fld === 'Color' && strcasecmp($opt['label'], 'fancy') === 0);
                ?>
                    <label class="ds-pill<?= $fld === 'Weight' ? ' ds-pill--carat' : '' ?><?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?> <?= $isColorFancyOpt ? 'data-color-fancy="1"' : '' ?>>
                        <?= e($opt['label']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($fld === 'Weight'): ?>
                <div class="ds-range-row ds-carat-manual-range">
                    <input type="number" step="0.01" min="0" placeholder="0.00" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[Weight_from]" value="<?= e((string)($priorFilters['Weight_from'] ?? '')) ?>">
                    <input type="number" step="0.01" min="0" placeholder="99.99" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[Weight_to]" value="<?= e((string)($priorFilters['Weight_to'] ?? '')) ?>">
                </div>
            <?php endif; ?>
            <?php // 3 EX / 3 VG / 3EX-VG shortcuts, under Symmetry, when
                  // Cut, Polish and Symmetry are all on this page. ?>
            <?php if ($fld === 'Symmetry' && in_array('CutGrade', $pageFldnames, true) && in_array('Polish', $pageFldnames, true)): ?>
                <div class="ds-pill-row ds-preset-row">
                    <button type="button" class="ds-pill ds-preset-btn" data-preset="EX">3 EX</button>
                    <button type="button" class="ds-pill ds-preset-btn" data-preset="VG">3 VG</button>
                    <button type="button" class="ds-pill ds-preset-btn" data-preset="EX,VG">3EX-VG</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
    <?php
}

$pageTitle = 'Diamond Search';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$bodyClass = 'ds-search-theme ds-v2 compact-footer';
$wideContent = true;

$pageFldnames = array_merge(array_column($sections, 'fldname'), array_column($advancedSections, 'fldname'));
$dsUser = current_user() ?? [];
$dsUserEmail = trim((string)($dsUser['emailid'] ?? '')) !== '' ? (string)$dsUser['emailid'] : (string)($dsUser['username'] ?? '');

// "Data last updated" info for the blue band under the form — the most
// recent Diamond Data Upload's file date/time and run date/time (see
// get_latest_upload_log() in includes/functions.php). Omitted if no
// upload has ever been logged.
$latestUpload = get_latest_upload_log();
$dsSetup = get_setup() ?? [];
$dsCopyright = '© ' . date('Y') . ' ' . ($dsSetup['company'] ?? APP_NAME) . '. All rights reserved. ' . APP_VERSION;
$hideFooterBar = true; // the copyright line is printed in the blue band instead
$dsUploadBand = '';
if ($latestUpload !== null) {
    $fmtDt = function (?string $date, ?string $time): string {
        if (!$date) {
            return '—';
        }
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . ($time ?? '00:00:00'));
        return $dt ? $dt->format('d M Y \a\t H:i:s') : $date;
    };
    $dsUploadBand = 'File: ' . $fmtDt($latestUpload['upldfile_date'] ?? null, $latestUpload['upldfile_time'] ?? null)
        . '    Last Update: ' . $fmtDt($latestUpload['data_uplddate'] ?? null, $latestUpload['data_upldtime'] ?? null);
}

require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page">
        <div class="ds-toolbar">
            <?php if ($dsUserEmail !== ''): ?>
                <span class="ds-toolbar-email"><?= e($dsUserEmail) ?></span>
            <?php endif; ?>
            <button type="button" class="ds-dark-btn" id="dsResetBtn">RESET FILTERS</button>
            <a class="ds-dark-btn" href="<?= e(asset_url('/logout.php')) ?>">LOGOUT</a>
        </div>

        <form method="post" action="<?= e(asset_url('/modules/user/results.php')) ?>" id="dsForm" class="ds-card">
            <?php if ($sections === [] && $advancedSections === []): ?>
                <div class="panel">
                    <p class="panel-desc">No filters are currently active. An administrator can enable filter fields from the Diamond Search Fields table, and add options to the relevant lookup tables (Shape, Color, Clarity, Cut, Polish, Symmetry, Fluorescence, Lab).</p>
                </div>
            <?php endif; ?>

            <?php render_ds_layout($sections, $priorFilters, $hasPriorFilters, $pageFldnames); ?>

            <?php if ($advancedSections !== []): ?>
                <div class="ds-advanced-toggle-row">
                    <button type="button" class="ds-dark-btn" id="dsAdvancedToggleBtn" aria-expanded="<?= $advancedStartsOpen ? 'true' : 'false' ?>">ADVANCED FILTER</button>
                </div>

                <div class="ds-advanced-panel" id="dsAdvancedPanel" <?= $advancedStartsOpen ? '' : 'hidden' ?>>
                    <?php render_ds_layout($advancedSections, $priorFilters, $hasPriorFilters, $pageFldnames); ?>
                </div>
            <?php endif; ?>

            <?php if ($sections !== [] || $advancedSections !== []): ?>
                <div class="ds-bottom-actions">
                    <button type="submit" class="ds-submit-btn">SUBMIT</button>
                </div>
            <?php endif; ?>
        </form>

        <div class="ds-update-band">
            <?php if ($dsUploadBand !== ''): ?>
                <span class="ds-update-info"><?= e($dsUploadBand) ?></span>
            <?php endif; ?>
            <span class="ds-copyright"><?= e($dsCopyright) ?></span>
        </div>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_search.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/footer.php';
