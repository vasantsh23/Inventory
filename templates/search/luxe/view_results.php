<?php
/**
 * templates/search/luxe/view_results.php — Atelier Luxe layout for View
 * Cart and View (Selected). Included by modules/user/view_results.php.
 * Markup only; ids as used by results_toolbar.js / memo_actions.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true;

$hideFooterBar = true;
$bodyClass = 'lx lx-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';
$btnClass = 'lx-btn lx-btn--primary';

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page lx-page">
        <header class="lx-hero lx-hero--results">
            <div class="lx-hero-top">
                <span class="lx-eyebrow"><?= $showSelected ? 'Your selection' : 'Your cart' ?></span>
                <span class="lx-hero-user">
                    <a href="<?= e(asset_url('/modules/user/results.php')) ?>">Back to results</a>
                    <?php if ($cartEnabled && $showSelected): ?> · <a href="<?= e(asset_url('/modules/user/view_results.php')) ?>">View cart</a><?php endif; ?>
                </span>
            </div>
            <h1 class="lx-hero-title"><?= e($viewTitle) ?></h1>
            <span class="lx-rule" aria-hidden="true"><span></span></span>
            <p class="lx-hero-sub"><?= number_format($total) ?> <?= $total === 1 ? 'diamond' : 'diamonds' ?></p>
        </header>

        <?php if ($total > 0): ?>
            <div class="lx-toolbar">
                <div class="lx-toolbar-group">
                    <button type="button" class="lx-btn lx-btn--line" id="exportExcelBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                    <button type="button" class="lx-btn lx-btn--line" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="lx-btn lx-btn--line" id="markupCopyBtn"><?= rs_icon('copy') ?>Markup copy</button>
                </div>
                <?php if (!$showSelected): ?>
                    <div class="lx-toolbar-group lx-toolbar-group--end">
                        <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" class="rs-inline-form" data-confirm="Clear your entire cart? This cannot be undone.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="lx-btn lx-btn--danger"><?= rs_icon('trash') ?>Clear cart</button>
                        </form>
                    </div>
                <?php endif; ?>
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
            <div class="lx-table-card">
                <?php render_results_table($columns, $rows, $mediaLinks, [
                    'total' => $total,
                    'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>',
                ]); ?>
            </div>
        <?php endif; ?>

        <footer class="lx-foot">&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
