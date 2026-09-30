<?php $img = $b->img('image'); ?>
<section class="s-intro<?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <div class="wrap s-intro__grid">
        <header class="s-intro__head">
            <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
            <h2 class="s-title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2>
        </header>
        <div class="s-intro__body prose"><?= $b->p('text') ?></div>
        <?php if ($img !== ''): ?><figure class="s-intro__media"><?= site_img($img) ?></figure><?php endif; ?>
    </div>
</section>
