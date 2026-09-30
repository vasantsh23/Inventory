<?php
/** Enquiry form (used inside the contact block). */
$state = site_contact_state();
$err = $state['errors'];
$old = $state['old'];
$fid = fn (string $k) => 'cf-' . $k;
?>
<?php if ($state['sent']): ?>
    <div class="form-notice form-notice--ok" role="status" tabindex="-1" data-focus><?= Site::paragraphs(Site::global('form', 'success_message')) ?></div>
<?php endif; ?>
<?php if (isset($err['form'])): ?>
    <div class="form-notice form-notice--error" role="alert" tabindex="-1" data-focus><p><?= e($err['form']) ?></p></div>
<?php elseif ($err): ?>
    <div class="form-notice form-notice--error" role="alert" tabindex="-1" data-focus><p>Please check the highlighted fields.</p></div>
<?php endif; ?>
<form class="enquiry" method="post" action="#contact-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="site_contact" value="1">
    <div class="enquiry__hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php foreach (site_contact_fields() as $k => [$label, $required, $type, $max, $ac]):
        $has = isset($err[$k]); ?>
        <div class="field field--<?= e($k) ?><?= $has ? ' has-error' : '' ?>">
            <label for="<?= $fid($k) ?>"><?= e($label) ?><?= $required ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea id="<?= $fid($k) ?>" name="<?= e($k) ?>" rows="5" maxlength="<?= $max ?>"<?= $required ? ' required' : '' ?><?= $has ? ' aria-invalid="true" aria-describedby="' . $fid($k) . '-err"' : '' ?>><?= e($old[$k] ?? '') ?></textarea>
            <?php else: ?>
                <input id="<?= $fid($k) ?>" type="<?= e($type) ?>" name="<?= e($k) ?>" value="<?= e($old[$k] ?? '') ?>" maxlength="<?= $max ?>" autocomplete="<?= e($ac) ?>"<?= $required ? ' required' : '' ?><?= $has ? ' aria-invalid="true" aria-describedby="' . $fid($k) . '-err"' : '' ?>>
            <?php endif; ?>
            <?php if ($has): ?><p class="field__error" id="<?= $fid($k) ?>-err"><?= e($err[$k]) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="field field--consent<?= isset($err['consent']) ? ' has-error' : '' ?>">
        <label class="check"><input type="checkbox" name="consent" value="1"<?= isset($err['consent']) ? ' aria-invalid="true" aria-describedby="cf-consent-err"' : '' ?>> <span><?= e(Site::global('form', 'consent_text')) ?></span></label>
        <?php if (isset($err['consent'])): ?><p class="field__error" id="cf-consent-err"><?= e($err['consent']) ?></p><?php endif; ?>
    </div>
    <div class="enquiry__actions"><button class="btn btn--primary" type="submit"><?= e(Site::global('form', 'button_label') ?: 'Send message') ?></button></div>
</form>
