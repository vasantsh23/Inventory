<?php
/**
 * fonts.php
 * Fonts offered in the Theme Settings font dropdown.
 *   value stored in theme_settings => [display name, Google Fonts axis spec | null for system fonts]
 * An empty spec ('') loads the regular style only (for single-weight fonts).
 * Admins can still type any other font stack as a custom value — any
 * Google Fonts family typed there is loaded automatically.
 */

declare(strict_types=1);

const STD_AXES = 'ital,wght@0,300;0,400;0,600;0,700;0,800;1,400';

const FONT_LIBRARY = [
    // Original site default: Inter if the device has it, otherwise the system UI font. Nothing is downloaded.
    "'Inter', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" => ['Inter / system UI (original default)', null],
    "'Inter', sans-serif"                        => ['Inter (web font)',   STD_AXES],
    "'Roboto', sans-serif"                       => ['Roboto',             'ital,wght@0,300;0,400;0,500;0,700;1,400'],
    "'Open Sans', sans-serif"                    => ['Open Sans',          STD_AXES],
    "'Mulish', sans-serif"                       => ['Mulish',             STD_AXES],
    "'Jost', sans-serif"                         => ['Jost',               STD_AXES],
    "'Poppins', sans-serif"                      => ['Poppins',            STD_AXES],
    "'Montserrat', sans-serif"                   => ['Montserrat',         STD_AXES],
    "'Lato', sans-serif"                         => ['Lato',               'ital,wght@0,300;0,400;0,700;1,400'],
    "'Nunito Sans', sans-serif"                  => ['Nunito Sans',        STD_AXES],
    "'Work Sans', sans-serif"                    => ['Work Sans',          STD_AXES],
    "'Marcellus', serif"                         => ['Marcellus',          ''],
    "'Cormorant Garamond', serif"                => ['Cormorant Garamond', 'ital,wght@0,400;0,600;0,700;1,400'],
    "'Playfair Display', serif"                  => ['Playfair Display',   'ital,wght@0,400;0,600;0,700;1,400'],
    "'Cinzel', serif"                            => ['Cinzel',             'wght@400;600;700'],
    "'Lora', serif"                              => ['Lora',               'ital,wght@0,400;0,600;0,700;1,400'],
    "'Libre Baskerville', serif"                 => ['Libre Baskerville',  'ital,wght@0,400;0,700;1,400'],
    "'DM Serif Display', serif"                  => ['DM Serif Display',   'ital@0;1'],
    "'Great Vibes', cursive"                     => ['Great Vibes',        ''],
    "Georgia, serif"                             => ['Georgia (system)',   null],
    "Arial, Helvetica, sans-serif"               => ['Arial (system)',     null],
    "Arial, sans-serif"                          => ['Arial (system, original results font)', null],
    "system-ui, sans-serif"                      => ['System UI',          null],
    "'SFMono-Regular', Consolas, monospace"      => ['Monospace (system)',  null],
    "Georgia, 'Times New Roman', serif"          => ['Georgia / Times (system)', null],
];

/** First family names that are installed on devices, never fetched from Google */
const SYSTEM_FONTS = ['georgia', 'arial', 'helvetica', 'system-ui', 'serif', 'sans-serif',
                      'cursive', 'monospace', 'times new roman', 'times', 'verdana', 'tahoma',
                      'ui-monospace', 'sfmono-regular', 'consolas', 'menlo', 'courier new',
                      '-apple-system', 'segoe ui', 'calibri', 'cambria', 'trebuchet ms', 'inherit'];

/** Build one Google Fonts URL covering every web font used in the theme */
function google_fonts_url(array $fontValues): ?string
{
    $families = [];
    foreach (array_unique($fontValues) as $stack) {
        $first = trim(explode(',', (string) $stack)[0], " '\"");
        if ($first === '' || in_array(strtolower($first), SYSTEM_FONTS, true)) {
            continue;
        }
        $axes = array_key_exists($stack, FONT_LIBRARY) ? FONT_LIBRARY[$stack][1] : '';
        if ($axes === null) {
            continue; // system font from the library
        }
        $families[$first] = 'family=' . str_replace(' ', '+', $first) . ($axes !== '' ? ':' . $axes : '');
    }
    return $families
        ? 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap'
        : null;
}
