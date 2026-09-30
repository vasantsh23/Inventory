<?php
/**
 * Atelier: clean, modern corporate (after Diamond Brothers and Trishla).
 * Each page lists its sections top to bottom: 'block' is the content
 * block in site_content, 'as' is the renderer in sections.php
 * (function at_<as>). Reorder or remove lines to change a page.
 */
return [
    'name'          => 'Atelier',
    'sort'          => 3,
    'prefix'        => 'at',
    'theme_section' => 'tpl_atelier',
    'tagline'       => 'Clean, modern and structured',
    'description'   => 'A white site with a deep teal colour band, a split hero, a step-by-step timeline, ruled lists with small red markers, an FAQ accordion and card-based contact details.',
    'inspired_by'   => 'Diamond Brothers, Trishla',
    'pages' => [
        'home' => [
            ['block' => 'hero',         'as' => 'hero', 'size' => 'full', 'art' => 'profile'],
            ['block' => 'stats',        'as' => 'band'],
            ['block' => 'process',      'as' => 'timeline'],
            ['block' => 'intro',        'as' => 'story', 'art' => 'brilliant', 'tone' => 'alt'],
            ['block' => 'specialities', 'as' => 'rows'],
            ['block' => 'feature',      'as' => 'story', 'art' => 'profile', 'reverse' => true, 'tone' => 'alt'],
            ['block' => 'faq',          'as' => 'faq'],
            ['block' => 'cta',          'as' => 'cta', 'art' => 'brilliant'],
        ],
        'about' => [
            ['block' => 'hero',     'as' => 'hero', 'art' => 'brilliant'],
            ['block' => 'story',    'as' => 'story', 'art' => 'profile'],
            ['block' => 'values',   'as' => 'band'],
            ['block' => 'timeline', 'as' => 'timeline'],
            ['block' => 'quote',    'as' => 'quote'],
        ],
        'services' => [
            ['block' => 'hero',     'as' => 'hero', 'art' => 'profile'],
            ['block' => 'intro',    'as' => 'lede'],
            ['block' => 'services', 'as' => 'tiles', 'tone' => 'alt'],
            ['block' => 'process',  'as' => 'timeline'],
            ['block' => 'cta',      'as' => 'cta', 'art' => 'profile'],
        ],
        'diamonds' => [
            ['block' => 'hero',       'as' => 'hero', 'art' => 'brilliant'],
            ['block' => 'intro',      'as' => 'lede'],
            ['block' => 'categories', 'as' => 'tiles', 'tone' => 'alt'],
            ['block' => 'education',  'as' => 'band'],
            ['block' => 'inventory',  'as' => 'cta', 'art' => 'brilliant'],
        ],
        'responsible' => [
            ['block' => 'hero',      'as' => 'hero', 'art' => 'rough'],
            ['block' => 'intro',     'as' => 'lede'],
            ['block' => 'practices', 'as' => 'tiles', 'tone' => 'alt'],
            ['block' => 'standards', 'as' => 'rows'],
            ['block' => 'quote',     'as' => 'quote'],
        ],
        'sustainability' => [
            ['block' => 'hero',    'as' => 'hero', 'art' => 'rough'],
            ['block' => 'intro',   'as' => 'lede'],
            ['block' => 'pillars', 'as' => 'band'],
            ['block' => 'closing', 'as' => 'cta', 'art' => 'rough'],
        ],
        'contact' => [
            ['block' => 'hero', 'as' => 'hero', 'art' => 'profile'],
            ['block' => 'form', 'as' => 'contact', 'always' => true],
        ],
    ],
];
