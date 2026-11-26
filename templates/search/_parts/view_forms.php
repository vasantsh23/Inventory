<?php
/**
 * templates/search/_parts/view_forms.php — status box, Markup % row and
 * hidden copy form for View Cart / View (Selected) (Luxe and Spectrum
 * layouts). $btnClass: the layout's button class for the Copy button.
 */
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
?>
        <?php if ($total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="copy-markup-row" id="markupCopyRow" hidden>
                <label class="copy-markup-field">
                    Markup %
                    <input type="number" step="any" id="markupCopyInput" placeholder="e.g. 5" style="width:90px;">
                </label>
                <button type="button" class="<?= e($btnClass ?? 'btn') ?>" id="markupCopyConfirmBtn">Copy</button>
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
