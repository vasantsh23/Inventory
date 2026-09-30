<?php
/**
 * Maison: light, editorial luxury (after Amour Pur and Kediam).
 * Each page lists its sections top to bottom: 'block' is the content
 * block in site_content, 'as' is the renderer in sections.php
 * (function mz_<as>). Reorder or remove lines to change a page.
 */
return [
    'name'          => 'Maison',
    'sort'          => 1,
    'prefix'        => 'mz',
    'theme_section' => 'tpl_maison',
    'tagline'       => 'Light, editorial and quiet',
    'description'   => 'Fine serif headings, generous white space and champagne accents. Full-bleed image halves alternate with calm text bands, finishing on a dark contact band.',
    'inspired_by'   => 'Amour Pur, Kediam',
    'pages' => [
        'home' => [
            ['block' => 'hero',         'as' => 'hero', 'size' => 'full', 'art' => 'brilliant'],
            ['block' => 'stats',        'as' => 'figures'],
            ['block' => 'intro',        'as' => 'lede'],
            ['block' => 'specialities', 'as' => 'grid', 'tone' => 'alt'],
            ['block' => 'feature',      'as' => 'split', 'art' => 'profile'],
            ['block' => 'process',      'as' => 'steps'],
            ['block' => 'faq',          'as' => 'faq', 'tone' => 'alt'],
            ['block' => 'cta',          'as' => 'cta'],
        ],
        'about' => [
            ['block' => 'hero',     'as' => 'hero', 'art' => 'profile'],
            ['block' => 'story',    'as' => 'split', 'art' => 'brilliant'],
            ['block' => 'timeline', 'as' => 'timeline'],
            ['block' => 'values',   'as' => 'grid', 'tone' => 'alt'],
            ['block' => 'quote',    'as' => 'quote', 'art' => 'rough'],
        ],
        'services' => [
            ['block' => 'hero',     'as' => 'hero', 'art' => 'brilliant'],
            ['block' => 'intro',    'as' => 'lede'],
            ['block' => 'services', 'as' => 'cards', 'tone' => 'alt'],
            ['block' => 'process',  'as' => 'steps'],
            ['block' => 'cta',      'as' => 'cta'],
        ],
        'diamonds' => [
            ['block' => 'hero',       'as' => 'hero', 'art' => 'brilliant'],
            ['block' => 'intro',      'as' => 'lede'],
            ['block' => 'categories', 'as' => 'cards', 'tone' => 'alt'],
            ['block' => 'inventory',  'as' => 'split', 'art' => 'brilliant', 'tone' => 'dark', 'reverse' => true],
            ['block' => 'education',  'as' => 'grid'],
        ],
        'responsible' => [
            ['block' => 'hero',      'as' => 'hero', 'art' => 'rough'],
            ['block' => 'intro',     'as' => 'lede'],
            ['block' => 'practices', 'as' => 'grid', 'tone' => 'alt'],
            ['block' => 'standards', 'as' => 'timeline'],
            ['block' => 'quote',     'as' => 'quote', 'art' => 'brilliant'],
        ],
        'sustainability' => [
            ['block' => 'hero',    'as' => 'hero', 'art' => 'rough'],
            ['block' => 'intro',   'as' => 'lede'],
            ['block' => 'pillars', 'as' => 'grid', 'tone' => 'alt'],
            ['block' => 'closing', 'as' => 'cta'],
        ],
        'contact' => [
            ['block' => 'hero',  'as' => 'hero', 'art' => 'profile'],
            ['block' => 'form',  'as' => 'contact', 'always' => true],
        ],
    ],
];
