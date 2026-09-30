<?php $items = $b->items('value'); if (!$items) return; ?>
<section class="s-stats s-count-<?= count($items) ?>" aria-label="Key figures">
    <div class="wrap">
        <dl class="s-stats__list">
            <?php foreach ($items as $it): ?>
                <div class="s-stat">
                    <dt class="s-stat__label"><?= e($it['label']) ?></dt>
                    <dd class="s-stat__value"><?= e($it['value']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
