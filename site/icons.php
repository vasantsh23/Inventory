<?php
/**
 * icons.php — small line icons (24×24, stroke = currentColor) used by the
 * website sections. Chosen per item in Admin -> Website -> Page Content.
 */

declare(strict_types=1);

const SITE_ICON_PATHS = [
    'diamond'     => '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20M9 3l-2 6 5 12 5-12-2-6"/>',
    'rough'       => '<path d="M12 2l8 6-2 10-6 4-6-4-2-10z"/><path d="M12 2l-2 9 8-3M10 11l-6-3M10 11l2 11"/>',
    'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>',
    'shield'      => '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
    'certificate' => '<rect x="3" y="4" width="18" height="13" rx="1"/><path d="M7 8h10M7 11h6"/><circle cx="16" cy="16" r="2.5"/><path d="M14.8 18.2L14 22l2-1 2 1-.8-3.8"/>',
    'handshake'   => '<path d="M2 11l4-4 4 2 3-2 4 1 5 3"/><path d="M6 7v7l5 5c.8.8 2 .8 2.8 0l4.7-4.7c.8-.8.8-2 0-2.8L13 7"/><path d="M9 14l2 2M11 12l2.5 2.5"/>',
    'truck'       => '<path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
    'scale'       => '<path d="M12 3v18M7 21h10M5 7h14"/><path d="M5 7l-3 6c0 1.7 1.3 3 3 3s3-1.3 3-3zM19 7l-3 6c0 1.7 1.3 3 3 3s3-1.3 3-3z"/>',
    'leaf'        => '<path d="M5 19C4 11 9 5 20 4c0 10-5 15-13 15"/><path d="M5 19c3-4 6-7 10-9"/>',
    'sparkle'     => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.7 1.8 1.8.7-1.8.7L19 21l-.7-1.8-1.8-.7 1.8-.7z"/>',
    'eye'         => '<circle cx="10" cy="10" r="6"/><path d="M14.5 14.5L21 21"/><path d="M8 8.5l2-1.5 2 1.5-2 3z"/>',
    'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'pin'         => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'phone'       => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
    'mail'        => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 7l9 6 9-6"/>',
    'check'       => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.8 2.8L16.5 9"/>',
    'people'      => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c2.9.3 5 2.7 5 5.8"/>',
    'search'      => '<circle cx="11" cy="11" r="7"/><path d="M16 16l5 5"/>',
    'layers'      => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 12.5l9 5 9-5M3 16.5l9 5 9-5"/>',
    'drop'        => '<path d="M12 3s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11z"/>',
    'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    // UI icons
    'arrow-left'  => '<path d="M15 5l-7 7 7 7"/>',
    'arrow-right' => '<path d="M9 5l7 7-7 7"/>',
    'menu'        => '<path d="M3 7h18M3 12h18M3 17h18"/>',
    'close'       => '<path d="M5 5l14 14M19 5L5 19"/>',
    'plus'        => '<path d="M12 5v14M5 12h14"/>',
    'star'        => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z" fill="currentColor"/>',
    'quote'       => '<path d="M4 17c0-5 2-8 6-10M13 17c0-5 2-8 6-10"/><circle cx="7" cy="16" r="3"/><circle cx="16" cy="16" r="3"/>',
];

function site_icon(string $name, string $class = 'ico', ?string $label = null): string
{
    $paths = SITE_ICON_PATHS[$name] ?? SITE_ICON_PATHS['diamond'];
    $a11y = $label !== null ? ' role="img" aria-label="' . e($label) . '"' : ' aria-hidden="true" focusable="false"';
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" '
         . 'stroke-linecap="round" stroke-linejoin="round"' . $a11y . '>' . $paths . '</svg>';
}

/** Social platform name -> tiny glyph (falls back to the configured image) */
function site_social_glyph(string $card): ?string
{
    $k = strtolower(trim($card));
    $g = [
        'facebook'  => '<path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.3-.6 1-.6z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor"/>',
        'linkedin'  => '<path d="M4 9h4v11H4zM6 3.5a2.2 2.2 0 1 1 0 4.4 2.2 2.2 0 0 1 0-4.4zM10 9h3.8v1.6c.6-1 1.9-1.9 3.8-1.9 3.3 0 4.4 2 4.4 5.2V20h-4v-5.4c0-1.4-.3-2.6-1.8-2.6s-2.2 1.1-2.2 2.6V20h-4z" fill="currentColor" stroke="none"/>',
        'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
        'twitter'   => '<path d="M4 4l16 16M20 4L4 20"/>',
        'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="3.5"/><path d="M10 9l5 3-5 3z" fill="currentColor"/>',
        'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.8z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 1c-1-.5-2-1.5-2.5-2.5l1-1-1-2z"/>',
    ];
    if (!isset($g[$k])) {
        return null;
    }
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">' . $g[$k] . '</svg>';
}
