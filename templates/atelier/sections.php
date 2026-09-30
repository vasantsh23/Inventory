<?php
/**
 * Atelier section renderers. Signature: at_<name>(page, block, options).
 */
declare(strict_types=1);

function at_eyebrow(?string $text, bool $marker = true): string
{
    return trim((string) $text) !== ''
        ? '<p class="at-eyebrow' . ($marker ? ' at-eyebrow--marker' : '') . '">' . st($text) . '</p>'
        : '';
}

function at_buttons(array $r, bool $onDark = false): string
{
    $html = '';
    if (!empty($r['link_label'])) {
        $html .= '<a class="at-btn' . ($onDark ? ' at-btn--light' : '') . '" href="' . e(site_href($r['link_url'] ?? '')) . '">' . st($r['link_label']) . '</a>';
    }
    if (!empty($r['link2_label'])) {
        $html .= '<a class="at-btn at-btn--outline' . ($onDark ? ' at-btn--outline-light' : '') . '" href="' . e(site_href($r['link2_url'] ?? '')) . '">' . st($r['link2_label']) . '</a>';
    }
    return $html !== '' ? '<div class="at-actions">' . $html . '</div>' : '';
}

function at_tone(array $o): string
{
    return ($o['tone'] ?? '') === 'alt' ? ' at-band--alt' : '';
}

function at_head(array $i, bool $center = true): string
{
    if ($i === []) {
        return '';
    }
    return '<header class="at-head' . ($center ? ' at-head--center' : '') . '">'
        . at_eyebrow($i['subtitle'] ?? '')
        . (!empty($i['title']) ? '<h2 class="at-h2">' . st($i['title']) . '</h2>' : '')
        . (!empty($i['body']) ? '<div class="at-head__text">' . site_paras($i['body']) . '</div>' : '')
        . '</header>';
}

// ------------------------------------------------------------------ hero

function at_hero(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    $full = ($o['size'] ?? '') === 'full';
    ?>
    <section class="at-hero<?= $full ? ' at-hero--full' : '' ?>">
        <div class="at-wrap at-hero__grid">
            <div class="at-hero__text">
                <?= at_eyebrow($r['subtitle'] ?? '', false) ?>
                <h1 class="at-hero__title"><?= st($r['title'] ?? '') ?></h1>
                <?php if (!empty($r['body'])): ?><div class="at-hero__lead"><?= site_paras($r['body']) ?></div><?php endif; ?>
                <?= at_buttons($r) ?>
            </div>
            <figure class="at-hero__media<?= empty($r['image']) ? ' is-art at-hero__media--' . e($o['art'] ?? 'profile') : '' ?>">
                <?= site_picture($r, $o['art'] ?? 'profile', 'at-hero__img') ?>
            </figure>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ band

function at_band(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $isStats = $rows !== [] && empty($rows[0]['body']) && !empty($rows[0]['subtitle']);
    ?>
    <section class="at-darkband">
        <div class="at-wrap">
            <?= at_head(site_intro($page, $block)) ?>
            <ul class="at-darkband__list at-cols-<?= min(4, max(2, count($rows))) ?>">
                <?php foreach ($rows as $r): ?>
                    <li class="at-darkband__item">
                        <?php if (!empty($r['icon'])): ?><span class="at-darkband__icon"><?= site_icon($r['icon']) ?></span><?php endif; ?>
                        <h3 class="at-darkband__title<?= $isStats ? ' is-figure' : '' ?>"><?= st($r['title'] ?? '') ?></h3>
                        <?php if (!empty($r['subtitle'])): ?><p class="at-darkband__sub"><?= st($r['subtitle']) ?></p><?php endif; ?>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// -------------------------------------------------------------- timeline

function at_timeline(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap">
            <?= at_head(site_intro($page, $block)) ?>
            <ol class="at-timeline">
                <?php foreach ($rows as $n => $r): ?>
                    <li class="at-timeline__item">
                        <span class="at-timeline__icon"><?= site_icon($r['icon'] ?: 'diamond') ?></span>
                        <span class="at-timeline__step"><span class="at-timeline__dot" aria-hidden="true"></span><?= !empty($r['subtitle']) ? st($r['subtitle']) : 'Step ' . ($n + 1) ?></span>
                        <div class="at-timeline__body">
                            <h3 class="at-h3"><?= st($r['title'] ?? '') ?></h3>
                            <?= site_paras($r['body'] ?? '') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php
}

// ----------------------------------------------------------------- story

function at_story(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap at-story<?= !empty($o['reverse']) ? ' at-story--reverse' : '' ?>">
            <figure class="at-story__media<?= empty($r['image']) ? ' is-art' : '' ?>">
                <?= site_picture($r, $o['art'] ?? 'brilliant', 'at-story__img') ?>
            </figure>
            <div class="at-story__body">
                <?= at_eyebrow($r['subtitle'] ?? '') ?>
                <h2 class="at-h2"><?= st($r['title'] ?? '') ?></h2>
                <?= site_paras($r['body'] ?? '') ?>
                <?= at_buttons($r) ?>
            </div>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ rows

function at_rows(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap at-wrap--narrow">
            <?= at_head(site_intro($page, $block)) ?>
            <ul class="at-rows">
                <?php foreach ($rows as $n => $r): ?>
                    <li class="at-rows__item">
                        <span class="at-rows__icon"><?= site_icon($r['icon'] ?: 'diamond') ?></span>
                        <div>
                            <h3 class="at-rows__title"><?= st($r['title'] ?? '') ?></h3>
                            <?= site_paras($r['body'] ?? '') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// ----------------------------------------------------------------- tiles

function at_tiles(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap">
            <?= at_head(site_intro($page, $block)) ?>
            <ul class="at-tiles">
                <?php foreach ($rows as $r): ?>
                    <li class="at-tile">
                        <?php if (!empty($r['image'])): ?>
                            <?= site_picture($r, 'brilliant', 'at-tile__img') ?>
                        <?php else: ?>
                            <span class="at-tile__icon"><?= site_icon($r['icon'] ?: 'diamond') ?></span>
                        <?php endif; ?>
                        <h3 class="at-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ lede

function at_lede(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap at-lede">
            <?= at_head($r) ?>
            <?= at_buttons($r) ?>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------- faq

function at_faq(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $i = site_intro($page, $block);
    ?>
    <section class="at-band<?= at_tone($o) ?>">
        <div class="at-wrap at-wrap--narrow">
            <?= at_head($i) ?>
            <div class="at-faq">
                <?php foreach ($rows as $n => $r): ?>
                    <details class="at-faq__item"<?= $n === 0 ? ' open' : '' ?>>
                        <summary><span><?= st($r['title'] ?? '') ?></span><?= site_icon('plus', 'icon at-faq__icon') ?></summary>
                        <div class="at-faq__answer"><?= site_paras($r['body'] ?? '') ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($i['link_label'])): ?>
                <p class="at-faq__more"><a class="at-link" href="<?= e(site_href($i['link_url'] ?? '')) ?>"><?= st($i['link_label']) ?><?= site_icon('chevron') ?></a></p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------- cta

function at_cta(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="at-cta">
        <div class="at-wrap at-cta__grid">
            <div class="at-cta__text">
                <?= at_eyebrow($r['subtitle'] ?? '') ?>
                <h2 class="at-h2"><?= st($r['title'] ?? '') ?></h2>
                <?= site_paras($r['body'] ?? '') ?>
                <?= at_buttons($r) ?>
            </div>
            <figure class="at-cta__media<?= empty($r['image']) ? ' is-art at-cta__media--' . e($o['art'] ?? 'brilliant') : '' ?>">
                <?= site_picture($r, $o['art'] ?? 'brilliant', 'at-cta__img') ?>
            </figure>
        </div>
    </section>
    <?php
}

// ----------------------------------------------------------------- quote

function at_quote(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="at-band at-band--alt at-quote">
        <figure class="at-wrap at-wrap--narrow">
            <blockquote><p><?= st($r['title'] ?? '') ?></p></blockquote>
            <?php if (!empty($r['subtitle'])): ?><figcaption><?= st($r['subtitle']) ?></figcaption><?php endif; ?>
        </figure>
    </section>
    <?php
}

// --------------------------------------------------------------- contact

function at_contact(string $page, string $block, array $o): void
{
    $form   = site_block($page, $block);
    $intro  = site_block($page, 'intro');
    $email  = primary_email(site_setup());
    $lines  = site_address_lines();
    $hours  = site_hours();
    ?>
    <section class="at-band at-band--alt">
        <div class="at-wrap">
            <div class="at-card">
                <div class="at-card__info">
                    <?= at_eyebrow($intro['subtitle'] ?? '') ?>
                    <h2 class="at-h2"><?= st($intro['title'] ?? 'Get in touch') ?></h2>
                    <?= site_paras($intro['body'] ?? '') ?>
                    <?php if ($lines): ?>
                        <div class="at-card__row">
                            <span class="at-card__icon"><?= site_icon('pin') ?></span>
                            <div><small>Address</small><address><?= implode('<br>', array_map('e', $lines)) ?></address></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($hours): ?>
                        <div class="at-card__row">
                            <span class="at-card__icon"><?= site_icon('clock') ?></span>
                            <div><small>Opening hours</small>
                                <dl class="at-hours"><?php foreach ($hours as $h): ?><div><dt><?= e($h['Day'] ?? '') ?></dt><dd><?= e(format_business_hours_row($h)) ?></dd></div><?php endforeach; ?></dl>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <ul class="at-card__links">
                    <?php foreach (site_phones() as $ph): ?>
                        <li><a href="<?= e(site_tel_href($ph)) ?>">
                            <span class="at-card__icon"><?= site_icon('phone') ?></span>
                            <span><small>Phone</small><?= e($ph) ?></span><?= site_icon('chevron', 'icon at-card__go') ?></a></li>
                    <?php endforeach; ?>
                    <?php if ($email !== ''): ?>
                        <li><a href="mailto:<?= e($email) ?>">
                            <span class="at-card__icon"><?= site_icon('mail') ?></span>
                            <span><small>Email</small><?= e($email) ?></span><?= site_icon('chevron', 'icon at-card__go') ?></a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(site_inventory_url()) ?>">
                        <span class="at-card__icon"><?= site_icon('diamond') ?></span>
                        <span><small>Trade customers</small>Search the inventory</span><?= site_icon('chevron', 'icon at-card__go') ?></a></li>
                </ul>
            </div>

            <div class="at-formcard" id="contact-form">
                <?= at_head($form, false) ?>
                <?= site_contact_form() ?>
            </div>
        </div>
    </section>
    <?php
}
