<?php $shapes = site_shapes($b->t('shapes'));
$href = $b->url('button_url') ?: asset_url('/inventory.php'); ?>
<section class="s-shapes"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <?php if ($shapes): ?>
            <ul class="s-shapes__grid">
                <?php foreach ($shapes as [$name, $src]): ?>
                    <li><a class="s-shape" href="<?= e($href) ?>"><img src="<?= e($src) ?>" alt="<?= e($name) ?>" loading="lazy" width="120" height="120"></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?= site_buttons($b->buttons(), 'btn-row--center') ?>
    </div>
</section>
