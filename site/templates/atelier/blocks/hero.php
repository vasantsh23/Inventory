<?php
$isHome = $page === 'home';
$img = $b->img('image');
if ($isHome): ?>
<section class="at-hero at-hero--home<?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <?php if ($img !== ''): ?><div class="at-hero__media"><?= site_img($img, '', '', true) ?></div><?php endif; ?>
    <div class="wrap at-hero__inner">
        <div class="at-hero__copy">
            <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
            <h1 class="at-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
            <?php if ($b->has('text')): ?><div class="at-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
            <?= site_buttons($b->buttons()) ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="at-hero at-hero--page"<?= site_labelled($b) ?>>
    <div class="wrap at-hero__head">
        <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
        <h1 class="at-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
        <?php if ($b->has('text')): ?><div class="at-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
        <?= site_buttons($b->buttons(), 'btn-row--center') ?>
    </div>
    <?php if ($img !== ''): ?><div class="at-hero__band"><?= site_img($img, '', '', true) ?></div><?php endif; ?>
</section>
<?php endif;
