<?php $items = $b->items('title'); if (!$items) return; ?>
<section class="s-cards s-count-<?= count($items) ?>"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <ul class="s-cards__list">
            <?php foreach ($items as $it): ?>
                <li class="s-card<?= $it['image'] !== '' ? ' has-image' : '' ?>">
                    <?php if ($it['image'] !== ''): ?>
                        <figure class="s-card__media"><?= site_img($it['image']) ?></figure>
                    <?php else: ?>
                        <span class="s-card__icon"><?= site_icon($it['icon'] ?: 'diamond') ?></span>
                    <?php endif; ?>
                    <h3 class="s-card__title"><?= e($it['title']) ?></h3>
                    <?php if ($it['text'] !== ''): ?><p class="s-card__text"><?= e($it['text']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
