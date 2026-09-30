<?php
$lines = site_contact_lines($setup);
$hours = $b->t('show_hours') === 'yes' ? site_hours() : [];
$showForm = $b->t('show_form') !== 'no';
$map = $b->url('map_url'); ?>
<section class="at-contact" id="contact"<?= site_labelled($b) ?>>
    <div class="wrap">
        <header class="at-contact__head">
            <h2 class="s-title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2>
            <?php if ($b->has('text')): ?><div class="s-lead"><?= $b->p('text') ?></div><?php endif; ?>
        </header>
        <?php if ($showForm): ?>
            <div class="at-contact__card" id="contact-form"><?php require dirname(__DIR__, 2) . '/_shared/form.php'; ?></div>
        <?php endif; ?>
        <?php if ($lines || $hours || $map !== ''): ?>
            <div class="at-contact__details">
                <?php foreach ($lines as [$icon, $label, $html]): ?>
                    <div class="at-contact__item"><span class="at-contact__label"><?= e($label) ?></span><span><?= $html ?></span></div>
                <?php endforeach; ?>
                <?php if ($hours): ?>
                    <div class="at-contact__item"><span class="at-contact__label">Hours</span>
                        <dl class="s-hours"><?php foreach ($hours as [$d, $t]): ?><div><dt><?= e($d) ?></dt><dd><?= e($t) ?></dd></div><?php endforeach; ?></dl>
                    </div>
                <?php endif; ?>
                <?php if ($map !== ''): ?><div class="at-contact__item"><span class="at-contact__label">Visit</span><a href="<?= e($map) ?>" target="_blank" rel="noopener noreferrer">Get directions</a></div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
