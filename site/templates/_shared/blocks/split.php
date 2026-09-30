<?php $img = $b->img('image');
$side = $b->t('image_side') === 'left' ? 'left' : 'right'; ?>
<section class="s-split s-split--img-<?= $side ?><?= $img === '' ? ' no-image' : '' ?>"<?= site_labelled($b) ?>>
    <?php if ($img !== ''): ?><figure class="s-split__media"><?= site_img($img, $b->t('title')) ?></figure><?php endif; ?>
    <div class="s-split__body">
        <div class="s-split__inner">
            <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
            <h2 class="s-title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2>
            <div class="prose"><?= $b->p('text') ?></div>
            <?= site_buttons($b->buttons()) ?>
        </div>
    </div>
</section>
