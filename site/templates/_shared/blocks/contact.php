<?php
$lines = site_contact_lines($setup);
$hours = $b->t('show_hours') === 'yes' ? site_hours() : [];
$showForm = $b->t('show_form') !== 'no';
$map = $b->url('map_url'); ?>
<section class="s-contact" id="contact"<?= site_labelled($b) ?>>
    <div class="wrap s-contact__grid<?= $showForm ? '' : ' no-form' ?>">
        <div class="s-contact__info">
            <h2 class="s-title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2>
            <?php if ($b->has('text')): ?><div class="s-lead"><?= $b->p('text') ?></div><?php endif; ?>
            <?php if ($lines): ?>
                <ul class="s-contact__lines">
                    <?php foreach ($lines as [$icon, $label, $html]): ?>
                        <li><span class="s-contact__icon"><?= site_icon($icon) ?></span><span><span class="s-contact__label"><?= e($label) ?></span><?= $html ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($hours): ?>
                <h3 class="s-contact__sub">Business hours</h3>
                <dl class="s-hours">
                    <?php foreach ($hours as [$day, $time]): ?><div><dt><?= e($day) ?></dt><dd><?= e($time) ?></dd></div><?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <?php if ($map !== ''): ?><p><a class="s-contact__map" href="<?= e($map) ?>" target="_blank" rel="noopener noreferrer"><?= site_icon('pin') ?> Get directions</a></p><?php endif; ?>
            <?= site_social($setup, 'social s-contact__social') ?>
        </div>
        <?php if ($showForm): ?>
            <div class="s-contact__form" id="contact-form"><?php require dirname(__DIR__) . '/form.php'; ?></div>
        <?php endif; ?>
    </div>
</section>
