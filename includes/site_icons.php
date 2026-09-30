<?php
/**
 * site_icons.php
 * Small line-icon set used by the public website templates. Icons are
 * inline SVG drawn with currentColor, so every template colours them
 * from its own theme settings.
 *
 * In Admin → Manage Tables → Website Content, the "icon" column of a
 * row takes one of the keys below (e.g. "diamond", "shield").
 */

declare(strict_types=1);

const SITE_ICONS = [
    'diamond'     => '<path d="M6 3h12l3 5-9 13L3 8z"/><path d="M3 8h18M9 3 7.5 8 12 21M15 3l1.5 5L12 21"/>',
    'spark'       => '<path d="M12 2v5M12 17v5M2 12h5M17 12h5M5 5l3 3M16 16l3 3M19 5l-3 3M8 16l-3 3"/><circle cx="12" cy="12" r="2"/>',
    'gem'         => '<ellipse cx="12" cy="12" rx="6.5" ry="9"/><ellipse cx="12" cy="12" rx="3" ry="5"/><path d="M12 3v4M12 17v4M5.5 12H9M15 12h3.5"/>',
    'drop'        => '<path d="M12 3c3.5 4.2 6 7.6 6 10.6A6 6 0 0 1 6 13.6C6 10.6 8.5 7.2 12 3z"/><path d="M9.5 14.5a2.6 2.6 0 0 0 2.5 2.4"/>',
    'search'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.8-4.8"/>',
    'mail'        => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6 8.5 7 8.5-7"/>',
    'shield'      => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.2 7.5 9.5 4.4-1.3 7.5-4.9 7.5-9.5V6z"/><path d="m8.8 12 2.3 2.3 4.2-4.6"/>',
    'truck'       => '<path d="M3 6h11v10H3zM14 9.5h4l3 3.5v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
    'factory'     => '<path d="M3 21V10l6 3.5V10l6 3.5V5h3v16z"/><path d="M3 21h18M7 17h1.5M11.5 17H13M16 17h1.5"/>',
    'certificate' => '<rect x="3.5" y="3.5" width="17" height="13" rx="1"/><path d="M7 8h10M7 11.5h6"/><circle cx="16" cy="16.5" r="2.5"/><path d="m14.6 18.6-.9 3 2.3-1.1 2.3 1.1-.9-3"/>',
    'handshake'   => '<path d="m2.5 11 3.5-3.5 3.5 1.5 2.5-2 3 .5 2.5 2.5 4 1"/><path d="M6 7.5 11.5 14a1.6 1.6 0 0 0 2.3-2.2L10 8"/><path d="m9 16.5 1.2 1.2a1.6 1.6 0 0 0 2.2-2.3M12.5 18.8l.4.4a1.6 1.6 0 0 0 2.3-2.3l-2-2M15.2 16.8a1.6 1.6 0 0 0 2.3-2.3L14 11"/><path d="M2.5 11 6 15"/>',
    'layers'      => '<path d="m12 3 9 4.5-9 4.5-9-4.5z"/><path d="m3 12 9 4.5 9-4.5M3 16.5 12 21l9-4.5"/>',
    'eye'         => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
    'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c.5-3.6 2.9-5.6 6-5.6s5.5 2 6 5.6"/><circle cx="17" cy="9" r="2.4"/><path d="M16.5 14.4c2.4.2 4 1.9 4.5 4.6"/>',
    'leaf'        => '<path d="M5 19c0-8.5 5.5-14 15-14 0 9.5-5.5 15-14 15"/><path d="M5 19 13 11"/>',
    'recycle'     => '<path d="m7.5 8 2.8-4.5a2 2 0 0 1 3.4 0l2 3.3"/><path d="m14 6.8 2.7.1-.1-2.7M18.5 11l2.6 4.5a2 2 0 0 1-1.7 3H15.5"/><path d="m17.3 16.2-1.8 2.3 1.9 2M9 18.5H4.6a2 2 0 0 1-1.7-3l2.1-3.6"/><path d="m3.7 12.4 1.3-.5 1.1 2.4"/>',
    'box'         => '<path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9z"/><path d="m3.5 7.5 8.5 4.5 8.5-4.5M12 12v9M7.8 5.3l8.4 4.5"/>',
    'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.7 3.8 5.7 3.8 9s-1.2 6.3-3.8 9c-2.6-2.7-3.8-5.7-3.8-9S9.4 5.7 12 3z"/>',
    'scale'       => '<path d="M12 3v18M7 21h10M4 7h16M12 4.5l-1 2.5h2z"/><path d="m6 7-3 7a3 3 0 0 0 6 0zM18 7l-3 7a3 3 0 0 0 6 0z"/>',
    'ring'        => '<circle cx="12" cy="14.5" r="6.5"/><path d="m9 8 1.5-3.5h3L15 8"/><path d="M10.5 4.5 12 8l1.5-3.5"/>',
    'document'    => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 11h6M9 14.5h6M9 18h4"/>',
    'phone'       => '<path d="M5 3.5h3.5l1.8 4.5-2.3 1.4a11 11 0 0 0 6.6 6.6l1.4-2.3 4.5 1.8V19a1.5 1.5 0 0 1-1.6 1.5A16.5 16.5 0 0 1 3.5 5.1 1.5 1.5 0 0 1 5 3.5z"/>',
    'pin'         => '<path d="M12 21s-6.5-6-6.5-11.2a6.5 6.5 0 0 1 13 0C18.5 15 12 21 12 21z"/><circle cx="12" cy="9.8" r="2.4"/>',
    'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'chevron'     => '<path d="m9 5 7 7-7 7"/>',
    'chevron-left'=> '<path d="m15 5-7 7 7 7"/>',
    'plus'        => '<path d="M12 5v14M5 12h14"/>',
    'menu'        => '<path d="M3.5 7h17M3.5 12h17M3.5 17h17"/>',
    'close'       => '<path d="m5.5 5.5 13 13M18.5 5.5l-13 13"/>',
];

/** Inline SVG for an icon key; unknown keys fall back to the diamond. */
function site_icon(?string $name, string $class = 'icon'): string
{
    $name = strtolower(trim((string) $name));
    $paths = SITE_ICONS[$name] ?? SITE_ICONS['diamond'];
    return '<svg class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" width="24" height="24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . $paths . '</svg>';
}
