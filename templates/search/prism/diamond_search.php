<?php
/**
 * templates/search/prism/diamond_search.php — Prism layout (colourful,
 * modern). Included by modules/user/diamond_search.php after it has
 * prepared all the data (see includes/search_templates.php). Markup
 * only: no queries or actions here, so every template keeps the same
 * features. Styles: assets/css/search_prism.css (body.pz), colours
 * from the template's settings (Admin → Search Templates).
 *
 * Keeps the class names / ids assets/js/diamond_search.js works with:
 * #dsForm, .ds-section[data-fldname], .ds-pill, .ds-shape-btn,
 * .ds-selected, .ds-visually-hidden-input, #dsResetBtn,
 * #dsAdvancedToggleBtn, #dsAdvancedPanel, .ds-section-reset-btn.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

/** Full-width card: Shape, Carat, and any long pill row (Color, Clarity). */
function pz_section_is_wide(array $section): bool
{
    if ($section['kind'] === 'shape' || $section['fldname'] === 'Weight') {
        return true;
    }
    return in_array($section['kind'], ['pill', 'checkbox_grid'], true) && count($section['options'] ?? []) > 8;
}

/** A small icon for each card title (decorative). */
function pz_section_icon(string $fld): string
{
    $paths = [
        'Shape'    => '<path d="M6 3h12l4 6-10 12L2 9zm1.1 2L4.6 8.7h4.2L10.3 5zm6.6 0 1.5 3.7h4.2L16.9 5zM12 5.6 10.6 8.7h2.8zM4.9 10.7l5.6 6.8-2.4-6.8zm5.3 0L12 16l1.8-5.3zm5.7 0-2.4 6.8 5.6-6.8z"/>',
        'Weight'   => '<path d="M12 3a3 3 0 0 1 2.8 4H18a2 2 0 0 1 2 1.6L22 20a1.7 1.7 0 0 1-1.7 2H3.7A1.7 1.7 0 0 1 2 20L4 8.6A2 2 0 0 1 6 7h3.2A3 3 0 0 1 12 3zm0 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>',
        'Color'    => '<path d="M12 3c4.4 0 9 3.1 9 7.5 0 3-2.4 4.5-4.5 4.5H15a1.5 1.5 0 0 0-1 2.6c.6.6.6 1.6 0 2.2-.5.5-1.2.7-2 .7-4.9 0-9-4-9-9s4.1-8.5 9-8.5zM7.5 9a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm3-3.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm5 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>',
        'Clarity'  => '<path d="M11 3a8 8 0 0 1 6.3 12.9l4.4 4.4-1.4 1.4-4.4-4.4A8 8 0 1 1 11 3zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/>',
        'Lab'      => '<path d="M6 2h9l5 5v15H6zm8 1.5V8h4.5zM8.5 13l2.5 2.5 4.5-4.5 1 1-5.5 5.5L7.5 14z" fill-rule="evenodd"/>',
    ];
    $p = $paths[$fld] ?? '<path d="M12 2 21 12 12 22 3 12z"/>';
    return '<svg class="pz-card-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/** Renders the sections as a grid of cards (wide ones span the full width). */
function pz_render_sections(array $sections, array $priorFilters, bool $hasPriorFilters, array $pageFldnames): void
{
    echo '<div class="pz-grid">';
    foreach ($sections as $section) {
        pz_render_section($section, $priorFilters, $hasPriorFilters, $pageFldnames);
    }
    echo '</div>';
}

/** One filter card: icon + title, then its pills / shape tiles / range boxes. */
function pz_render_section(array $section, array $priorFilters, bool $hasPriorFilters, array $pageFldnames): void
{
    $fld = $section['fldname'];
    $label = ds_format_label($section['label']);
    $wide = pz_section_is_wide($section);
    ?>
    <div class="ds-section pz-card<?= $wide ? ' pz-card--wide' : '' ?><?= $section['kind'] === 'shape' ? ' pz-card--shape' : '' ?><?= $fld === 'Weight' ? ' pz-card--carat' : '' ?>" data-fldname="<?= e($fld) ?>"<?= !empty($section['range']) ? ' data-range="1"' : '' ?>>
        <div class="pz-card-head">
            <h2 class="pz-card-title"><?= pz_section_icon($fld) ?><?= e($label) ?></h2>
            <?php if (!empty($section['range'])): ?>
                <span class="pz-card-hint">Click two to select a range</span>
            <?php endif; ?>
            <?php if ($fld === 'Weight'): ?>
                <button type="button" class="ds-section-reset-btn pz-link" data-reset-section="Weight">Reset</button>
            <?php endif; ?>
        </div>

        <?php if ($section['kind'] === 'checkbox'): ?>
            <div class="pz-pills">
                <label class="ds-pill<?= !empty($priorFilters[$fld]) ? ' ds-selected' : '' ?>">
                    <input type="checkbox" name="f[<?= e($fld) ?>]" value="1" class="ds-visually-hidden-input" <?= !empty($priorFilters[$fld]) ? 'checked' : '' ?>>
                    Yes
                </label>
            </div>

        <?php elseif ($section['kind'] === 'range' || $section['kind'] === 'generic_range'): ?>
            <div class="pz-range">
                <label class="pz-field"><span>From</span>
                    <input type="number" step="any" placeholder="0.00" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[<?= e($fld) ?>_from]" value="<?= e((string)($priorFilters[$fld . '_from'] ?? '')) ?>">
                </label>
                <span class="pz-range-dash" aria-hidden="true">–</span>
                <label class="pz-field"><span>To</span>
                    <input type="number" step="any" placeholder="99.99" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[<?= e($fld) ?>_to]" value="<?= e((string)($priorFilters[$fld . '_to'] ?? '')) ?>">
                </label>
            </div>

        <?php elseif ($section['kind'] === 'generic_text'): ?>
            <div class="pz-range">
                <label class="pz-field pz-field--text"><span>Contains</span>
                    <input type="text" class="ds-range-input ds-text-input" aria-label="<?= e($label) ?>" name="f[<?= e($fld) ?>_text]" value="<?= e((string)($priorFilters[$fld . '_text'] ?? '')) ?>">
                </label>
            </div>

        <?php elseif ($section['options'] === []): ?>
            <p class="pz-empty">No options configured yet.</p>

        <?php elseif ($section['kind'] === 'shape'): ?>
            <div class="pz-shapes">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    $isAllOpt = strcasecmp($opt['label'], 'all') === 0;
                ?>
                    <label class="ds-shape-btn pz-shape<?= $checkedAttr ? ' ds-selected' : '' ?><?= $isAllOpt ? ' pz-shape--all' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <?php if ($isAllOpt): ?>
                            <span class="pz-shape-all">All</span>
                            <span class="pz-shape-label">Shapes</span>
                        <?php else: ?>
                            <span class="pz-shape-icon">
                                <?php if (!empty($opt['image'])): ?>
                                    <img src="<?= e($opt['image']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="pz-shape-fallback" aria-hidden="true">◆</span>
                                <?php endif; ?>
                            </span>
                            <span class="pz-shape-label"><?= e($opt['label']) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php else: /* pill and checkbox_grid both render as pills */ ?>
            <div class="pz-pills<?= $fld === 'Weight' ? ' pz-pills--carat' : '' ?>">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    // Same special options as the other layouts (see diamond_search.js):
                    // Color "Fancy" excludes the other colours; Lab OTH / NOCERT stand alone.
                    $isColorFancyOpt = ($fld === 'Color' && strcasecmp($opt['label'], 'fancy') === 0);
                    $isExclusiveOpt = ($fld === 'Lab' && in_array(strtoupper(trim((string)$opt['value'])), ['OTH', 'NOCERT'], true));
                ?>
                    <label class="ds-pill<?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?> <?= $isColorFancyOpt ? 'data-color-fancy="1"' : '' ?> <?= $isExclusiveOpt ? 'data-exclusive="1"' : '' ?>>
                        <?= e($opt['label']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($fld === 'Weight'): ?>
                <div class="pz-range pz-range--carat">
                    <span class="pz-range-caption">or enter carats</span>
                    <label class="pz-field"><span>From</span>
                        <input type="number" step="0.01" min="0" placeholder="0.00" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[Weight_from]" value="<?= e((string)($priorFilters['Weight_from'] ?? '')) ?>">
                    </label>
                    <span class="pz-range-dash" aria-hidden="true">–</span>
                    <label class="pz-field"><span>To</span>
                        <input type="number" step="0.01" min="0" placeholder="99.99" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[Weight_to]" value="<?= e((string)($priorFilters['Weight_to'] ?? '')) ?>">
                    </label>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    // 3 EX / 3 VG / 3EX-VG shortcut card, right after Symmetry (when Cut
    // and Polish are on the page too) — submitted as f[CPS3][], applied by
    // build_maindata_search_where().
    if ($fld === 'Symmetry' && in_array('CutGrade', $pageFldnames, true) && in_array('Polish', $pageFldnames, true)) {
        $priorPreset = (array)($priorFilters['CPS3'] ?? []);
        ?>
        <div class="ds-section pz-card pz-card--presets" data-fldname="CPS3">
            <div class="pz-card-head">
                <h2 class="pz-card-title"><?= pz_section_icon('CPS3') ?>Cut · Polish · Symmetry</h2>
            </div>
            <div class="pz-pills" role="group" aria-label="Cut, Polish and Symmetry shortcuts">
                <?php foreach (['EX' => '3 EX', 'VG' => '3 VG', 'EXVG' => '3EX-VG'] as $val => $text):
                    $on = in_array($val, $priorPreset, true);
                ?>
                    <label class="ds-pill<?= $on ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[CPS3][]" value="<?= e($val) ?>" class="ds-visually-hidden-input" data-preset="1" <?= $on ? 'checked' : '' ?>>
                        <?= e($text) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

$pageTitle = 'Diamond Search';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$bodyClass = 'pz pz-search compact-footer no-footer-gap';
$wideContent = true;
$hideFooterBar = true; // the copyright line is printed in the page's own footer

$pageFldnames = array_merge(array_column($sections, 'fldname'), array_column($advancedSections, 'fldname'));
$dsUser = current_user() ?? [];
$dsUserName = trim((string)($dsUser['username'] ?? ''));
$dsSetup = get_setup() ?? [];
$dsCompany = (string)($dsSetup['company'] ?? APP_NAME);

// "Data last updated": the most recent Diamond Data Upload (omitted if none).
$latestUpload = get_latest_upload_log();
$dsUpdated = '';
if ($latestUpload !== null && !empty($latestUpload['data_uplddate'])) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $latestUpload['data_uplddate'] . ' ' . ($latestUpload['data_upldtime'] ?? '00:00:00'));
    $dsUpdated = $dt ? $dt->format('d M Y, H:i') : (string)$latestUpload['data_uplddate'];
}

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page pz-page">
        <header class="pz-hero">
            <div class="pz-hero-text">
                <p class="pz-eyebrow">Diamond Search</p>
                <h1 class="pz-hero-title">Find your perfect diamond</h1>
                <p class="pz-hero-sub">Pick a shape, a carat range and the qualities that matter to you<?= $dsUpdated !== '' ? ' — stock updated ' . e($dsUpdated) : '' ?>.</p>
            </div>
            <div class="pz-hero-side">
                <?php if ($dsUserName !== ''): ?>
                    <span class="pz-chip pz-chip--glass" title="Signed in"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm0 2c-4.4 0-8 2.2-8 5v2h16v-2c0-2.8-3.6-5-8-5z"/></svg><?= e($dsUserName) ?></span>
                <?php endif; ?>
                <a class="pz-btn pz-btn--glass" href="<?= e(asset_url('/logout.php')) ?>">Log out</a>
            </div>
            <span class="pz-hero-gem" aria-hidden="true"></span>
        </header>

        <form method="post" action="<?= e(asset_url('/modules/user/results.php')) ?>" id="dsForm" class="pz-form">
            <?php if ($sections === [] && $advancedSections === []): ?>
                <div class="pz-card pz-card--wide">
                    <p class="pz-empty">No filters are currently active. An administrator can enable filter fields from the Diamond Search Fields table, and add options to the relevant lookup tables (Shape, Color, Clarity, Cut, Polish, Symmetry, Fluorescence, Lab).</p>
                </div>
            <?php endif; ?>

            <?php pz_render_sections($sections, $priorFilters, $hasPriorFilters, $pageFldnames); ?>

            <?php if ($advancedSections !== []): ?>
                <div class="pz-advanced-row">
                    <button type="button" class="pz-btn pz-btn--outline" id="dsAdvancedToggleBtn" aria-expanded="<?= $advancedStartsOpen ? 'true' : 'false' ?>" aria-controls="dsAdvancedPanel">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 6h10v2H4zm14 0h2v2h-2zM4 11h4v2H4zm8 0h8v2h-8zM4 16h10v2H4zm14 0h2v2h-2zM15 5h2v4h-2zM9 10h2v4H9zm6 5h2v4h-2z"/></svg>
                        Advanced filters
                    </button>
                </div>
                <div class="pz-advanced" id="dsAdvancedPanel" <?= $advancedStartsOpen ? '' : 'hidden' ?>>
                    <?php pz_render_sections($advancedSections, $priorFilters, $hasPriorFilters, $pageFldnames); ?>
                </div>
            <?php endif; ?>

            <?php if ($sections !== [] || $advancedSections !== []): ?>
                <div class="pz-actionbar">
                    <span class="pz-actionbar-count" id="pzFilterCount" aria-live="polite">No filters selected — showing all stock</span>
                    <div class="pz-actionbar-buttons">
                        <button type="button" class="pz-btn pz-btn--ghost" id="dsResetBtn">Reset filters</button>
                        <button type="submit" class="pz-btn pz-btn--primary pz-btn--lg">
                            Show diamonds
                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M13 5l7 7-7 7-1.4-1.4 4.6-4.6H4v-2h12.2l-4.6-4.6z"/></svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>

        <footer class="pz-foot">
            <span>&copy; <?= date('Y') ?> <?= e($dsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></span>
            <?php if ($dsUpdated !== ''): ?>
                <span>Stock last updated <?= e($dsUpdated) ?></span>
            <?php endif; ?>
        </footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_search.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/search_prism.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
