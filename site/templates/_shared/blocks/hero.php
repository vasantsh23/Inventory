<?php /** Generic hero: home = large, other pages = page banner. Templates usually override. */
$isHome = $page === 'home';
$img = $b->img('image'); ?>
<section class="s-hero <?= $isHome ? 's-hero--home' : 's-hero--page' ?><?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <?php if ($img !== ''): ?><div class="s-hero__media"><?= site_img($img, '', '', true) ?></div><?php endif; ?>
    <div class="wrap s-hero__inner">
        <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
        <h1 class="s-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
        <?php if ($b->has('text')): ?><div class="s-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
        <?= site_buttons($b->buttons()) ?>
    </div>
</section>
