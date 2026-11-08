<?php
/**
 * templates/search/solitaire/results.php — Solitaire Station layout.
 * Included by modules/user/results.php after it has prepared all the
 * data (see includes/search_templates.php). Markup only: no queries
 * or actions here, so every template keeps the same features.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

$hideFooterBar = true; // the copyright line is printed in the page's own blue footer band
$bodyClass = 'compact-footer no-footer-gap rs-v2'; // tight footer; rs-v2 = bordered blue-header table design
$rsCompany = (string)((get_setup() ?? [])['company'] ?? '');
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';  // e.g. "Veera-Copy"

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page">
        <div class="rs-toolbar">
            <?php if ($canMemo && $resultsError === ''): ?>
                <div class="rs-customer">
                    <div class="rs-customer-search">
                        <input type="search" id="memoCustomerSearch" placeholder="Search Customer" aria-label="Search customer" autocomplete="off">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 0 1 5.9 12.1l4.7 4.7-1.4 1.4-4.7-4.7A7.5 7.5 0 1 1 10.5 3zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11z"/></svg>
                    </div>
                    <select id="memoCustomerSelect" aria-label="Select customer" data-add-url="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e((string)$c['custid']) ?>" <?= $newCustomerId === (string)$c['custid'] ? 'selected' : '' ?>><?= e($c['custnm']) ?></option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Add New Customer…</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="rs-buttons">
                <div class="rs-btn-row">
                    <?php if ($columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn" id="excelSelectBtn"><?= rs_icon('xls') ?>Excel-Select</button>
                    <?php endif; ?>
                    <a class="rs-btn" href="<?= e(asset_url('/modules/user/diamond_search.php')) ?>"><?= rs_icon('filter') ?>Blank Filter</a>
                    <a class="rs-btn" href="<?= e(asset_url('/modules/user/diamond_search.php') . '?restore=1') ?>"><?= rs_icon('filter') ?>Modify Filter</a>
                    <?php if ($canMemo): ?>
                        <a class="rs-btn" href="<?= e(asset_url('/modules/user/customer_form.php?return=results')) ?>"><?= rs_icon('filter') ?>Add Customer</a>
                    <?php endif; ?>
                    <?php if ($columns !== []): ?>
                        <a class="rs-btn" href="?export=xlsx_all"><?= rs_icon('xls') ?>Complete Stock</a>
                    <?php endif; ?>
                </div>
                <div class="rs-btn-row">
                    <?php if ($cartEnabled && $columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn rs-btn--big" id="addToCartBtn"><?= rs_icon('cart') ?><span>Add to cart-<span id="cartCountLabel"><?= (int)$cartCount ?></span></span></button>
                    <?php endif; ?>
                    <?php if ($cartEnabled): ?>
                        <a class="rs-btn" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                    <?php endif; ?>
                    <?php if ($columns !== [] && $total > 0): ?>
                        <button type="button" class="rs-btn" id="viewSelectedBtn"><?= rs_icon('eye') ?>View (Selected)</button>
                        <button type="button" class="rs-btn" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <?php endif; ?>
                    <?php if ($canMemo && $resultsError === ''): ?>
                        <button type="button" class="rs-btn" id="memo1Btn"><?= rs_icon('eye') ?>Memo-1</button>
                        <button type="button" class="rs-btn" id="memo3Btn"><?= rs_icon('eye') ?>Memo-3</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="memo-error" id="memoError" hidden></div>
        <?php if ($columns !== [] && $total > 0): ?>
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
            <?php
            $filterTags = '';
            if ($appliedFilters !== []) {
                $filterTags = '<div class="results-applied-filters">';
                foreach ($appliedFilters as $af) {
                    $filterTags .= '<span class="filter-tag"><strong>' . e($af['label']) . ':</strong> ' . e($af['value']) . '</span>';
                }
                $filterTags .= '</div>';
            }
            render_results_table($columns, $rows, $mediaLinks, [
                'total' => $total,
                'persist' => true,
                'reset' => $resetSelection,
                'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>' . $filterTags,
            ]);
            ?>

            <div class="results-pagination-row">
                <form class="rows-per-page" method="get" action="<?= e(asset_url('/modules/user/results.php')) ?>">
                    <label for="perPageSelect">Rows per page:</label>
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
    </section>
    <?php render_rs_footer_band(); ?>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
