<?php
/**
 * templates/search/spectrum/results.php — Spectrum layout. Included by
 * modules/user/results.php after it has prepared all the data. Markup
 * only; button / form ids as used by results_toolbar.js and
 * memo_actions.js. Table / Grid switch: assets/js/results_view_toggle.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true;

$hideFooterBar = true;
$bodyClass = 'sp sp-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';
$hasRows = $columns !== [] && $total > 0;

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page sp-page sp-page--results">
        <header class="sp-rhead">
            <div class="sp-rhead-main">
                <p class="sp-kicker">Results</p>
                <h1 class="sp-title"><span class="sp-title-pop"><?= number_format($total) ?></span> <?= $total === 1 ? 'diamond' : 'diamonds' ?> found</h1>
                <?php if ($appliedFilters !== []): ?>
                    <div class="sp-chips" aria-label="Your filters">
                        <?php foreach ($appliedFilters as $i => $af): ?>
                            <span class="filter-tag sp-c<?= ($i % 5) + 1 ?>"><strong><?= e($af['label']) ?></strong> <?= e($af['value']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sp-rhead-side">
                <a class="sp-btn sp-btn--outline" href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>"><?= rs_icon('filter') ?>Edit filters</a>
                <a class="sp-btn sp-btn--ghost" href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>">New search</a>
            </div>
        </header>

        <div class="sp-toolbar">
            <?php if ($canMemo && $resultsError === ''): ?>
                <?php $pickerClass = 'sp-customer'; require __DIR__ . '/../_parts/customer_picker.php'; ?>
                <div class="sp-toolbar-group">
                    <button type="button" class="sp-tool sp-c4" id="memo1Btn"><?= rs_icon('eye') ?>Memo-1</button>
                    <button type="button" class="sp-tool sp-c4" id="memo3Btn"><?= rs_icon('eye') ?>Memo-3</button>
                    <a class="sp-tool sp-c4" href="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">+ Customer</a>
                </div>
            <?php endif; ?>
            <div class="sp-toolbar-group">
                <?php if ($hasRows): ?>
                    <button type="button" class="sp-tool sp-c5" id="viewSelectedBtn"><?= rs_icon('eye') ?>View selected</button>
                    <button type="button" class="sp-tool sp-c3" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="sp-tool sp-c2" id="excelSelectBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                <?php endif; ?>
                <?php if ($columns !== []): ?>
                    <a class="sp-tool sp-c2" href="?export=xlsx_all"><?= rs_icon('xls') ?>Complete stock</a>
                <?php endif; ?>
            </div>
            <div class="sp-toolbar-group sp-toolbar-group--end">
                <?php if ($hasRows): ?>
                    <div class="sp-switch" role="group" aria-label="Show results as">
                        <button type="button" data-results-view="table" aria-pressed="true">Table</button>
                        <button type="button" data-results-view="grid" aria-pressed="false">Grid</button>
                    </div>
                <?php endif; ?>
                <?php if ($cartEnabled): ?>
                    <a class="sp-tool sp-c1" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php if ($hasRows): ?>
                        <button type="button" class="sp-btn sp-btn--go" id="addToCartBtn"><?= rs_icon('cart') ?>Add to cart · <span id="cartCountLabel"><?= (int)$cartCount ?></span></button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php require __DIR__ . '/../_parts/results_forms.php'; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel"><p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p></div>
        <?php else: ?>
            <div class="sp-table-card">
                <?php render_results_table($columns, $rows, $mediaLinks, [
                    'total' => $total,
                    'persist' => true,
                    'reset' => $resetSelection,
                    'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>',
                ]); ?>
            </div>
            <div class="results-pagination-row">
                <form class="rows-per-page" method="get" action="<?= e(asset_url('/modules/user/results.php')) ?>">
                    <label for="perPageSelect">Rows per page</label>
                    <select id="perPageSelect" name="per_page">
                        <?php foreach (rows_per_page_choices() as $opt): ?>
                            <option value="<?= (int)$opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= (int)$opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
                <?php render_pagination($page, $totalPages, fn($p) => '?page=' . $p); ?>
            </div>
        <?php endif; ?>

        <footer class="sp-foot">&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_view_toggle.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
