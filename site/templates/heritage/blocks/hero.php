<?php
$isHome = $page === 'home';
if ($isHome):
    $img = $b->imgFirst('image_alt', 'image'); ?>
<section class="he-hero he-hero--home"<?= site_labelled($b) ?>>
    <div class="wrap he-hero__grid">
        <div class="he-hero__copy">
            <?php if ($b->has('eyebrow')): ?><p class="he-hero__eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
            <h1 class="he-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
            <?php if ($b->has('text')): ?><div class="he-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
            <?= site_buttons($b->buttons()) ?>
        </div>
        <?php if ($img !== ''): ?><figure class="he-hero__media"><?= site_img($img, '', '', true) ?></figure><?php endif; ?>
    </div>
</section>
<?php else:
    $img = $b->img('image'); ?>
<section class="he-hero he-hero--page<?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <div class="wrap he-hero__grid">
        <div class="he-hero__copy">
            <nav class="he-crumbs" aria-label="Breadcrumb"><a href="<?= e(Site::pageUrl('home')) ?>"><?= e(Site::global('nav', 'home') ?: 'Home') ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e($pageDef['label']) ?></span></nav>
            <h1 class="he-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
            <?php if ($b->has('text')): ?><div class="he-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
            <?= site_buttons($b->buttons()) ?>
        </div>
        <?php if ($img !== ''): ?><figure class="he-hero__media"><?= site_img($img, '', '', true) ?></figure><?php endif; ?>
    </div>
</section>
<?php endif;
