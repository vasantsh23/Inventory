<?php
/** view_helpers.php — small rendering helpers shared by every template. */

declare(strict_types=1);

/** Buttons from SiteBlock::buttons(): first is primary, the rest secondary */
function site_buttons(array $buttons, string $extra = ''): string
{
    if ($buttons === []) {
        return '';
    }
    $html = '<div class="btn-row' . ($extra !== '' ? ' ' . e($extra) : '') . '">';
    foreach ($buttons as $btn) {
        $html .= '<a class="btn ' . ($btn['primary'] ? 'btn--primary' : 'btn--secondary') . '" href="' . e($btn['url']) . '">'
               . e($btn['label']) . '</a>';
    }
    return $html . '</div>';
}

/** Section heading group: optional eyebrow + h2 + optional intro text */
function site_heading(SiteBlock $b, string $class = 's-head', string $tag = 'h2'): string
{
    if (!$b->has('title') && !$b->has('eyebrow')) {
        return '';
    }
    $html = '<header class="' . e($class) . '">';
    if ($b->has('eyebrow')) {
        $html .= '<p class="eyebrow">' . $b->e('eyebrow') . '</p>';
    }
    if ($b->has('title')) {
        $html .= "<$tag class=\"s-title\" id=\"" . e($b->id()) . "\">" . $b->e('title') . "</$tag>";
    }
    if ($b->has('text')) {
        $html .= '<div class="s-lead">' . $b->p('text') . '</div>';
    }
    return $html . '</header>';
}

function site_labelled(SiteBlock $b): string
{
    return $b->has('title') ? ' aria-labelledby="' . e($b->id()) . '"' : '';
}

/** <img> with sensible defaults; decorative when $alt === '' */
function site_img(string $src, string $alt = '', string $class = '', bool $eager = false, string $sizes = ''): string
{
    if ($src === '') {
        return '';
    }
    return '<img src="' . e($src) . '" alt="' . e($alt) . '"' . ($class !== '' ? ' class="' . e($class) . '"' : '')
         . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
}

/** Logo / wordmark for the header or footer */
function site_brand(bool $onDark, string $class = 'brand'): string
{
    $company = Site::company();
    $logo = Site::logoUrl($onDark);
    $showName = Site::raw('global', 'brand', 'show_name') === 'yes';
    $html = '<a class="' . e($class) . '" href="' . e(Site::pageUrl('home')) . '" aria-label="' . e($company) . ' — home">';
    if ($logo !== '') {
        $html .= '<img class="brand__logo" src="' . e($logo) . '" alt="">';
    }
    if ($logo === '' || $showName) {
        $html .= '<span class="brand__name">' . e($company) . '</span>';
    }
    return $html . '</a>';
}

/** Social links from setup (SM1–SM6) */
function site_social(array $setup, string $class = 'social'): string
{
    try {
        $links = get_social_media_links($setup);
    } catch (Throwable $e) {
        $links = [];
    }
    $html = '';
    foreach ($links as $l) {
        if ($l['url'] === '' || !preg_match('#^https?://#i', $l['url'])) {
            continue;
        }
        $glyph = site_social_glyph($l['card']);
        $inner = $glyph ?? '<img src="' . e(preg_match('#^https?://#i', $l['image']) ? $l['image'] : asset_url($l['image'])) . '" alt="">';
        $html .= '<li><a href="' . e($l['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($l['title']) . '">' . $inner . '</a></li>';
    }
    return $html !== '' ? '<ul class="' . e($class) . '">' . $html . '</ul>' : '';
}

/** Contact lines from setup: address, phone, email */
function site_contact_lines(array $setup): array
{
    $lines = [];
    if (($a = format_address($setup)) !== '') {
        $lines['address'] = ['pin', 'Address', e($a)];
    }
    foreach (['telno-1', 'telno-2', 'telno-3'] as $k) {
        if (!empty($setup[$k])) {
            $tel = preg_replace('/[^0-9+]/', '', (string) $setup[$k]);
            $lines['phone'] = ['phone', 'Phone', '<a href="tel:' . e($tel) . '">' . e((string) $setup[$k]) . '</a>'];
            break;
        }
    }
    if (($m = primary_email($setup)) !== '') {
        $lines['email'] = ['mail', 'Email', '<a href="mailto:' . e($m) . '">' . e($m) . '</a>'];
    }
    return $lines;
}

function site_hours(): array
{
    try {
        $rows = get_business_hours();
    } catch (Throwable $e) {
        return [];
    }
    $out = [];
    foreach ($rows as $r) {
        if (trim((string) ($r['Day'] ?? '')) !== '') {
            $out[] = [(string) $r['Day'], format_business_hours_row($r)];
        }
    }
    return $out;
}

/** Shape names -> [name, image url] using the inventory's own shape icons */
function site_shapes(string $list): array
{
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', strtolower($list)))) as $name) {
        $slug = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $name));
        $file = dirname(__DIR__) . "/assets/img/shapes/$slug.png";
        if ($slug !== '' && is_file($file)) {
            $out[] = [ucwords(str_replace('-', ' ', $slug)), asset_url("/assets/img/shapes/$slug.png")];
        }
    }
    return $out;
}

/** Footer copyright + optional client login */
function site_footer_meta(): string
{
    $html = '<p class="footer-copy">' . e(Site::global('footer', 'copyright')) . '</p>';
    if (Site::raw('global', 'footer', 'show_login') === 'yes') {
        $html .= '<p class="footer-login"><a href="' . e(asset_url('/login.php')) . '">Client login</a></p>';
    }
    return $html;
}

/** Header CTA button ('' if not configured) */
function site_header_button(string $class = 'btn btn--primary'): string
{
    $label = trim(Site::global('header', 'button_label'));
    $url = Site::url(Site::raw('global', 'header', 'button_url'));
    return ($label !== '' && $url !== '') ? '<a class="' . e($class) . '" href="' . e($url) . '">' . e($label) . '</a>' : '';
}

/** Main menu <ul> */
function site_menu(array $nav, string $class = 'menu'): string
{
    $html = '<ul class="' . e($class) . '">';
    foreach ($nav as $item) {
        $html .= '<li><a href="' . e($item['url']) . '"' . ($item['current'] ? ' aria-current="page"' : '') . '>' . e($item['label']) . '</a></li>';
    }
    return $html . '</ul>';
}
