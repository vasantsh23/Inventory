<?php
/**
 * HERO banner of this design (first section of a page).
 * Optional: any file you add to blocks/ replaces that section's shared
 * markup in site/templates/_shared/blocks/ (cards.php, faq.php, …).
 * Delete this file to use the shared hero.
 *
 * $b is the section: $b->e('title'), $b->has('text'), $b->p('text'),
 * $b->img('image'), $b->buttons(). $page is 'home', 'about', …
 */
if (!function_exists('site_render')) { http_response_code(404); exit; } // only via site/bootstrap.php
$isHome = $page === 'home';
$img = $b->img('image'); ?>
<section class="md-hero <?= $isHome ? 'md-hero--home' : 'md-hero--page' ?><?= $img !== '' ? ' has-image' : '' ?>"<?= site_labelled($b) ?>>
    <?php if ($img !== ''): ?><div class="md-hero__media"><?= site_img($img, '', '', true) ?></div><?php endif; ?>
    <div class="wrap md-hero__inner">
        <?php if ($b->has('eyebrow')): ?><p class="eyebrow"><?= $b->e('eyebrow') ?></p><?php endif; ?>
        <h1 class="md-hero__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h1>
        <?php if ($b->has('text')): ?><div class="md-hero__text"><?= $b->p('text') ?></div><?php endif; ?>
        <?= site_buttons($b->buttons()) ?>
    </div>
</section>
