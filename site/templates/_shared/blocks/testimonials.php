<?php $items = $b->items('quote'); if (!$items) return; ?>
<section class="s-quotes"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <div class="s-quotes__carousel" <?= count($items) > 1 ? 'data-carousel' : '' ?> aria-roledescription="carousel">
            <div class="s-quotes__track">
                <?php foreach ($items as $i => $it): ?>
                    <figure class="s-quote" data-slide aria-roledescription="slide" aria-label="<?= $i + 1 ?> of <?= count($items) ?>">
                        <blockquote class="s-quote__text"><p><?= e($it['quote']) ?></p></blockquote>
                        <?php if ($it['name'] !== ''): ?>
                            <figcaption class="s-quote__by"><span class="s-quote__name"><?= e($it['name']) ?></span><?php if ($it['role'] !== ''): ?><span class="s-quote__role"><?= e($it['role']) ?></span><?php endif; ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
            <?php if (count($items) > 1): ?>
                <div class="s-quotes__nav">
                    <button type="button" class="s-quotes__btn" data-prev aria-label="Previous review"><?= site_icon('arrow-left') ?></button>
                    <div class="s-quotes__dots" data-dots></div>
                    <button type="button" class="s-quotes__btn" data-next aria-label="Next review"><?= site_icon('arrow-right') ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
