<?php
/**
 * templates/search/spectrum/view_results.php — Spectrum layout for View
 * Cart and View (Selected). Included by modules/user/view_results.php.
 * Markup only; ids as used by results_toolbar.js / memo_actions.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true;

$hideFooterBar = true;
$bodyClass = 'sp sp-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';
$btnClass = 'sp-btn sp-btn--go';

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page sp-page sp-page--results">
        <header class="sp-rhead">
            <div class="sp-rhead-main">
                <p class="sp-kicker"><?= $showSelected ? 'Your selection' : 'Your cart' ?></p>
                <h1 class="sp-title"><span class="sp-title-pop"><?= number_format($total) ?></span> <?= $total === 1 ? 'diamond' : 'diamonds' ?></h1>
                <p class="sp-sub"><?= e($viewTitle) ?></p>
            </div>
            <div class="sp-rhead-side">
                <a class="sp-btn sp-btn--outline" href="<?= e(asset_url('/modules/user/results.php')) ?>"><?= rs_icon('back') ?>Back to results</a>
                <?php if ($cartEnabled && $showSelected): ?>
                    <a class="sp-btn sp-btn--ghost" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($total > 0): ?>
            <div class="sp-toolbar">
                <div class="sp-toolbar-group">
                    <button type="button" class="sp-tool sp-c2" id="exportExcelBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                    <button type="button" class="sp-tool sp-c3" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="sp-tool sp-c4" id="markupCopyBtn"><?= rs_icon('copy') ?>Markup copy</button>
                </div>
                <div class="sp-toolbar-group sp-toolbar-group--end">
                    <div class="sp-switch" role="group" aria-label="Show results as">
                        <button type="button" data-results-view="table" aria-pressed="true">Table</button>
                        <button type="button" data-results-view="grid" aria-pressed="false">Grid</button>
                    </div>
                    <?php if (!$showSelected): ?>
                        <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" class="rs-inline-form" data-confirm="Clear your entire cart? This cannot be undone.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="sp-tool sp-c1"><?= rs_icon('trash') ?>Clear cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/../_parts/view_forms.php'; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel"><p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p></div>
        <?php elseif ($total === 0): ?>
            <div class="panel"><p class="panel-desc"><?= !$showSelected ? 'Your cart is empty. Go to Results, select some rows, and click "Add to cart".' : 'No diamonds selected. Go to Results, tick some rows, and click "View selected".' ?></p></div>
        <?php else: ?>
            <div class="sp-table-card">
                <?php render_results_table($columns, $rows, $mediaLinks, [
                    'total' => $total,
                    'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>',
                ]); ?>
            </div>
        <?php endif; ?>

        <footer class="sp-foot">&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_view_toggle.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
