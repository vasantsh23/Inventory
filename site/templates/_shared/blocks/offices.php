<?php $items = $b->items('name'); if (!$items) return; ?>
<section class="s-offices"<?= site_labelled($b) ?>>
    <div class="wrap">
        <?= site_heading($b) ?>
        <ul class="s-offices__list s-count-<?= count($items) ?>">
            <?php foreach ($items as $it): ?>
                <li class="s-office">
                    <h3 class="s-office__name"><?= e($it['name']) ?></h3>
                    <?php if (trim($it['address']) !== ''): ?><address class="s-office__addr"><?= nl2br(e(str_replace(', ', "\n", $it['address'])), false) ?></address><?php endif; ?>
                    <?php if (trim($it['phone']) !== ''): ?><p class="s-office__line"><?= site_icon('phone') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $it['phone'])) ?>"><?= e($it['phone']) ?></a></p><?php endif; ?>
                    <?php if (trim($it['email']) !== '' && filter_var($it['email'], FILTER_VALIDATE_EMAIL)): ?><p class="s-office__line"><?= site_icon('mail') ?><a href="mailto:<?= e($it['email']) ?>"><?= e($it['email']) ?></a></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
