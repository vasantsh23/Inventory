<?php $items = $b->items('title'); if (!$items) return; ?>
<section class="s-features s-count-<?= count($items) ?>"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <ul class="s-features__list">
            <?php foreach ($items as $it): ?>
                <li class="s-feature">
                    <span class="s-feature__icon"><?= site_icon($it['icon'] ?: 'diamond') ?></span>
                    <h3 class="s-feature__title"><?= e($it['title']) ?></h3>
                    <?php if ($it['text'] !== ''): ?><p class="s-feature__text"><?= e($it['text']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
