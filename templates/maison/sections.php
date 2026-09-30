<?php
/**
 * Maison section renderers. Signature: mz_<name>(page, block, options).
 * Content comes from site_content via site_block() / site_list();
 * everything is escaped with st() / site_paras().
 */
declare(strict_types=1);

function mz_eyebrow(?string $text, string $extra = ''): string
{
    return trim((string) $text) !== '' ? '<p class="mz-eyebrow' . ($extra !== '' ? ' ' . e($extra) : '') . '">' . st($text) . '</p>' : '';
}

/** Up to two buttons from a row's link fields. $onDark picks the light button style. */
function mz_buttons(array $r, bool $onDark = false): string
{
    $html = '';
    if (!empty($r['link_label'])) {
        $html .= '<a class="mz-btn' . ($onDark ? ' mz-btn--light' : '') . '" href="' . e(site_href($r['link_url'] ?? '')) . '">' . st($r['link_label']) . '</a>';
    }
    if (!empty($r['link2_label'])) {
        $html .= '<a class="mz-btn mz-btn--ghost' . ($onDark ? ' mz-btn--ghost-light' : '') . '" href="' . e(site_href($r['link2_url'] ?? '')) . '">' . st($r['link2_label']) . '</a>';
    }
    return $html !== '' ? '<div class="mz-actions">' . $html . '</div>' : '';
}

function mz_textlink(array $r): string
{
    return !empty($r['link_label'])
        ? '<a class="mz-textlink" href="' . e(site_href($r['link_url'] ?? '')) . '">' . st($r['link_label']) . '</a>'
        : '';
}

/** Heading for a list section from its "{block}_intro" row. */
function mz_section_head(string $page, string $block, bool $center = true): string
{
    $i = site_intro($page, $block);
    if ($i === []) {
        return '';
    }
    return '<header class="mz-head' . ($center ? ' mz-head--center' : '') . '">'
        . mz_eyebrow($i['subtitle'] ?? '')
        . (!empty($i['title']) ? '<h2 class="mz-h2">' . st($i['title']) . '</h2>' : '')
        . (!empty($i['body']) ? '<div class="mz-head__text">' . site_paras($i['body']) . '</div>' : '')
        . '</header>';
}

function mz_tone(array $o): string
{
    return match ($o['tone'] ?? '') {
        'alt'  => ' mz-band--alt',
        'dark' => ' mz-band--dark',
        default => '',
    };
}

// ------------------------------------------------------------------ hero

function mz_hero(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    $photo = site_img($r['image'] ?? '');
    $full = ($o['size'] ?? '') === 'full';
    ?>
    <section class="mz-hero<?= $full ? ' mz-hero--full' : '' ?><?= $photo ? ' has-photo' : '' ?>">
        <?php if ($photo): ?>
            <img class="mz-hero__photo" src="<?= e($photo) ?>" alt="" fetchpriority="high">
        <?php else: ?>
            <div class="mz-hero__art mz-hero__art--<?= e($o['art'] ?? 'brilliant') ?>" aria-hidden="true">
                <img src="<?= e(site_art($o['art'] ?? 'brilliant')) ?>" alt="" fetchpriority="high">
            </div>
        <?php endif; ?>
        <div class="mz-wrap mz-hero__inner">
            <?= mz_eyebrow($r['subtitle'] ?? '', 'mz-eyebrow--light') ?>
            <h1 class="mz-hero__title"><?= st($r['title'] ?? '') ?></h1>
            <?php if (!empty($r['body'])): ?><div class="mz-hero__text"><?= site_paras($r['body']) ?></div><?php endif; ?>
            <?= mz_buttons($r, true) ?>
        </div>
    </section>
    <?php
}

// ---------------------------------------------------------------- figures

function mz_figures(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="mz-figures" aria-label="Key facts">
        <dl class="mz-wrap mz-figures__list">
            <?php foreach ($rows as $r): ?>
                <div class="mz-figures__item">
                    <dt><?= st($r['subtitle'] ?? '') ?></dt>
                    <dd><?= st($r['title'] ?? '') ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>
    <?php
}

// ------------------------------------------------------------------- lede

function mz_lede(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap mz-lede">
            <div class="mz-lede__head">
                <?= mz_eyebrow($r['subtitle'] ?? '') ?>
                <h2 class="mz-h2"><?= st($r['title'] ?? '') ?></h2>
            </div>
            <div class="mz-lede__body">
                <?= site_paras($r['body'] ?? '') ?>
                <?= mz_textlink($r) ?>
            </div>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------- grid

function mz_grid(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $cols = min(4, max(2, count($rows)));
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap">
            <?= mz_section_head($page, $block) ?>
            <ul class="mz-grid mz-grid--<?= $cols ?>">
                <?php foreach ($rows as $r): ?>
                    <li class="mz-grid__item">
                        <?php if (!empty($r['icon'])): ?><span class="mz-grid__icon"><?= site_icon($r['icon']) ?></span><?php endif; ?>
                        <h3 class="mz-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ cards

function mz_cards(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap">
            <?= mz_section_head($page, $block) ?>
            <ul class="mz-cards">
                <?php foreach ($rows as $r): ?>
                    <li class="mz-card">
                        <?php if (!empty($r['image'])): ?>
                            <?= site_picture($r, 'brilliant', 'mz-card__img') ?>
                        <?php elseif (!empty($r['icon'])): ?>
                            <span class="mz-card__icon"><?= site_icon($r['icon']) ?></span>
                        <?php endif; ?>
                        <h3 class="mz-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                        <?= mz_textlink($r) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ split

function mz_split(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    $dark = ($o['tone'] ?? '') === 'dark';
    ?>
    <section class="mz-split<?= !empty($o['reverse']) ? ' mz-split--reverse' : '' ?><?= $dark ? ' mz-split--dark' : '' ?>">
        <figure class="mz-split__media<?= empty($r['image']) ? ' is-art' : '' ?>">
            <?= site_picture($r, $o['art'] ?? 'brilliant', 'mz-split__img') ?>
        </figure>
        <div class="mz-split__body">
            <?= mz_eyebrow($r['subtitle'] ?? '') ?>
            <h2 class="mz-h2"><?= st($r['title'] ?? '') ?></h2>
            <?= site_paras($r['body'] ?? '') ?>
            <?= $dark ? mz_buttons($r, true) : mz_textlink($r) ?>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ steps

function mz_steps(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap">
            <?= mz_section_head($page, $block) ?>
            <ol class="mz-steps">
                <?php foreach ($rows as $n => $r): ?>
                    <li class="mz-steps__item">
                        <span class="mz-steps__num" aria-hidden="true"><?= $n + 1 ?></span>
                        <h3 class="mz-h3"><?= st($r['title'] ?? '') ?></h3>
                        <?= site_paras($r['body'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php
}

// --------------------------------------------------------------- timeline

function mz_timeline(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap mz-timeline">
            <div class="mz-timeline__head"><?= mz_section_head($page, $block, false) ?></div>
            <ol class="mz-timeline__list">
                <?php foreach ($rows as $n => $r): ?>
                    <li class="mz-timeline__item">
                        <span class="mz-timeline__mark" aria-hidden="true">
                            <?php if (!empty($r['subtitle'])): ?><?= st($r['subtitle']) ?>
                            <?php elseif (!empty($r['icon'])): ?><?= site_icon($r['icon']) ?>
                            <?php else: ?><?= sprintf('%02d', $n + 1) ?><?php endif; ?>
                        </span>
                        <div>
                            <h3 class="mz-h3"><?= st($r['title'] ?? '') ?></h3>
                            <?= site_paras($r['body'] ?? '') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php
}

// ------------------------------------------------------------------ quote

function mz_quote(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="mz-band mz-band--alt">
        <div class="mz-wrap mz-quote">
            <figure class="mz-quote__media<?= empty($r['image']) ? ' is-art' : '' ?>">
                <?= site_picture($r, $o['art'] ?? 'rough', 'mz-quote__img') ?>
            </figure>
            <blockquote class="mz-quote__body">
                <p class="mz-quote__text"><?= st($r['title'] ?? '') ?></p>
                <?php if (!empty($r['subtitle'])): ?><footer class="mz-quote__by"><?= st($r['subtitle']) ?></footer><?php endif; ?>
            </blockquote>
        </div>
    </section>
    <?php
}

// -------------------------------------------------------------------- faq

function mz_faq(string $page, string $block, array $o): void
{
    $rows = site_list($page, $block);
    $i = site_intro($page, $block);
    ?>
    <section class="mz-band<?= mz_tone($o) ?>">
        <div class="mz-wrap mz-faq">
            <div class="mz-faq__head">
                <?= mz_section_head($page, $block, false) ?>
                <?= $i ? mz_textlink($i) : '' ?>
            </div>
            <div class="mz-faq__list">
                <?php foreach ($rows as $r): ?>
                    <details class="mz-faq__item">
                        <summary><span><?= st($r['title'] ?? '') ?></span><?= site_icon('plus', 'icon mz-faq__icon') ?></summary>
                        <div class="mz-faq__answer"><?= site_paras($r['body'] ?? '') ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

// -------------------------------------------------------------------- cta

function mz_cta(string $page, string $block, array $o): void
{
    $r = site_block($page, $block);
    ?>
    <section class="mz-cta">
        <div class="mz-wrap mz-cta__inner">
            <?= mz_eyebrow($r['subtitle'] ?? '', 'mz-eyebrow--light') ?>
            <h2 class="mz-h2"><?= st($r['title'] ?? '') ?></h2>
            <?= site_paras($r['body'] ?? '') ?>
            <?= mz_buttons($r, true) ?>
        </div>
    </section>
    <?php
}

// ---------------------------------------------------------------- contact

function mz_contact(string $page, string $block, array $o): void
{
    $form  = site_block($page, $block);
    $intro = site_block($page, 'intro');
    $email = primary_email(site_setup());
    $hours = site_hours();
    ?>
    <section class="mz-contact" id="contact-form">
        <div class="mz-wrap">
            <header class="mz-head mz-head--center">
                <h2 class="mz-h2"><?= st($form['title'] ?? 'Send us a message') ?></h2>
                <?php if (!empty($form['body'])): ?><div class="mz-head__text"><?= site_paras($form['body']) ?></div><?php endif; ?>
            </header>
            <div class="mz-contact__card"><?= site_contact_form() ?></div>

            <div class="mz-contact__details">
                <?php if ($intro): ?>
                    <div class="mz-contact__col">
                        <h3 class="mz-contact__label"><?= st($intro['title'] ?? '') ?></h3>
                        <?= site_paras($intro['body'] ?? '') ?>
                    </div>
                <?php endif; ?>
                <?php if ($lines = site_address_lines()): ?>
                    <div class="mz-contact__col">
                        <h3 class="mz-contact__label">Address</h3>
                        <address><?= implode('<br>', array_map('e', $lines)) ?></address>
                    </div>
                <?php endif; ?>
                <div class="mz-contact__col">
                    <h3 class="mz-contact__label">Phone and email</h3>
                    <?php foreach (site_phones() as $ph): ?>
                        <p><a href="<?= e(site_tel_href($ph)) ?>"><?= e($ph) ?></a></p>
                    <?php endforeach; ?>
                    <?php if ($email !== ''): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
                </div>
                <?php if ($hours): ?>
                    <div class="mz-contact__col">
                        <h3 class="mz-contact__label">Opening hours</h3>
                        <dl class="mz-hours">
                            <?php foreach ($hours as $h): ?>
                                <div><dt><?= e($h['Day'] ?? '') ?></dt><dd><?= e(format_business_hours_row($h)) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}
