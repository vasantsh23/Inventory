<?php
/**
 * Noir section renderers. Signature: nr_<name>(page, block, options).
 */
declare(strict_types=1);

function nr_eyebrow(?string $text): string
{
    return trim((string) $text) !== '' ? '<p class="nr-eyebrow">' . st($text) . '</p>' : '';
}

function nr_buttons(array $r): string
{
    $html = '';
    if (!empty($r['link_label'])) {
        $html .= '<a class="nr-btn" href="' . e(site_href($r['link_url'] ?? '')) . '">' . st($r['link_label']) . '</a>';
    }
    if (!empty($r['link2_label'])) {
        $html .= '<a class="nr-btn nr-btn--text" href="' . e(site_href($r['link2_url'] ?? '')) . '">' . st($r['link2_label']) . '</a>';
    }
    return $html !== '' ? '<div class="nr-actions">' . $html . '</div>' : '';
}

function nr_tone(array $o): string
{
    return ($o['tone'] ?? '') === 'alt' ? ' nr-band--alt' : '';
}

/** Centred heading with the small gold rule, from a row. */
function nr_heading(array $i, bool $withBody = true): string
{
    if ($i === []) {
        return '';
    }
    return '<header class="nr-head">'
        . nr_eyebrow($i['subtitle'] ?? '')
        . (!empty($i['title']) ? '<h2 class="nr-h2">' . st($i['title']) . '</h2>' : '')
        . '<span class="nr-rule" aria-hidden="true"></span>'
        . ($withBody && !empty($i['body']) ? '<div class="nr-head__text">' . site_paras($i['body']) . '</div>' : '')
        . '</header>';
}

// ------------------------------------------------------------------ hero

function nr_hero(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    if (empty($o['slider'])) {
        $rows = array_slice($rows, 0, 1);
    }
    $full = ($o['size'] ?? '') === 'full';
    $multi = count($rows) > 1;
    ?>
    <section class="nr-hero<?= $full ? ' nr-hero--full' : '' ?>"<?= $multi ? ' data-slider data-interval="7000" aria-roledescription="carousel" aria-label="Highlights"' : '' ?>>
        <?php foreach ($rows as $n => $r): $photo = site_img($r['image'] ?? ''); ?>
            <div class="nr-hero__slide<?= $n === 0 ? ' is-current' : '' ?>"<?= $multi ? ' data-slide role="group" aria-roledescription="slide" aria-label="' . ($n + 1) . ' of ' . count($rows) . '"' : '' ?>>
                <?php if ($photo): ?>
                    <img class="nr-hero__bg" src="<?= e($photo) ?>" alt=""<?= $n === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
                <?php else: ?>
                    <div class="nr-hero__art nr-hero__art--<?= $n % 3 ?>" aria-hidden="true"></div>
                <?php endif; ?>
                <div class="nr-wrap nr-hero__inner">
                    <?= nr_eyebrow($r['subtitle'] ?? '') ?>
                    <?php if ($n === 0): ?>
                        <h1 class="nr-hero__title"><?= st($r['title'] ?? '') ?></h1>
                    <?php else: ?>
                        <p class="nr-hero__title" role="heading" aria-level="2"><?= st($r['title'] ?? '') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($r['body'])): ?><div class="nr-hero__text"><?= site_paras($r['body']) ?></div><?php endif; ?>
                    <?= nr_buttons($r) ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($multi): ?>
            <button class="nr-hero__arrow nr-hero__arrow--prev" type="button" data-slide-prev aria-label="Previous slide"><?= site_icon('chevron-left') ?></button>
            <button class="nr-hero__arrow nr-hero__arrow--next" type="button" data-slide-next aria-label="Next slide"><?= site_icon('chevron') ?></button>
            <div class="nr-hero__dots">
                <?php foreach ($rows as $n => $r): ?>
                    <button type="button" data-slide-dot class="<?= $n === 0 ? 'is-current' : '' ?>" aria-label="Show slide <?= $n + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

// --------------------------------------------------------------- statbar

function nr_statbar(string $page, string $block, array $o): void
{
    ?>
    <section class="nr-statbar" aria-label="Key facts">
        <dl class="nr-statbar__list">
            <?php foreach (site_list($page, $block) as $r): ?>
                <div class="nr-statbar__item"><dt><?= st($r['subtitle'] ?? '') ?></dt><dd><?= st($r['title'] ?? '') ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>
    <?php
}

// -------------------------------------------------------------- centered

function nr_centered(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap nr-centered">
            <?= nr_heading($r, false) ?>
            <div class="nr-centered__body"><?= site_paras($r['body'] ?? '') ?></div>
            <?= nr_buttons($r) ?>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------ icon cards

function nr_icon_cards(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $cols = count($rows) % 3 === 0 && count($rows) !== 3 ? 3 : min(4, max(2, count($rows)));
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap">
            <?= nr_heading(site_intro($page, $block)) ?>
            <ul class="nr-icards nr-cols-<?= $cols ?>">
                <?php foreach ($rows as $r): ?>
                    <li class="nr-icard">
                        <span class="nr-icard__icon"><?= site_icon($r['icon'] ?? 'diamond') ?></span>
                        <h3 class="nr-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// --------------------------------------------------------- feature cards

function nr_feature_cards(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap">
            <?= nr_heading(site_intro($page, $block)) ?>
            <ul class="nr-fcards">
                <?php foreach ($rows as $r): ?>
                    <li class="nr-fcard">
                        <span class="nr-fcard__icon"><?= site_icon($r['icon'] ?? 'diamond') ?></span>
                        <h3 class="nr-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// ---------------------------------------------------------------- framed

function nr_framed(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap nr-framed<?= !empty($o['reverse']) ? ' nr-framed--reverse' : '' ?>">
            <figure class="nr-framed__media<?= empty($r['image']) ? ' is-art' : '' ?>">
                <?= site_picture($r, $o['art'] ?? 'brilliant', 'nr-framed__img') ?>
            </figure>
            <div class="nr-framed__body">
                <?= nr_eyebrow($r['subtitle'] ?? '') ?>
                <h2 class="nr-h2"><?= st($r['title'] ?? '') ?></h2>
                <span class="nr-rule nr-rule--left" aria-hidden="true"></span>
                <?= site_paras($r['body'] ?? '') ?>
                <?= nr_buttons($r) ?>
            </div>
        </div>
    </section>
    <?php
}

// ----------------------------------------------------------------- steps

function nr_steps(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap">
            <?= nr_heading(site_intro($page, $block)) ?>
            <ol class="nr-steps nr-cols-<?= min(4, max(2, count($rows))) ?>">
                <?php foreach ($rows as $n => $r): ?>
                    <li class="nr-steps__item">
                        <span class="nr-steps__num"><?= !empty($r['subtitle']) ? st($r['subtitle']) : $n + 1 ?></span>
                        <h3 class="nr-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------- faq

function nr_faq(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $i = site_intro($page, $block);
    ?>
    <section class="nr-band<?= nr_tone($o) ?>">
        <div class="nr-wrap nr-faq">
            <?= nr_heading($i) ?>
            <div class="nr-faq__list">
                <?php foreach ($rows as $r): ?>
                    <details class="nr-faq__item">
                        <summary><span><?= st($r['title'] ?? '') ?></span><?= site_icon('plus', 'icon nr-faq__icon') ?></summary>
                        <div class="nr-faq__answer"><?= site_paras($r['body'] ?? '') ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($i['link_label'])): ?><div class="nr-faq__more"><?= nr_buttons(['link_label' => '', 'link2_label' => $i['link_label'], 'link2_url' => $i['link_url'] ?? '']) ?></div><?php endif; ?>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------- cta

function nr_cta(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="nr-band">
        <div class="nr-wrap">
            <div class="nr-cta">
                <div>
                    <h2 class="nr-h2"><?= st($r['title'] ?? '') ?></h2>
                    <?= site_paras($r['body'] ?? '') ?>
                </div>
                <?= nr_buttons($r) ?>
            </div>
        </div>
    </section>
    <?php
}

// ----------------------------------------------------------------- quote

function nr_quote(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="nr-band nr-band--alt">
        <figure class="nr-wrap nr-quote">
            <span class="nr-quote__mark" aria-hidden="true">&ldquo;</span>
            <blockquote><p><?= st($r['title'] ?? '') ?></p></blockquote>
            <?php if (!empty($r['subtitle'])): ?><figcaption><?= st($r['subtitle']) ?></figcaption><?php endif; ?>
        </figure>
    </section>
    <?php
}

// --------------------------------------------------------------- contact

function nr_contact(string $page, string $block, array $o): void
{
    $form    = site_block($page, $block);
    $intro   = site_block($page, 'intro');
    $offices = site_list($page, 'offices');
    $email   = primary_email(site_setup());
    $phones  = site_phones();
    $hours   = site_hours();
    ?>
    <section class="nr-band" id="contact-form">
        <div class="nr-wrap">
            <?= nr_heading($intro) ?>
            <ul class="nr-offices">
                <?php if ($offices): foreach ($offices as $of): ?>
                    <li class="nr-office">
                        <span class="nr-office__icon"><?= site_icon($of['icon'] ?: 'pin') ?></span>
                        <h3><?= st($of['title'] ?? '') ?></h3>
                        <?= site_paras($of['body'] ?? '') ?>
                    </li>
                <?php endforeach; elseif ($lines = site_address_lines()): ?>
                    <li class="nr-office">
                        <span class="nr-office__icon"><?= site_icon('pin') ?></span>
                        <h3><?= e(site_company()) ?></h3>
                        <address><?= implode('<br>', array_map('e', $lines)) ?></address>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="nr-contact">
                <div class="nr-panel">
                    <h2 class="nr-panel__title"><?= st($form['title'] ?? 'Send us a message') ?></h2>
                    <?php if (!empty($form['body'])): ?><div class="nr-panel__text"><?= site_paras($form['body']) ?></div><?php endif; ?>
                    <?= site_contact_form() ?>
                </div>
                <aside class="nr-panel nr-panel--side">
                    <h2 class="nr-panel__title">Direct contact</h2>
                    <ul class="nr-direct">
                        <?php if ($email !== ''): ?>
                            <li><span class="nr-direct__icon"><?= site_icon('mail') ?></span>
                                <div><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><small>Email us any time</small></div></li>
                        <?php endif; ?>
                        <?php foreach ($phones as $ph): ?>
                            <li><span class="nr-direct__icon"><?= site_icon('phone') ?></span>
                                <div><a href="<?= e(site_tel_href($ph)) ?>"><?= e($ph) ?></a><small>Call us</small></div></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($hours): ?>
                        <h3 class="nr-panel__sub">Opening hours</h3>
                        <dl class="nr-hours">
                            <?php foreach ($hours as $h): ?>
                                <div><dt><?= e($h['Day'] ?? '') ?></dt><dd><?= e(format_business_hours_row($h)) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>
    <?php
}
