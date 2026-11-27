<?php
/**
 * templates/search/luxe/diamond_search.php — Atelier Luxe layout:
 * elegant ivory / black / gold, serif headings, editorial filter rows and
 * a sticky "Your selection" panel. Included by
 * modules/user/diamond_search.php after it has prepared all the data.
 * Markup only. Styles: assets/css/search_luxe.css (body.lx).
 *
 * Keeps the hooks assets/js/diamond_search.js uses: #dsForm,
 * .ds-section[data-fldname], .ds-pill, .ds-shape-btn, .ds-selected,
 * .ds-visually-hidden-input, #dsResetBtn, #dsAdvancedToggleBtn,
 * #dsAdvancedPanel, .ds-section-reset-btn. Live summary:
 * assets/js/search_summary.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

/** One filter row: serif label on the left, options on the right. */
function lx_render_section(array $section, array $priorFilters, bool $hasPriorFilters, array $pageFldnames): void
{
    $fld = $section['fldname'];
    $label = ds_format_label($section['label']);
    $id = 'lx-' . preg_replace('/[^A-Za-z0-9_-]/', '', $fld);
    ?>
    <div class="ds-section lx-row<?= $section['kind'] === 'shape' ? ' lx-row--shape' : '' ?>" id="<?= e($id) ?>" data-title="<?= e($label) ?>" data-fldname="<?= e($fld) ?>"<?= !empty($section['range']) ? ' data-range="1"' : '' ?>>
        <div class="lx-row-label">
            <h2 class="lx-row-title"><?= e($label) ?></h2>
            <?php if (!empty($section['range'])): ?><span class="lx-row-hint">Select two for a range</span><?php endif; ?>
            <?php if ($fld === 'Weight'): ?>
                <button type="button" class="ds-section-reset-btn lx-text-btn" data-reset-section="Weight">Reset</button>
            <?php endif; ?>
        </div>
        <div class="lx-row-control">
        <?php if ($section['kind'] === 'checkbox'): ?>
            <div class="lx-options">
                <label class="ds-pill<?= !empty($priorFilters[$fld]) ? ' ds-selected' : '' ?>">
                    <input type="checkbox" name="f[<?= e($fld) ?>]" value="1" class="ds-visually-hidden-input" <?= !empty($priorFilters[$fld]) ? 'checked' : '' ?>>
                    Yes
                </label>
            </div>

        <?php elseif ($section['kind'] === 'range' || $section['kind'] === 'generic_range'): ?>
            <div class="lx-range">
                <input type="number" step="any" placeholder="Min" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[<?= e($fld) ?>_from]" value="<?= e((string)($priorFilters[$fld . '_from'] ?? '')) ?>">
                <span class="lx-range-to">to</span>
                <input type="number" step="any" placeholder="Max" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[<?= e($fld) ?>_to]" value="<?= e((string)($priorFilters[$fld . '_to'] ?? '')) ?>">
            </div>

        <?php elseif ($section['kind'] === 'generic_text'): ?>
            <div class="lx-range">
                <input type="text" class="ds-range-input ds-text-input lx-text-input" aria-label="<?= e($label) ?>" placeholder="Contains…" name="f[<?= e($fld) ?>_text]" value="<?= e((string)($priorFilters[$fld . '_text'] ?? '')) ?>">
            </div>

        <?php elseif ($section['options'] === []): ?>
            <p class="lx-empty">No options configured yet.</p>

        <?php elseif ($section['kind'] === 'shape'): ?>
            <div class="lx-shapes">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    $isAllOpt = strcasecmp($opt['label'], 'all') === 0;
                ?>
                    <label class="ds-shape-btn lx-shape<?= $checkedAttr ? ' ds-selected' : '' ?><?= $isAllOpt ? ' lx-shape--all' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <span class="lx-shape-icon">
                            <?php if ($isAllOpt): ?>
                                <span class="lx-shape-all">All</span>
                            <?php elseif (!empty($opt['image'])): ?>
                                <img src="<?= e($opt['image']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="lx-shape-all">◇</span>
                            <?php endif; ?>
                        </span>
                        <span class="lx-shape-label"><?= e($isAllOpt ? 'All shapes' : $opt['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php else: /* pill and checkbox_grid */ ?>
            <div class="lx-options<?= $fld === 'Weight' ? ' lx-options--carat' : '' ?>">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    // Color "Fancy" excludes the other colours; Lab OTH / NOCERT stand alone (diamond_search.js).
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
                <div class="lx-range lx-range--carat">
                    <span class="lx-range-to">or exactly</span>
                    <input type="number" step="0.01" min="0" placeholder="Min ct" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[Weight_from]" value="<?= e((string)($priorFilters['Weight_from'] ?? '')) ?>">
                    <span class="lx-range-to">to</span>
                    <input type="number" step="0.01" min="0" placeholder="Max ct" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[Weight_to]" value="<?= e((string)($priorFilters['Weight_to'] ?? '')) ?>">
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
    <?php
    // 3 EX / 3 VG / 3EX-VG shortcut row after Symmetry (f[CPS3][], see build_maindata_search_where()).
    if ($fld === 'Symmetry' && in_array('CutGrade', $pageFldnames, true) && in_array('Polish', $pageFldnames, true)) {
        $priorPreset = (array)($priorFilters['CPS3'] ?? []);
        ?>
        <div class="ds-section lx-row" id="lx-CPS3" data-title="Cut · Polish · Symmetry" data-fldname="CPS3">
            <div class="lx-row-label"><h2 class="lx-row-title">Excellence</h2><span class="lx-row-hint">Cut, polish and symmetry together</span></div>
            <div class="lx-row-control">
                <div class="lx-options" role="group" aria-label="Cut, Polish and Symmetry shortcuts">
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
        </div>
        <?php
    }
}

$pageTitle = 'Diamond Search';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$bodyClass = 'lx lx-search compact-footer no-footer-gap';
$wideContent = true;
$hideFooterBar = true;

$pageFldnames = array_merge(array_column($sections, 'fldname'), array_column($advancedSections, 'fldname'));
$dsUserName = trim((string)((current_user() ?? [])['username'] ?? ''));
$dsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$latestUpload = get_latest_upload_log();
$dsUpdated = '';
if ($latestUpload !== null && !empty($latestUpload['data_uplddate'])) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $latestUpload['data_uplddate'] . ' ' . ($latestUpload['data_upldtime'] ?? '00:00:00'));
    $dsUpdated = $dt ? $dt->format('d F Y, H:i') : (string)$latestUpload['data_uplddate'];
}

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page lx-page">
        <header class="lx-hero">
            <div class="lx-hero-top">
                <span class="lx-eyebrow"><?= e($dsCompany) ?> · Diamond Search</span>
                <span class="lx-hero-user">
                    <?php if ($dsUserName !== ''): ?><?= e($dsUserName) ?> · <?php endif; ?>
                    <a href="<?= e(asset_url('/logout.php')) ?>">Log out</a>
                </span>
            </div>
            <h1 class="lx-hero-title">Discover your diamond</h1>
            <span class="lx-rule" aria-hidden="true"><span></span></span>
            <p class="lx-hero-sub">Select a shape, a carat weight and the qualities you desire.</p>
        </header>

        <form method="post" action="<?= e(asset_url('/modules/user/results.php')) ?>" id="dsForm" class="lx-layout">
            <div class="lx-main">
                <?php if ($sections === [] && $advancedSections === []): ?>
                    <p class="lx-empty">No filters are currently active. An administrator can enable filter fields from the Diamond Search Fields table, and add options to the relevant lookup tables (Shape, Color, Clarity, Cut, Polish, Symmetry, Fluorescence, Lab).</p>
                <?php endif; ?>

                <?php foreach ($sections as $section) { lx_render_section($section, $priorFilters, $hasPriorFilters, $pageFldnames); } ?>

                <?php if ($advancedSections !== []): ?>
                    <div class="lx-advanced-row">
                        <button type="button" class="lx-text-btn lx-advanced-btn" id="dsAdvancedToggleBtn" aria-expanded="<?= $advancedStartsOpen ? 'true' : 'false' ?>" aria-controls="dsAdvancedPanel">
                            Refine further <span aria-hidden="true">+</span>
                        </button>
                    </div>
                    <div class="lx-advanced" id="dsAdvancedPanel" <?= $advancedStartsOpen ? '' : 'hidden' ?>>
                        <?php foreach ($advancedSections as $section) { lx_render_section($section, $priorFilters, $hasPriorFilters, $pageFldnames); } ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($sections !== [] || $advancedSections !== []): ?>
                <aside class="lx-aside">
                    <div class="lx-aside-card">
                        <h2 class="lx-aside-title">Your selection</h2>
                        <p class="lx-aside-count" id="dsFilterSummary" aria-live="polite">No filters selected — showing all stock</p>
                        <ul class="lx-aside-list" id="dsFilterList" hidden></ul>
                        <button type="submit" class="lx-btn lx-btn--primary">View diamonds</button>
                        <button type="button" class="lx-btn lx-btn--plain" id="dsResetBtn">Clear all</button>
                        <?php if ($dsUpdated !== ''): ?>
                            <p class="lx-aside-note">Stock updated <?= e($dsUpdated) ?></p>
                        <?php endif; ?>
                    </div>
                </aside>
            <?php endif; ?>
        </form>

        <footer class="lx-foot">&copy; <?= date('Y') ?> <?= e($dsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_search.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/search_summary.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
