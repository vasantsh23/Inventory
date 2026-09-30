<?php
/**
 * Contact form shared by every template. Each template styles
 * .site-form in its own stylesheet. Uses $submitLabel from
 * site_contact_form(). Submits to contact.php (site_contact_submit()).
 */
declare(strict_types=1);

$state  = site_contact_state();
$errors = $state['errors'];
$old    = $state['old'];
$val    = fn(string $f): string => e((string) ($old[$f] ?? ''));
$err    = function (string $f) use ($errors): string {
    return isset($errors[$f]) ? '<span class="site-form__error" id="err-' . e($f) . '">' . e($errors[$f]) . '</span>' : '';
};
$aria   = fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="err-' . e($f) . '"' : '';
?>
<?php if (!empty($state['sent'])): ?>
    <div class="site-form__notice site-form__notice--ok" role="status">
        <strong>Thank you, your message has been sent.</strong>
        <span>We reply to every enquiry within one business day.</span>
    </div>
<?php endif; ?>
<?php if (isset($errors['form'])): ?>
    <div class="site-form__notice site-form__notice--error" role="alert"><?= e($errors['form']) ?></div>
<?php elseif ($errors): ?>
    <div class="site-form__notice site-form__notice--error" role="alert">Check the highlighted fields and send the form again.</div>
<?php endif; ?>
<form class="site-form" method="post" action="<?= e(site_page_url('contact')) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="site-form__hp" aria-hidden="true">
        <label for="cf-website">Leave this field empty</label>
        <input id="cf-website" type="text" name="website" tabindex="-1" autocomplete="off">
    </div>
    <div class="site-form__grid">
        <div class="site-form__field site-form__field--full">
            <label for="cf-name">Name <span class="site-form__req" aria-hidden="true">*</span></label>
            <input id="cf-name" name="name" type="text" required maxlength="120" autocomplete="name" value="<?= $val('name') ?>"<?= $aria('name') ?>>
            <?= $err('name') ?>
        </div>
        <div class="site-form__field">
            <label for="cf-company">Company</label>
            <input id="cf-company" name="company" type="text" maxlength="150" autocomplete="organization" value="<?= $val('company') ?>"<?= $aria('company') ?>>
            <?= $err('company') ?>
        </div>
        <div class="site-form__field">
            <label for="cf-location">City / country</label>
            <input id="cf-location" name="location" type="text" maxlength="120" autocomplete="address-level2" value="<?= $val('location') ?>"<?= $aria('location') ?>>
            <?= $err('location') ?>
        </div>
        <div class="site-form__field">
            <label for="cf-email">Email <span class="site-form__req" aria-hidden="true">*</span></label>
            <input id="cf-email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= $val('email') ?>"<?= $aria('email') ?>>
            <?= $err('email') ?>
        </div>
        <div class="site-form__field">
            <label for="cf-phone">Phone</label>
            <input id="cf-phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= $val('phone') ?>"<?= $aria('phone') ?>>
            <?= $err('phone') ?>
        </div>
        <div class="site-form__field site-form__field--full">
            <label for="cf-message">What are you looking for? <span class="site-form__req" aria-hidden="true">*</span></label>
            <textarea id="cf-message" name="message" rows="5" required maxlength="5000"
                      placeholder="Shape, carat, colour, clarity, lab, quantity or budget"<?= $aria('message') ?>><?= $val('message') ?></textarea>
            <?= $err('message') ?>
        </div>
    </div>
    <p class="site-form__foot">
        <button class="site-form__submit" type="submit"><?= e($submitLabel) ?></button>
        <span class="site-form__legal">Fields marked * are required. We use your details only to reply to this enquiry.</span>
    </p>
</form>
