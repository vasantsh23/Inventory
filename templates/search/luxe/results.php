<?php
/**
 * templates/search/luxe/results.php — Atelier Luxe layout. Included by
 * modules/user/results.php after it has prepared all the data. Markup
 * only; button / form ids as used by results_toolbar.js and
 * memo_actions.js. Styles: assets/css/search_luxe.css.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true;

$hideFooterBar = true;
$bodyClass = 'lx lx-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';
$hasRows = $columns !== [] && $total > 0;

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page lx-page">
        <header class="lx-hero lx-hero--results">
            <div class="lx-hero-top">
                <span class="lx-eyebrow">Search results</span>
                <span class="lx-hero-user">
                    <a href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>">Refine search</a> ·
                    <a href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>">New search</a>
                </span>
            </div>
            <h1 class="lx-hero-title"><?= number_format($total) ?> <?= $total === 1 ? 'diamond' : 'diamonds' ?></h1>
            <span class="lx-rule" aria-hidden="true"><span></span></span>
            <?php if ($appliedFilters !== []): ?>
                <p class="lx-hero-filters">
                    <?php foreach ($appliedFilters as $i => $af): ?><?= $i > 0 ? '<span class="lx-dot" aria-hidden="true">◆</span>' : '' ?><span class="filter-tag"><strong><?= e($af['label']) ?></strong> <?= e($af['value']) ?></span><?php endforeach; ?>
                </p>
            <?php else: ?>
                <p class="lx-hero-sub">The complete collection.</p>
            <?php endif; ?>
        </header>

        <div class="lx-toolbar">
            <?php if ($canMemo && $resultsError === ''): ?>
                <?php $pickerClass = 'lx-customer'; require __DIR__ . '/../_parts/customer_picker.php'; ?>
                <div class="lx-toolbar-group">
                    <button type="button" class="lx-btn lx-btn--line" id="memo1Btn">Memo-1</button>
                    <button type="button" class="lx-btn lx-btn--line" id="memo3Btn">Memo-3</button>
                    <a class="lx-btn lx-btn--line" href="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">+ Customer</a>
                </div>
            <?php endif; ?>
            <div class="lx-toolbar-group">
                <?php if ($hasRows): ?>
                    <button type="button" class="lx-btn lx-btn--line" id="viewSelectedBtn"><?= rs_icon('eye') ?>View selected</button>
                    <button type="button" class="lx-btn lx-btn--line" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="lx-btn lx-btn--line" id="excelSelectBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                <?php endif; ?>
                <?php if ($columns !== []): ?>
                    <a class="lx-btn lx-btn--line" href="?export=xlsx_all"><?= rs_icon('xls') ?>Complete stock</a>
                <?php endif; ?>
            </div>
            <?php if ($cartEnabled): ?>
                <div class="lx-toolbar-group lx-toolbar-group--end">
                    <a class="lx-btn lx-btn--line" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php if ($hasRows): ?>
                        <button type="button" class="lx-btn lx-btn--primary" id="addToCartBtn"><?= rs_icon('cart') ?>Add to cart (<span id="cartCountLabel"><?= (int)$cartCount ?></span>)</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php require __DIR__ . '/../_parts/results_forms.php'; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel"><p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p></div>
        <?php else: ?>
            <div class="lx-table-card">
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

        <footer class="lx-foot">&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
