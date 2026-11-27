<?php
/**
 * templates/search/spectrum/diamond_search.php — Spectrum layout: bold
 * and playful, a dark sidebar with jump links to each filter, and every
 * filter card in its own colour (5 colours, repeating). Included by
 * modules/user/diamond_search.php after it has prepared all the data.
 * Markup only. Styles: assets/css/search_spectrum.css (body.sp).
 *
 * Keeps the hooks assets/js/diamond_search.js uses: #dsForm,
 * .ds-section[data-fldname], .ds-pill, .ds-shape-btn, .ds-selected,
 * .ds-visually-hidden-input, #dsResetBtn, #dsAdvancedToggleBtn,
 * #dsAdvancedPanel, .ds-section-reset-btn. Live summary and the
 * sidebar's "in use" dots: assets/js/search_summary.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

/** Colour number (1–5) for the n-th card. */
function sp_colour(int $n): int
{
    return ($n % 5) + 1;
}

/** The section's anchor id (used by the sidebar jump links). */
function sp_section_id(string $fld): string
{
    return 'sp-' . preg_replace('/[^A-Za-z0-9_-]/', '', $fld);
}

/** One colourful filter card. */
function sp_render_section(array $section, int $colour, array $priorFilters, bool $hasPriorFilters): void
{
    $fld = $section['fldname'];
    $label = ds_format_label($section['label']);
    $wide = $section['kind'] === 'shape' || $fld === 'Weight'
        || (in_array($section['kind'], ['pill', 'checkbox_grid'], true) && count($section['options'] ?? []) > 8);
    ?>
    <div class="ds-section sp-card sp-c<?= $colour ?><?= $wide ? ' sp-card--wide' : '' ?>" id="<?= e(sp_section_id($fld)) ?>" data-title="<?= e($label) ?>" data-fldname="<?= e($fld) ?>"<?= !empty($section['range']) ? ' data-range="1"' : '' ?>>
        <div class="sp-card-head">
            <h2 class="sp-card-title"><span class="sp-blob" aria-hidden="true"></span><?= e($label) ?></h2>
            <?php if (!empty($section['range'])): ?><span class="sp-hint">tap two for a range</span><?php endif; ?>
            <?php if ($fld === 'Weight'): ?>
                <button type="button" class="ds-section-reset-btn sp-mini-btn" data-reset-section="Weight">Reset</button>
            <?php endif; ?>
        </div>

        <?php if ($section['kind'] === 'checkbox'): ?>
            <div class="sp-pills">
                <label class="ds-pill<?= !empty($priorFilters[$fld]) ? ' ds-selected' : '' ?>">
                    <input type="checkbox" name="f[<?= e($fld) ?>]" value="1" class="ds-visually-hidden-input" <?= !empty($priorFilters[$fld]) ? 'checked' : '' ?>>
                    Yes
                </label>
            </div>

        <?php elseif ($section['kind'] === 'range' || $section['kind'] === 'generic_range'): ?>
            <div class="sp-range">
                <input type="number" step="any" placeholder="from" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[<?= e($fld) ?>_from]" value="<?= e((string)($priorFilters[$fld . '_from'] ?? '')) ?>">
                <span class="sp-range-sep" aria-hidden="true">→</span>
                <input type="number" step="any" placeholder="to" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[<?= e($fld) ?>_to]" value="<?= e((string)($priorFilters[$fld . '_to'] ?? '')) ?>">
            </div>

        <?php elseif ($section['kind'] === 'generic_text'): ?>
            <div class="sp-range">
                <input type="text" class="ds-range-input ds-text-input sp-text-input" placeholder="contains…" aria-label="<?= e($label) ?>" name="f[<?= e($fld) ?>_text]" value="<?= e((string)($priorFilters[$fld . '_text'] ?? '')) ?>">
            </div>

        <?php elseif ($section['options'] === []): ?>
            <p class="sp-empty">No options configured yet.</p>

        <?php elseif ($section['kind'] === 'shape'): ?>
            <div class="sp-shapes">
                <?php foreach ($section['options'] as $opt):
                    $checkedAttr = ds_option_was_checked($section, $opt, $priorFilters, $hasPriorFilters) ? 'checked' : '';
                    $isAllOpt = strcasecmp($opt['label'], 'all') === 0;
                ?>
                    <label class="ds-shape-btn sp-shape<?= $checkedAttr ? ' ds-selected' : '' ?>">
                        <input type="checkbox" name="f[<?= e($fld) ?>][]" value="<?= e(ds_option_submit_value($section, $opt)) ?>" class="ds-visually-hidden-input" <?= $checkedAttr ?>>
                        <span class="sp-shape-icon">
                            <?php if ($isAllOpt): ?>
                                <span class="sp-shape-all">ALL</span>
                            <?php elseif (!empty($opt['image'])): ?>
                                <img src="<?= e($opt['image']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="sp-shape-all">◆</span>
                            <?php endif; ?>
                        </span>
                        <span class="sp-shape-label"><?= e($isAllOpt ? 'Any shape' : $opt['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php else: /* pill and checkbox_grid */ ?>
            <div class="sp-pills<?= $fld === 'Weight' ? ' sp-pills--carat' : '' ?>">
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
            <?= ds_section_extras($section, $priorFilters) /* e.g. Nat Fancy Color "Exact" */ ?>
            <?php if ($fld === 'Weight'): ?>
                <div class="sp-range sp-range--carat">
                    <span class="sp-hint">exact carats</span>
                    <input type="number" step="0.01" min="0" placeholder="from" class="ds-range-input" aria-label="<?= e($label) ?> from" name="f[Weight_from]" value="<?= e((string)($priorFilters['Weight_from'] ?? '')) ?>">
                    <span class="sp-range-sep" aria-hidden="true">→</span>
                    <input type="number" step="0.01" min="0" placeholder="to" class="ds-range-input" aria-label="<?= e($label) ?> to" name="f[Weight_to]" value="<?= e((string)($priorFilters['Weight_to'] ?? '')) ?>">
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/** 3 EX / 3 VG / 3EX-VG card (f[CPS3][], see build_maindata_search_where()). */
function sp_render_presets(int $colour, array $priorFilters): void
{
    $priorPreset = (array)($priorFilters['CPS3'] ?? []);
    ?>
    <div class="ds-section sp-card sp-c<?= $colour ?>" id="sp-CPS3" data-title="Cut · Polish · Sym" data-fldname="CPS3">
        <div class="sp-card-head"><h2 class="sp-card-title"><span class="sp-blob" aria-hidden="true"></span>Cut · Polish · Sym</h2></div>
        <div class="sp-pills" role="group" aria-label="Cut, Polish and Symmetry shortcuts">
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

/** All cards of one list, colours continuing from $n; returns the next $n. */
function sp_render_sections(array $sections, int $n, array $priorFilters, bool $hasPriorFilters, array $pageFldnames): int
{
    echo '<div class="sp-grid">';
    foreach ($sections as $section) {
        sp_render_section($section, sp_colour($n++), $priorFilters, $hasPriorFilters);
        if ($section['fldname'] === 'Symmetry' && in_array('CutGrade', $pageFldnames, true) && in_array('Polish', $pageFldnames, true)) {
            sp_render_presets(sp_colour($n++), $priorFilters);
        }
    }
    echo '</div>';
    return $n;
}

$pageTitle = 'Diamond Search';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$bodyClass = 'sp sp-search compact-footer no-footer-gap';
$wideContent = true;
$hideFooterBar = true;

$pageFldnames = array_merge(array_column($sections, 'fldname'), array_column($advancedSections, 'fldname'));
$dsUserName = trim((string)((current_user() ?? [])['username'] ?? ''));
$dsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$latestUpload = get_latest_upload_log();
$dsUpdated = '';
if ($latestUpload !== null && !empty($latestUpload['data_uplddate'])) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $latestUpload['data_uplddate'] . ' ' . ($latestUpload['data_upldtime'] ?? '00:00:00'));
    $dsUpdated = $dt ? $dt->format('d M Y, H:i') : (string)$latestUpload['data_uplddate'];
}
// Sidebar jump links: same order and colours as the cards.
$navItems = [];
$n = 0;
foreach ($sections as $section) {
    $navItems[] = [sp_section_id($section['fldname']), ds_format_label($section['label']), sp_colour($n++)];
    if ($section['fldname'] === 'Symmetry' && in_array('CutGrade', $pageFldnames, true) && in_array('Polish', $pageFldnames, true)) {
        $navItems[] = ['sp-CPS3', '3 EX / 3 VG', sp_colour($n++)];
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page sp-page">
        <form method="post" action="<?= e(asset_url('/modules/user/results.php')) ?>" id="dsForm" class="sp-shell">
            <aside class="sp-side">
                <div class="sp-side-brand">
                    <span class="sp-side-logo" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span>Diamond<br>Search</span>
                </div>
                <?php if ($navItems !== []): ?>
                    <nav class="sp-nav" aria-label="Jump to a filter">
                        <?php foreach ($navItems as [$id, $text, $c]): ?>
                            <a href="#<?= e($id) ?>" class="sp-nav-link sp-c<?= $c ?>" data-section-link="<?= e($id) ?>"><span class="sp-nav-dot" aria-hidden="true"></span><?= e($text) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <div class="sp-side-bottom">
                    <p class="sp-side-count" id="dsFilterSummary" aria-live="polite">No filters selected — showing all stock</p>
                    <?php if ($sections !== [] || $advancedSections !== []): ?>
                        <button type="submit" class="sp-btn sp-btn--go">Search diamonds <span aria-hidden="true">→</span></button>
                        <button type="button" class="sp-btn sp-btn--ghost" id="dsResetBtn">Reset all</button>
                    <?php endif; ?>
                    <p class="sp-side-user"><?= $dsUserName !== '' ? e($dsUserName) . ' · ' : '' ?><a href="<?= e(asset_url('/logout.php')) ?>">Log out</a></p>
                </div>
            </aside>

            <div class="sp-main">
                <header class="sp-head">
                    <h1 class="sp-title">Let’s find your <span class="sp-title-pop">sparkle.</span></h1>
                    <p class="sp-sub">Tap anything you like — mix shapes, colours and grades<?= $dsUpdated !== '' ? '. Stock updated ' . e($dsUpdated) : '' ?>.</p>
                </header>

                <?php if ($sections === [] && $advancedSections === []): ?>
                    <p class="sp-empty">No filters are currently active. An administrator can enable filter fields from the Diamond Search Fields table, and add options to the relevant lookup tables (Shape, Color, Clarity, Cut, Polish, Symmetry, Fluorescence, Lab).</p>
                <?php endif; ?>

                <?php $next = sp_render_sections($sections, 0, $priorFilters, $hasPriorFilters, $pageFldnames); ?>

                <?php if ($advancedSections !== []): ?>
                    <div class="sp-advanced-row">
                        <button type="button" class="sp-btn sp-btn--outline" id="dsAdvancedToggleBtn" aria-expanded="<?= $advancedStartsOpen ? 'true' : 'false' ?>" aria-controls="dsAdvancedPanel">More filters <span aria-hidden="true">＋</span></button>
                    </div>
                    <div class="sp-advanced" id="dsAdvancedPanel" <?= $advancedStartsOpen ? '' : 'hidden' ?>>
                        <?php sp_render_sections($advancedSections, $next, $priorFilters, $hasPriorFilters, $pageFldnames); ?>
                    </div>
                <?php endif; ?>

                <footer class="sp-foot">&copy; <?= date('Y') ?> <?= e($dsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
            </div>
        </form>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_search.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/search_summary.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
