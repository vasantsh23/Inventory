<?php
/**
 * templates/search/classic/diamond_search.php — Classic layout (the design
 * these pages had before the Solitaire Station redesign).
 * Included by modules/user/diamond_search.php after it has prepared all the
 * data (see includes/search_templates.php). Colours and fonts come
 * from Admin → Theme Settings.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: preview bar for admins

/** Renders one filter section (heading + its pill/shape/checkbox/range controls). */
function render_ds_section(array $section, array $priorFilters, bool $hasPriorFilters): void
{
    $fld = $section['fldname'];
    ?>
    <div class="ds-section" data-fldname="<?= e($fld) ?>"<?= !empty($section['range']) ? ' data-range="1"' : '' ?>>
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
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?> <?= $isColorFancyOpt ? 'data-color-fancy="1"' : '' ?> <?= ($fld === 'Lab' && in_array(strtoupper(trim((string)$opt['value'])), ['OTH', 'NOCERT'], true)) ? 'data-exclusive="1"' : '' ?>>
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
$bodyClass = 'ds-search-theme compact-footer'; // light/teal restyle + tight footer

require_once __DIR__ . '/../../../includes/header.php';
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
require_once __DIR__ . '/../../../includes/footer.php';
