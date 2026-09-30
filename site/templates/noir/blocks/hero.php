<?php
$isHome = $page === 'home';
$img = $b->imgFirst('image_dark', 'image'); ?>
<section class="no-hero <?= $isHome ? 'no-hero--home' : 'no-hero--page' ?><?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <?php if ($img !== ''): ?><div class="no-hero__media"><?= site_img($img, '', '', true) ?></div><?php endif; ?>
    <div class="wrap no-hero__inner">
        <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
        <h1 class="no-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
        <?php if ($b->has('text')): ?><div class="no-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
        <?= site_buttons($b->buttons(), 'btn-row--center') ?>
    </div>
</section>
