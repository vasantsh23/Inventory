<?php
/**
 * templates/search/prism/view_results.php — Prism layout (colourful,
 * modern). View Cart and View (Selected). Included by
 * modules/user/view_results.php after it has prepared all the data.
 * Markup only; ids as used by results_toolbar.js / memo_actions.js.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: print this template's colours + preview bar

$hideFooterBar = true;
$bodyClass = 'pz pz-results compact-footer no-footer-gap';
$wideContent = true;
$rsCompany = (string)((get_setup() ?? [])['company'] ?? APP_NAME);
$copyLabel = (strtok(trim($rsCompany), ' ') ?: 'Copy') . '-Copy';

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page rs-page pz-page">
        <header class="pz-hero pz-hero--results">
            <div class="pz-hero-text">
                <p class="pz-eyebrow"><?= $showSelected ? 'Your selection' : 'Your cart' ?></p>
                <div class="pz-hero-count">
                    <strong><?= number_format($total) ?></strong>
                    <span><?= $total === 1 ? 'diamond' : 'diamonds' ?> — <?= e($viewTitle) ?></span>
                </div>
            </div>
            <div class="pz-hero-side">
                <a class="pz-btn pz-btn--glass" href="<?= e(asset_url('/modules/user/results.php')) ?>"><?= rs_icon('back') ?>Back to results</a>
                <?php if ($cartEnabled && $showSelected): ?>
                    <a class="pz-btn pz-btn--glass" href="<?= e(asset_url('/modules/user/view_results.php')) ?>"><?= rs_icon('cart') ?>View cart</a>
                <?php endif; ?>
            </div>
            <span class="pz-hero-gem" aria-hidden="true"></span>
        </header>

        <?php if ($total > 0): ?>
            <div class="pz-toolbar">
                <div class="pz-toolbar-group">
                    <button type="button" class="pz-btn" id="exportExcelBtn"><?= rs_icon('xls') ?>Excel (selected)</button>
                    <button type="button" class="pz-btn" id="copyBtn"><?= rs_icon('copy') ?><?= e($copyLabel) ?></button>
                    <button type="button" class="pz-btn" id="markupCopyBtn"><?= rs_icon('copy') ?>Markup copy</button>
                </div>
                <?php if (!$showSelected): ?>
                    <div class="pz-toolbar-group pz-toolbar-group--end">
                        <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" class="rs-inline-form"
                              data-confirm="Clear your entire cart? This cannot be undone.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="pz-btn pz-btn--danger"><?= rs_icon('trash') ?>Clear cart</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="copy-markup-row" id="markupCopyRow" hidden>
                <label class="copy-markup-field">
                    Markup %
                    <input type="number" step="any" id="markupCopyInput" placeholder="e.g. 5" style="width:90px;">
                </label>
                <button type="button" class="pz-btn pz-btn--primary" id="markupCopyConfirmBtn">Copy</button>
            </div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
                <input type="hidden" name="mk" id="copyFormMk">
                <input type="hidden" name="mv" id="copyFormMv">
            </form>
        <?php endif; ?>

        <?php if (($_GET['cleared'] ?? '') === '1'): ?>
            <div class="alert alert-success" style="margin-bottom:18px;">Your cart has been cleared.</div>
        <?php endif; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php elseif ($total === 0): ?>
            <div class="panel">
                <?php if (!$showSelected): ?>
                    <p class="panel-desc">Your cart is empty. Go to Results, select some rows, and click "Add to cart".</p>
                <?php else: ?>
                    <p class="panel-desc">No diamonds selected. Go to Results, tick some rows, and click "View selected".</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="pz-table-card">
                <?php render_results_table($columns, $rows, $mediaLinks, [
                    'total' => $total,
                    'toolbarExtra' => '<button type="button" class="rs-link-btn" id="clearSelectionBtn">Clear selection</button>',
                ]); ?>
            </div>
        <?php endif; ?>

        <footer class="pz-foot">
            <span>&copy; <?= date('Y') ?> <?= e($rsCompany) ?>. All rights reserved. <?= e(APP_VERSION) ?></span>
        </footer>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_toolbar.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
