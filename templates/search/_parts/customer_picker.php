<?php
/**
 * templates/search/_parts/customer_picker.php — customer search + select
 * for Memo users on Results (Luxe and Spectrum layouts). $pickerClass:
 * the layout's wrapper class.
 */
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
?>
<div class="<?= e($pickerClass ?? '') ?>">
    <div class="<?= e(($pickerClass ?? '') . '-search') ?>">
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
