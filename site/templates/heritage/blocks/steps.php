<?php $items = $b->items('title'); if (!$items) return; ?>
<section class="he-steps"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <ol class="he-timeline">
            <?php foreach ($items as $i => $it): ?>
                <li class="he-tl">
                    <span class="he-tl__icon"><?= site_icon($it['icon'] ?: 'diamond') ?></span>
                    <span class="he-tl__marker"><span class="he-tl__dot" aria-hidden="true"></span><span class="he-tl__label">Step <?= $i + 1 ?></span></span>
                    <div class="he-tl__body">
                        <h3 class="he-tl__title"><?= e($it['title']) ?></h3>
                        <?php if ($it['text'] !== ''): ?><p><?= e($it['text']) ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
