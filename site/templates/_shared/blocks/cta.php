<?php $img = $b->img('image'); ?>
<section class="s-cta<?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <div class="wrap s-cta__grid">
        <div class="s-cta__body">
            <h2 class="s-title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2>
            <?php if ($b->has('text')): ?><div class="s-lead"><?= $b->p('text') ?></div><?php endif; ?>
            <?= site_buttons($b->buttons()) ?>
        </div>
        <?php if ($img !== ''): ?><figure class="s-cta__media"><?= site_img($img) ?></figure><?php endif; ?>
    </div>
</section>
