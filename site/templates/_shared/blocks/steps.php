<?php $items = $b->items('title'); if (!$items) return; ?>
<section class="s-steps s-count-<?= count($items) ?>"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <ol class="s-steps__list">
            <?php foreach ($items as $i => $it): ?>
                <li class="s-step">
                    <span class="s-step__num" aria-hidden="true"><?= $i + 1 ?></span>
                    <div class="s-step__body">
                        <h3 class="s-step__title"><?= e($it['title']) ?></h3>
                        <?php if ($it['text'] !== ''): ?><p class="s-step__text"><?= e($it['text']) ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
