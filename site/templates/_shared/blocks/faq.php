<?php $items = $b->items('question'); if (!$items) return; ?>
<section class="s-faq"<?= site_labelled($b) ?>>
    <div class="wrap s-faq__grid">
        <?= site_heading($b) ?>
        <div class="s-faq__list">
            <?php foreach ($items as $it): ?>
                <details class="s-faq__item">
                    <summary><span><?= e($it['question']) ?></span><?= site_icon('plus', 'ico s-faq__icon') ?></summary>
                    <div class="s-faq__answer"><?= Site::paragraphs($it['answer']) ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
