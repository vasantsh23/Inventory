<?php
/**
 * templates/search/_parts/results_forms.php — the status boxes and hidden
 * forms the Results page needs (used by the Luxe and Spectrum layouts).
 * Ids are the ones assets/js/results_toolbar.js and memo_actions.js use.
 * Included from a results.php view, so it sees that page's variables.
 */
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
?>
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
