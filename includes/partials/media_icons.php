<?php
declare(strict_types=1);

/**
 * Icon strip (video / hand video / info video / certificate) shown next
 * to the Stock# on Results and View Cart/Selected.
 *
 * $links = build_result_media_links(...) result, or null to draw the
 * header version (all four icons, not clickable). A missing/invalid URL
 * leaves an empty slot of the same width so icons stay aligned in
 * every row — the icon itself is not drawn.
 */
function render_media_icons(?array $links): string
{
    $defs = [
        'video'     => ['Video (360°)', '<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h8A2.5 2.5 0 0 1 16 6.5v2.2l4.2-2.4a.6.6 0 0 1 .9.5v10.4a.6.6 0 0 1-.9.5L16 15.3v2.2a2.5 2.5 0 0 1-2.5 2.5h-8A2.5 2.5 0 0 1 3 17.5z"/>'],
        'handvideo' => ['Hand video',   '<path d="M12 2.5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19zm-2.2 5.4a.7.7 0 0 1 1.05-.6l6 3.6a.7.7 0 0 1 0 1.2l-6 3.6a.7.7 0 0 1-1.05-.6z"/>'],
        'infovideo' => ['Info video',   '<path d="M7 3h10l4.5 6L12 21.5 2.5 9zM8.2 4.9 5.6 8.5h3.2zm7.6 0-.6 3.6h3.2zM12 4.9l-1.3 3.6h2.6zM5.7 10l5.5 8.2L9.1 10zm13.6 0h-3.4l-2.1 8.2zM10.7 10 12 16.5l1.3-6.5z" fill-rule="evenodd"/>'],
        'cert'      => ['Certificate',  '<path d="M6 2h8l5 5v15H6zm7 1.5V8h4.5zM8 12h8v1.4H8zm0 3h8v1.4H8zm0 3h5v1.4H8z" fill-rule="evenodd"/>'],
    ];
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
