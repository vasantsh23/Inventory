<?php
declare(strict_types=1);

/**
 * Icon strip (image / 360 video / hand video / info video / certificate) shown next
 * to the Stock# on Results and View Cart/Selected.
 *
 * $links = build_result_media_links(...) result, or null to draw the
 * header version (all four icons, not clickable). A missing/invalid URL
 * leaves an empty slot of the same width so icons stay aligned in
 * every row — the icon itself is not drawn.
 */
function media_icon_defs(): array
{
    return [
        'image'     => ['Image',        '<path d="M4 4h16a1.5 1.5 0 0 1 1.5 1.5v13A1.5 1.5 0 0 1 20 20H4a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 4 4zm.5 2v9.6l4.2-4.3a1 1 0 0 1 1.4 0l3.4 3.4 2.2-2.2a1 1 0 0 1 1.4 0l2.4 2.4V6zm11 1.5a1.8 1.8 0 1 1 0 3.6 1.8 1.8 0 0 1 0-3.6z" fill-rule="evenodd"/>'],
        'video'     => ['Video (360°)', '<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h8A2.5 2.5 0 0 1 16 6.5v2.2l4.2-2.4a.6.6 0 0 1 .9.5v10.4a.6.6 0 0 1-.9.5L16 15.3v2.2a2.5 2.5 0 0 1-2.5 2.5h-8A2.5 2.5 0 0 1 3 17.5z"/>'],
        'handvideo' => ['Hand video',   '<path d="M12 2.5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19zm-2.2 5.4a.7.7 0 0 1 1.05-.6l6 3.6a.7.7 0 0 1 0 1.2l-6 3.6a.7.7 0 0 1-1.05-.6z"/>'],
        'infovideo' => ['Info video',   '<path d="M7 3h10l4.5 6L12 21.5 2.5 9zM8.2 4.9 5.6 8.5h3.2zm7.6 0-.6 3.6h3.2zM12 4.9l-1.3 3.6h2.6zM5.7 10l5.5 8.2L9.1 10zm13.6 0h-3.4l-2.1 8.2zM10.7 10 12 16.5l1.3-6.5z" fill-rule="evenodd"/>'],
        'cert'      => ['Certificate',  '<path d="M6 2h8l5 5v15H6zm7 1.5V8h4.5zM8 12h8v1.4H8zm0 3h8v1.4H8zm0 3h5v1.4H8z" fill-rule="evenodd"/>'],
    ];
}

function render_media_icons(?array $links): string
{
    $defs = media_icon_defs();
    $html = '<span class="media-icons' . ($links === null ? ' media-icons--head' : '') . '">';
    foreach ($defs as $key => [$label, $path]) {
        $svg = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $path . '</svg>';
        if ($links === null) {
            $html .= '<span class="media-icon media-icon--' . $key . '" title="' . e($label) . '">' . $svg . '</span>';
        } elseif (!empty($links[$key])) {
            $html .= '<a class="media-icon media-icon--' . $key . '" href="' . e($links[$key]) . '" target="_blank" rel="noopener" title="' . e($label) . '" aria-label="' . e($label) . '">' . $svg . '</a>';
        } else {
            $html .= '<span class="media-icon media-icon--empty" aria-hidden="true"></span>';
        }
    }
    return $html . '</span>';
}

/**
 * The same four icons as separate table cells (one column each), for
 * the bordered Results / View Cart table. $links = null draws the
 * header cells (icons only, not clickable); a row with no valid URL
 * for an icon gets an empty cell.
 */
function render_media_cells(?array $links): string
{
    $html = '';
    foreach (media_icon_defs() as $key => [$label, $path]) {
        $svg = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' . $path . '</svg>';
        if ($links === null) {
            $html .= '<th class="rs-media-col rs-media-col--' . $key . '" title="' . e($label) . '"><span class="media-icon">' . $svg . '</span><span class="rs-sr-only">' . e($label) . '</span></th>';
        } elseif (!empty($links[$key])) {
            $html .= '<td class="rs-media-col" data-label="' . e($label) . '"><a class="media-icon media-icon--' . $key . '" href="' . e($links[$key]) . '" target="_blank" rel="noopener" title="' . e($label) . '" aria-label="' . e($label) . '">' . $svg . '</a></td>';
        } else {
            $html .= '<td class="rs-media-col rs-media-col--empty" data-label="' . e($label) . '"></td>';
        }
    }
    return $html;
}
