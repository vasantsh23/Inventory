<?php
/**
 * templates/search/prism/results.php — Prism layout (colourful, modern).
 * Included by modules/user/results.php after it has prepared all the
 * data (see includes/search_templates.php). Markup only: no queries or
 * actions here, so every template keeps the same features. The button
 * and form ids are the ones assets/js/results_toolbar.js and
 * memo_actions.js work with.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

$hideFooterBar = true; // the copyright line is printed in the page's own footer
$bodyClass = 'pz pz-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';  // e.g. "Veera-Copy"
$hasRows = $columns !== [] && $total > 0;

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page pz-page">
        <header class="pz-hero pz-hero--results">
            <div class="pz-hero-text">
                <p class="pz-eyebrow">Results</p>
                <div class="pz-hero-count">
                    <strong><?= number_format($total) ?></strong>
                    <span><?= $total === 1 ? 'diamond matches your search' : 'diamonds match your search' ?></span>
                </div>
                <?php if ($appliedFilters !== []): ?>
                    <div class="pz-filters" aria-label="Your filters">
                        <?php foreach ($appliedFilters as $af): ?>
                            <span class="filter-tag"><strong><?= e($af['label']) ?>:</strong> <?= e($af['value']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="pz-hero-side">
                <a class="pz-btn pz-btn--glass" href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>"><?= rs_icon('filter') ?>Modify filter</a>
                <a class="pz-btn pz-btn--glass" href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>">New search</a>
            </div>
            <span class="pz-hero-gem" aria-hidden="true"></span>
        </header>

        <div class="pz-toolbar">
            <?php if ($canMemo && $resultsError === ''): ?>
                <div class="pz-customer">
                    <div class="pz-customer-search">
                        <input type="search" id="memoCustomerSearch" placeholder="Search customer" aria-label="Search customer" autocomplete="off">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 0 1 5.9 12.1l4.7 4.7-1.4 1.4-4.7-4.7A7.5 7.5 0 1 1 10.5 3zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11z"/></svg>
                    </div>
                    <select id="memoCustomerSelect" aria-label="Select customer" data-add-url="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e((string)$c['custid']) ?>" <?= $newCustomerId === (string)$c['custid'] ? 'selected' : '' ?>><?= e($c['custnm']) ?></option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Add New Customer…</option>
                    </select>
                </div>
                <div class="pz-toolbar-group">
                    <button type="button" class="pz-btn" id="memo1Btn"><?= rs_icon('eye') ?>Memo-1</button>
                    <button type="button" class="pz-btn" id="memo3Btn"><?= rs_icon('eye') ?>Memo-3</button>
                    <a class="pz-btn" href="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">+ Customer</a>
                </div>
            <?php endif; ?>

            <div class="pz-toolbar-group">
                <?php if ($hasRows): ?>
                    <button type="button" class="pz-btn" id="viewSelectedBtn"><?= rs_icon('eye') ?>View selected</button>
                    <button type="button" class="pz-btn" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="pz-btn" id="excelSelectBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                <?php endif; ?>
                <?php if ($columns !== []): ?>
                    <a class="pz-btn" href="?export=xlsx_all"><?= rs_icon('xls') ?>Complete stock</a>
                <?php endif; ?>
            </div>

            <?php if ($cartEnabled): ?>
                <div class="pz-toolbar-group pz-toolbar-group--end">
                    <a class="pz-btn" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php if ($hasRows): ?>
                        <button type="button" class="pz-btn pz-btn--primary" id="addToCartBtn"><?= rs_icon('cart') ?>Add to cart <span class="pz-btn-badge" id="cartCountLabel"><?= (int)$cartCount ?></span></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="memo-error" id="memoError" hidden></div>
        <?php if ($hasRows): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="cart-status" id="cartStatus" hidden></div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
            </form>
            <?php if ($cartEnabled): ?>
                <form method="post" action="<?= e(asset_url('/modules/user/cart_add.php')) ?>" id="cartForm" style="display:none;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="ids" id="cartFormIds">
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" id="viewSelectedForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="view_selected">
                <input type="hidden" name="stocknos" id="viewSelectedStocknos">
            </form>
        <?php endif; ?>

        <?php if ($canMemo && $resultsError === ''): ?>
            <form method="post" action="<?= e(asset_url('/modules/user/memo_print.php')) ?>" id="memoForm" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="customer_id" id="memoFormCustomerId">
                <input type="hidden" name="copyopt" id="memoFormCopyopt">
                <input type="hidden" name="ids" id="memoFormIds">
            </form>
        <?php endif; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php else: ?>
            <div class="pz-table-card">
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

        <footer class="pz-foot">
            <span>&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></span>
        </footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
