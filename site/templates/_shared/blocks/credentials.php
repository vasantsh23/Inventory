<?php $items = $b->items('name'); if (!$items) return; ?>
<section class="s-creds"<?= $b->has('title') ? site_labelled($b) : ' aria-label="Memberships and certifications"' ?>>
    <div class="wrap">
        <?php if ($b->has('title')): ?><h2 class="s-creds__title" id="<?= e($b->id()) ?>"><?= $b->e('title') ?></h2><?php endif; ?>
        <ul class="s-creds__list s-count-<?= count($items) ?>">
            <?php foreach ($items as $it): ?>
                <li class="s-cred">
                    <?php if ($it['image'] !== ''): ?>
                        <img src="<?= e($it['image']) ?>" alt="<?= e($it['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="s-cred__name"><?= e($it['name']) ?></span>
                        <?php if ($it['text'] !== ''): ?><span class="s-cred__text"><?= e($it['text']) ?></span><?php endif; ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
