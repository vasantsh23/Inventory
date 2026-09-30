<?php
/**
 * Noir: dark charcoal and gold (after NN Diamonds).
 * Each page lists its sections top to bottom: 'block' is the content
 * block in site_content, 'as' is the renderer in sections.php
 * (function nr_<as>). Reorder or remove lines to change a page.
 */
return [
    'name'          => 'Noir',
    'sort'          => 2,
    'prefix'        => 'nr',
    'theme_section' => 'tpl_noir',
    'tagline'       => 'Dark, rich and gold-accented',
    'description'   => 'A charcoal palette with gold details, an info bar above the menu, a rotating hero, a key-figures strip and centred sections built from cards.',
    'inspired_by'   => 'NN Diamonds',
    'pages' => [
        'home' => [
            ['block' => 'hero',         'as' => 'hero', 'slider' => true, 'size' => 'full'],
            ['block' => 'stats',        'as' => 'statbar'],
            ['block' => 'intro',        'as' => 'centered'],
            ['block' => 'specialities', 'as' => 'icon_cards', 'tone' => 'alt'],
            ['block' => 'feature',      'as' => 'framed', 'art' => 'brilliant'],
            ['block' => 'process',      'as' => 'steps', 'tone' => 'alt'],
            ['block' => 'faq',          'as' => 'faq'],
            ['block' => 'cta',          'as' => 'cta'],
        ],
        'about' => [
            ['block' => 'hero',     'as' => 'hero'],
            ['block' => 'story',    'as' => 'framed', 'art' => 'profile'],
            ['block' => 'timeline', 'as' => 'steps', 'tone' => 'alt'],
            ['block' => 'values',   'as' => 'feature_cards'],
            ['block' => 'quote',    'as' => 'quote'],
        ],
        'services' => [
            ['block' => 'hero',     'as' => 'hero'],
            ['block' => 'intro',    'as' => 'centered'],
            ['block' => 'services', 'as' => 'feature_cards', 'tone' => 'alt'],
            ['block' => 'process',  'as' => 'steps'],
            ['block' => 'cta',      'as' => 'cta'],
        ],
        'diamonds' => [
            ['block' => 'hero',       'as' => 'hero'],
            ['block' => 'intro',      'as' => 'centered'],
            ['block' => 'categories', 'as' => 'icon_cards', 'tone' => 'alt'],
            ['block' => 'inventory',  'as' => 'framed', 'art' => 'brilliant', 'reverse' => true],
            ['block' => 'education',  'as' => 'icon_cards', 'tone' => 'alt'],
        ],
        'responsible' => [
            ['block' => 'hero',      'as' => 'hero'],
            ['block' => 'intro',     'as' => 'centered'],
            ['block' => 'practices', 'as' => 'feature_cards', 'tone' => 'alt'],
            ['block' => 'standards', 'as' => 'icon_cards'],
            ['block' => 'quote',     'as' => 'quote'],
        ],
        'sustainability' => [
            ['block' => 'hero',    'as' => 'hero'],
            ['block' => 'intro',   'as' => 'centered'],
            ['block' => 'pillars', 'as' => 'icon_cards', 'tone' => 'alt'],
            ['block' => 'closing', 'as' => 'cta'],
        ],
        'contact' => [
            ['block' => 'hero', 'as' => 'hero'],
            ['block' => 'form', 'as' => 'contact', 'always' => true],
        ],
    ],
];
