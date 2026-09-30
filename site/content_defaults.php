<?php
/**
 * content_defaults.php
 * The website's content model: every page, the sections (blocks) it can
 * show, the fields each section has, and the default text/images.
 *
 * - Values saved in Admin -> Website -> Page Content (table site_content)
 *   override these defaults; a field that has never been saved shows the
 *   default below, so the site looks complete straight after install.
 * - 'layouts' lists, per template, which blocks a page shows and in which
 *   order. That is what makes each template's page structure different.
 *   Admins can switch blocks on/off and reorder them per template
 *   (table site_blocks).
 *
 * Placeholders usable in any text: {company} {year} {phone} {email} {address}
 * Link fields accept: page:home, page:about, page:services, page:diamonds,
 * page:responsible, page:sustainability, page:contact, inventory,
 * /path/on/this/site, https://..., mailto:..., tel:...
 */

declare(strict_types=1);

if (!function_exists('sd_f')) {
    /** One field definition */
    function sd_f(string $type, string $label, string $default = '', ?array $options = null): array
    {
        return ['type' => $type, 'label' => $label, 'default' => $default, 'options' => $options];
    }

    /**
     * Repeated items (cards, steps, FAQs...). $spec = field => [type, label];
     * $rows = list of default rows (field => value). $count slots are created
     * so admins can fill more than the defaults; an item whose first field is
     * empty is not shown.
     */
    function sd_items(int $count, array $spec, array $rows): array
    {
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            foreach ($spec as $field => [$type, $label, $opts]) {
                $out["item{$i}_{$field}"] = sd_f($type, "Item $i — $label", (string) ($rows[$i - 1][$field] ?? ''), $opts);
            }
        }
        return $out;
    }

    define('SD_ICONS', [
        'diamond' => 'Diamond', 'rough' => 'Rough crystal', 'globe' => 'Globe', 'shield' => 'Shield',
        'certificate' => 'Certificate', 'handshake' => 'Handshake', 'truck' => 'Delivery', 'scale' => 'Scale',
        'leaf' => 'Leaf', 'sparkle' => 'Sparkle', 'eye' => 'Loupe / eye', 'clock' => 'Clock',
        'pin' => 'Map pin', 'phone' => 'Phone', 'mail' => 'Envelope', 'check' => 'Tick', 'people' => 'People',
        'search' => 'Search', 'layers' => 'Layers', 'drop' => 'Water drop', 'sun' => 'Sun',
    ]);
    define('SD_YESNO', ['yes' => 'Yes', 'no' => 'No']);
    define('SD_SIDE', ['left' => 'Image on the left', 'right' => 'Image on the right']);
}

$icon  = fn (string $label = 'Icon') => ['select', $label, SD_ICONS];
$txt   = fn (string $label) => ['text', $label, null];
$area  = fn (string $label) => ['textarea', $label, null];
$img   = fn (string $label = 'Image') => ['image', $label, null];

$I = '/assets/site/img/';

return [

    // ================================================================ GLOBAL
    'global' => [
        'label' => 'Site-wide',
        'file'  => null,
        'blocks' => [
            'brand' => ['type' => 'settings', 'label' => 'Brand', 'fields' => [
                'show_name'  => sd_f('select', 'Show the company name next to the logo', 'no', SD_YESNO),
                'logo_dark'  => sd_f('image', 'Logo for dark backgrounds (optional — used by dark headers and footers)'),
                'tagline'    => sd_f('text', 'Tagline under the logo', 'Diamonds, cut and traded with care'),
            ]],
            'header' => ['type' => 'settings', 'label' => 'Header', 'fields' => [
                'button_label' => sd_f('text', 'Header button label (blank = no button)', 'View inventory'),
                'button_url'   => sd_f('url', 'Header button link', 'inventory'),
            ]],
            'nav' => ['type' => 'settings', 'label' => 'Menu labels (leave blank to hide a page from the menu)', 'fields' => [
                'home'           => sd_f('text', 'Home', 'Home'),
                'about'          => sd_f('text', 'About', 'About'),
                'services'       => sd_f('text', 'Services', 'Services'),
                'diamonds'       => sd_f('text', 'Diamonds', 'Diamonds'),
                'responsible'    => sd_f('text', 'Responsible Practices', 'Responsible Practices'),
                'sustainability' => sd_f('text', 'Sustainability', 'Sustainability'),
                'contact'        => sd_f('text', 'Contact Us', 'Contact Us'),
            ]],
            'footer' => ['type' => 'settings', 'label' => 'Footer', 'fields' => [
                'about'      => sd_f('textarea', 'Short description', '{company} manufactures and trades polished diamonds for jewellers, designers and manufacturers.'),
                'copyright'  => sd_f('text', 'Copyright line', '© {year} {company}. All rights reserved.'),
                'show_login' => sd_f('select', 'Show a small "Client login" link', 'yes', SD_YESNO),
            ]],
            'form' => ['type' => 'settings', 'label' => 'Contact form', 'fields' => [
                'notify_email'    => sd_f('text', 'Send new enquiries to (blank = main company email from Site Setup)'),
                'success_message' => sd_f('textarea', 'Message shown after sending', 'Thank you — your message has been sent. We reply within one working day.'),
                'consent_text'    => sd_f('text', 'Consent checkbox text', 'I agree that {company} may use these details to reply to my enquiry.'),
                'button_label'    => sd_f('text', 'Send button label', 'Send message'),
            ]],
            'seo' => ['type' => 'settings', 'label' => 'Page titles', 'fields' => [
                'title_suffix' => sd_f('text', 'Added after every page title', ' | {company}'),
            ]],
        ],
    ],

    // ================================================================= HOME
    'home' => [
        'label' => 'Home',
        'file'  => 'index.php',
        'title' => 'Polished diamonds for the jewellery trade',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Hero banner', 'fields' => [
                'eyebrow'       => sd_f('text', 'Small line above the title', 'Manufacturer and trader of polished diamonds'),
                'title'         => sd_f('text', 'Title', 'Every stone, chosen for its light'),
                'text'          => sd_f('textarea', 'Text', 'We cut, grade and supply natural diamonds to jewellers and manufacturers — with certificates, consistent make and prices you can plan around.'),
                'image'         => sd_f('image', 'Background / hero image', $I . 'hero-atelier.webp'),
                'image_alt'     => sd_f('image', 'Alternative hero image (used by the Heritage template)', $I . 'diamond-profile.webp'),
                'image_dark'    => sd_f('image', 'Dark hero image (used by the Noir template)', $I . 'hero-field.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Browse inventory'),
                'button1_url'   => sd_f('url', 'Main button link', 'inventory'),
                'button2_label' => sd_f('text', 'Second button label', 'Our story'),
                'button2_url'   => sd_f('url', 'Second button link', 'page:about'),
            ]],
            'stats' => ['type' => 'stats', 'label' => 'Key figures', 'fields' => sd_items(4,
                ['value' => $txt('Figure'), 'label' => $txt('Label')],
                [['value' => '1993', 'label' => 'Founded'], ['value' => '30+', 'label' => 'Years in the trade'],
                 ['value' => '12,000', 'label' => 'Stones in stock'], ['value' => 'B2B', 'label' => 'Trade clients only']])],
            'features' => ['type' => 'features', 'label' => 'Why clients choose us', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Why the trade works with us'),
                'title'   => sd_f('text', 'Title', 'Consistency you can build a collection on'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(4, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text')], [
                ['icon' => 'globe', 'title' => 'Trusted worldwide', 'text' => 'Supplying jewellers across Europe, the Middle East and Asia.'],
                ['icon' => 'certificate', 'title' => 'Certified stones', 'text' => 'GIA, IGI and HRD reports on every stone above 0.30 ct.'],
                ['icon' => 'scale', 'title' => 'Fair, stable pricing', 'text' => 'Transparent lists, updated daily from our own stock.'],
                ['icon' => 'truck', 'title' => 'Insured delivery', 'text' => 'Fully insured shipping to your door, usually within 48 hours.'],
            ])],
            'intro' => ['type' => 'intro', 'label' => 'Introduction', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'About {company}'),
                'title'   => sd_f('text', 'Title', 'Three decades of polished expertise'),
                'text'    => sd_f('textarea', 'Text', "{company} brings together rough sourcing, our own cutting and polishing, and a trading desk that knows each stone by name.\n\nThe result is a supply chain jewellery brands can rely on: top-grade polish, ethical sourcing and service from people who answer the phone."),
                'image'   => sd_f('image', 'Image (optional)', ''),
            ]],
            'split1' => ['type' => 'split', 'label' => 'Feature with image — perfection', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'The make'),
                'title'        => sd_f('text', 'Title', 'What perfection looks like'),
                'text'         => sd_f('textarea', 'Text', "A well-cut diamond returns almost all the light that enters it. We plan every stone for brightness and symmetry first, then for weight.\n\nThat is why our parcels match from the first stone to the last — the same make, the same life, the same look in the setting."),
                'image'        => sd_f('image', 'Image', $I . 'diamond-top-black.webp'),
                'image_side'   => sd_f('select', 'Image position', 'right', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', 'Learn about the 4Cs'),
                'button_url'   => sd_f('url', 'Button link', 'page:diamonds'),
            ]],
            'split2' => ['type' => 'split', 'label' => 'Feature with image — certification', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Certification'),
                'title'        => sd_f('text', 'Title', 'Independently graded, every time'),
                'text'         => sd_f('textarea', 'Text', 'Each stone above 0.30 ct travels with a report from a leading gemmological laboratory. You see the grades before you buy, and your customer sees them after.'),
                'image'        => sd_f('image', 'Image', $I . 'grading-report.webp'),
                'image_side'   => sd_f('select', 'Image position', 'left', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', ''),
                'button_url'   => sd_f('url', 'Button link', ''),
            ]],
            'shapes' => ['type' => 'shapes', 'label' => 'Shop by shape', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Inventory'),
                'title'        => sd_f('text', 'Title', 'Search our stock by shape'),
                'text'         => sd_f('textarea', 'Text', 'Rounds and fancy shapes from 0.18 ct to 10 ct, updated daily.'),
                'shapes'       => sd_f('text', 'Shapes to show (comma-separated)', 'round, oval, cushion, emerald, princess, pear, marquise, radiant, asscher, heart'),
                'button_label' => sd_f('text', 'Button label', 'Open the full inventory'),
                'button_url'   => sd_f('url', 'Button link', 'inventory'),
            ]],
            'steps' => ['type' => 'steps', 'label' => 'How it works', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Working with us'),
                'title'   => sd_f('text', 'Title', 'How it works'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(5, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text')], [
                ['icon' => 'people', 'title' => 'Open a trade account', 'text' => 'Send us your company details. Most accounts are approved the same day.'],
                ['icon' => 'search', 'title' => 'Search live stock', 'text' => 'Filter our inventory by shape, size, colour, clarity and lab.'],
                ['icon' => 'eye', 'title' => 'Request stones on memo', 'text' => 'See the stones in person before you commit, in our office or yours.'],
                ['icon' => 'handshake', 'title' => 'Confirm your order', 'text' => 'Agree the price and we reserve the stones for you immediately.'],
                ['icon' => 'truck', 'title' => 'Insured delivery', 'text' => 'Your diamonds arrive fully insured, with their certificates.'],
            ])],
            'testimonials' => ['type' => 'testimonials', 'label' => 'Client reviews', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Clients'),
                'title'   => sd_f('text', 'Title', 'What our clients say'),
            ] + sd_items(4, ['quote' => $area('Quote'), 'name' => $txt('Name'), 'role' => $txt('Company / role')], [
                ['quote' => 'We have matched pairs from {company} for twelve years. The make is consistent enough that our setters can tell the difference.', 'name' => 'Isabelle Moreau', 'role' => 'Atelier Moreau, Paris'],
                ['quote' => 'Stones arrive exactly as graded, and when we need something unusual they find it within days.', 'name' => 'Daniel Hoffmann', 'role' => 'Hoffmann Juwelen, Munich'],
                ['quote' => 'Clear prices, fast memo and people who pick up the phone. That is rare in this trade.', 'name' => 'Priya Raman', 'role' => 'Raman Fine Jewellery, Dubai'],
            ])],
            'credentials' => ['type' => 'credentials', 'label' => 'Memberships & certifications', 'fields' => [
                'title' => sd_f('text', 'Title (optional)', ''),
            ] + sd_items(5, ['name' => $txt('Name'), 'text' => $txt('Small line'), 'image' => $img('Logo (optional)')], [
                ['name' => 'GIA', 'text' => 'Graded stones'], ['name' => 'IGI', 'text' => 'Graded stones'],
                ['name' => 'HRD', 'text' => 'Antwerp'], ['name' => 'RJC', 'text' => 'Certified member'],
                ['name' => 'Kimberley Process', 'text' => 'Compliant'],
            ])],
            'cta' => ['type' => 'cta', 'label' => 'Call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Looking for a specific stone?'),
                'text'          => sd_f('textarea', 'Text', 'Tell us the shape, size and grades. We will send options from stock or source it for you.'),
                'image'         => sd_f('image', 'Image', $I . 'tweezers.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Send a request'),
                'button1_url'   => sd_f('url', 'Main button link', 'page:contact'),
                'button2_label' => sd_f('text', 'Second button label', 'Browse inventory'),
                'button2_url'   => sd_f('url', 'Second button link', 'inventory'),
            ]],
            'contact' => ['type' => 'contact', 'label' => 'Contact form', 'fields' => [
                'title'      => sd_f('text', 'Title', 'Contact us'),
                'text'       => sd_f('textarea', 'Text', 'Reach out if you would like to open a trade account or request stones on memo.'),
                'show_form'  => sd_f('select', 'Show the enquiry form', 'yes', SD_YESNO),
                'show_hours' => sd_f('select', 'Show business hours', 'no', SD_YESNO),
                'map_url'    => sd_f('url', 'Directions link (e.g. a Google Maps link)', ''),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'intro', 'split1', 'split2', 'features', 'shapes', 'testimonials', 'contact'],
            'heritage' => ['hero', 'features', 'steps', 'split1', 'testimonials', 'cta', 'credentials'],
            'noir'     => ['hero', 'stats', 'intro', 'features', 'split1', 'shapes', 'testimonials', 'contact'],
        ],
    ],

    // ================================================================ ABOUT
    'about' => [
        'label' => 'About',
        'file'  => 'about.php',
        'title' => 'About us',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'About {company}'),
                'title'   => sd_f('text', 'Title', 'A family business, built one stone at a time'),
                'text'    => sd_f('textarea', 'Text', 'From a single cutting bench to a trading house supplying jewellers on four continents.'),
                'image'   => sd_f('image', 'Banner image', $I . 'diamond-studio.webp'),
                'image_dark' => sd_f('image', 'Dark banner image (Noir template)', $I . 'hero-field.webp'),
            ]],
            'intro' => ['type' => 'intro', 'label' => 'Our story', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Our story'),
                'title'   => sd_f('text', 'Title', 'Where we come from'),
                'text'    => sd_f('textarea', 'Text', "{company} was founded by a cutter who believed the trade deserved more consistent goods. We started by polishing for other houses, and our reputation for make brought us our first trading clients.\n\nToday a second generation runs the business. We still plan and polish many of our stones ourselves, and every parcel is checked by a grader who has worked here for years."),
                'image'   => sd_f('image', 'Image (optional)', ''),
            ]],
            'stats' => ['type' => 'stats', 'label' => 'Key figures', 'fields' => sd_items(4,
                ['value' => $txt('Figure'), 'label' => $txt('Label')],
                [['value' => '1993', 'label' => 'Founded'], ['value' => '2nd', 'label' => 'Generation'],
                 ['value' => '40', 'label' => 'Countries served'], ['value' => '3', 'label' => 'Offices']])],
            'split1' => ['type' => 'split', 'label' => 'Feature with image — craft', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Craft'),
                'title'        => sd_f('text', 'Title', 'Cutters first, traders second'),
                'text'         => sd_f('textarea', 'Text', 'Knowing how a stone was planned tells you how it will behave in the setting. Our traders sit next to our planners, so the advice you get comes from the bench, not a spreadsheet.'),
                'image'        => sd_f('image', 'Image', $I . 'rough-crystal.webp'),
                'image_side'   => sd_f('select', 'Image position', 'left', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', 'Our services'),
                'button_url'   => sd_f('url', 'Button link', 'page:services'),
            ]],
            'features' => ['type' => 'features', 'label' => 'Our values', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'What we stand for'),
                'title'   => sd_f('text', 'Title', 'Our values'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(4, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text')], [
                ['icon' => 'shield', 'title' => 'Integrity', 'text' => 'Every grade, weight and origin stated honestly — no exceptions.'],
                ['icon' => 'diamond', 'title' => 'Craft', 'text' => 'We would rather lose weight than lose light.'],
                ['icon' => 'handshake', 'title' => 'Relationships', 'text' => 'Most of our clients have worked with us for more than ten years.'],
                ['icon' => 'leaf', 'title' => 'Responsibility', 'text' => 'Traceable sourcing and fair treatment of everyone in our chain.'],
            ])],
            'testimonials' => ['type' => 'testimonials', 'label' => 'Quote from the founder', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', ''),
                'title'   => sd_f('text', 'Title', ''),
            ] + sd_items(2, ['quote' => $area('Quote'), 'name' => $txt('Name'), 'role' => $txt('Company / role')], [
                ['quote' => 'We stand out because of our make and our consistency. Everything else follows from that.', 'name' => 'The founders', 'role' => '{company}'],
            ])],
            'cta' => ['type' => 'cta', 'label' => 'Call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Meet us in person'),
                'text'          => sd_f('textarea', 'Text', 'Visit our office to see stones on the table, or ask for a video call with our traders.'),
                'image'         => sd_f('image', 'Image', $I . 'tweezers.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Book an appointment'),
                'button1_url'   => sd_f('url', 'Main button link', 'page:contact'),
                'button2_label' => sd_f('text', 'Second button label', ''),
                'button2_url'   => sd_f('url', 'Second button link', ''),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'intro', 'split1', 'features', 'testimonials', 'cta'],
            'heritage' => ['hero', 'intro', 'stats', 'split1', 'features', 'cta'],
            'noir'     => ['hero', 'intro', 'split1', 'stats', 'features', 'testimonials', 'cta'],
        ],
    ],

    // ============================================================= SERVICES
    'services' => [
        'label' => 'Services',
        'file'  => 'services.php',
        'title' => 'Services',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Services'),
                'title'   => sd_f('text', 'Title', 'From rough to the finished parcel'),
                'text'    => sd_f('textarea', 'Text', 'Buy from stock, have stones cut to order, or let us match pairs and layouts for your designs.'),
                'image'   => sd_f('image', 'Banner image', $I . 'diamond-black.webp'),
                'image_dark' => sd_f('image', 'Dark banner image (Noir template)', $I . 'diamond-black.webp'),
            ]],
            'cards' => ['type' => 'cards', 'label' => 'Services', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'What we do'),
                'title'   => sd_f('text', 'Title', 'How we can help'),
                'text'    => sd_f('textarea', 'Text', 'Each service is handled by the same small team, so you always know who to call.'),
            ] + sd_items(6, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text'), 'image' => $img('Image (optional)')], [
                ['icon' => 'diamond', 'title' => 'Polished diamonds from stock', 'text' => 'Rounds and fancy shapes from 0.18 ct to 10 ct, available to view today.'],
                ['icon' => 'rough', 'title' => 'Cutting to order', 'text' => 'Tell us the size and grades you need; we plan and polish them from rough.'],
                ['icon' => 'layers', 'title' => 'Pairs and layouts', 'text' => 'Matched pairs, graduated layouts and calibrated melee for your designs.'],
                ['icon' => 'certificate', 'title' => 'Certification service', 'text' => 'We submit, track and collect lab reports for stones you already own.'],
                ['icon' => 'eye', 'title' => 'Memo and consignment', 'text' => 'See stones before you buy, with clear memo terms for approved clients.'],
                ['icon' => 'truck', 'title' => 'Insured logistics', 'text' => 'Door-to-door insured shipping and customs paperwork handled for you.'],
            ])],
            'steps' => ['type' => 'steps', 'label' => 'How it works', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'The process'),
                'title'   => sd_f('text', 'Title', 'Ordering a stone cut to your brief'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(5, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text')], [
                ['icon' => 'mail', 'title' => 'Send your brief', 'text' => 'Shape, size, colour, clarity and the date you need it.'],
                ['icon' => 'rough', 'title' => 'We select the rough', 'text' => 'We show you the rough and the expected yield before cutting.'],
                ['icon' => 'diamond', 'title' => 'Planning and polishing', 'text' => 'Our cutters polish for light first, weight second.'],
                ['icon' => 'certificate', 'title' => 'Independent grading', 'text' => 'The finished stone goes to the laboratory of your choice.'],
                ['icon' => 'truck', 'title' => 'Delivered to you', 'text' => 'Insured delivery with the report and a full invoice.'],
            ])],
            'split1' => ['type' => 'split', 'label' => 'Feature with image — fancy colours', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Speciality'),
                'title'        => sd_f('text', 'Title', 'Fancy colour diamonds'),
                'text'         => sd_f('textarea', 'Text', 'Yellow, pink, blue and natural brown diamonds, sourced one by one and matched for colour under daylight lamps.'),
                'image'        => sd_f('image', 'Image', $I . 'fancy-colours.webp'),
                'image_side'   => sd_f('select', 'Image position', 'right', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', 'Ask about fancy colours'),
                'button_url'   => sd_f('url', 'Button link', 'page:contact'),
            ]],
            'faq' => ['type' => 'faq', 'label' => 'Frequently asked questions', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Questions'),
                'title'   => sd_f('text', 'Title', 'Frequently asked questions'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(6, ['question' => $txt('Question'), 'answer' => $area('Answer')], [
                ['question' => 'Who can buy from {company}?', 'answer' => 'We supply the jewellery trade: retailers, manufacturers, designers and other dealers. Private buyers are welcome to ask for a recommended jeweller.'],
                ['question' => 'How do I open a trade account?', 'answer' => 'Send us your company name, registration number and a trade reference through the contact form. Most accounts are approved within one working day.'],
                ['question' => 'Can I see stones before I buy?', 'answer' => 'Yes. Approved clients can request stones on memo, or view them in our office by appointment.'],
                ['question' => 'Which laboratories do you use?', 'answer' => 'Most of our stones carry GIA, IGI or HRD reports. We can submit stones to the laboratory you prefer.'],
                ['question' => 'How quickly do you deliver?', 'answer' => 'Stones in stock usually ship within 48 hours, fully insured.'],
            ])],
            'cta' => ['type' => 'cta', 'label' => 'Call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Tell us what you are looking for'),
                'text'          => sd_f('textarea', 'Text', 'Our traders reply the same working day.'),
                'image'         => sd_f('image', 'Image', $I . 'tweezers.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Contact our traders'),
                'button1_url'   => sd_f('url', 'Main button link', 'page:contact'),
                'button2_label' => sd_f('text', 'Second button label', 'Browse inventory'),
                'button2_url'   => sd_f('url', 'Second button link', 'inventory'),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'cards', 'split1', 'steps', 'faq', 'cta'],
            'heritage' => ['hero', 'cards', 'steps', 'split1', 'faq', 'cta'],
            'noir'     => ['hero', 'cards', 'steps', 'split1', 'faq', 'cta'],
        ],
    ],

    // ============================================================= DIAMONDS
    'diamonds' => [
        'label' => 'Diamonds',
        'file'  => 'diamonds.php',
        'title' => 'Diamonds',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow'       => sd_f('text', 'Small line above the title', 'Diamonds'),
                'title'         => sd_f('text', 'Title', 'Natural diamonds, graded without compromise'),
                'text'          => sd_f('textarea', 'Text', 'Browse our live inventory, or read how we grade every stone we sell.'),
                'image'         => sd_f('image', 'Banner image', $I . 'diamond-top-pearl.webp'),
                'image_dark'    => sd_f('image', 'Dark banner image (Noir template)', $I . 'diamond-top-black.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Browse inventory'),
                'button1_url'   => sd_f('url', 'Main button link', 'inventory'),
                'button2_label' => sd_f('text', 'Second button label', ''),
                'button2_url'   => sd_f('url', 'Second button link', ''),
            ]],
            'shapes' => ['type' => 'shapes', 'label' => 'Search by shape', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Inventory'),
                'title'        => sd_f('text', 'Title', 'Find your stone'),
                'text'         => sd_f('textarea', 'Text', 'Choose a shape to start searching our live stock. Prices and availability update daily.'),
                'shapes'       => sd_f('text', 'Shapes to show (comma-separated)', 'round, oval, cushion, emerald, princess, pear, marquise, radiant, asscher, heart'),
                'button_label' => sd_f('text', 'Button label', 'Open the full inventory'),
                'button_url'   => sd_f('url', 'Button link', 'inventory'),
            ]],
            'cards' => ['type' => 'cards', 'label' => 'The 4Cs', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Diamond education'),
                'title'   => sd_f('text', 'Title', 'The 4Cs'),
                'text'    => sd_f('textarea', 'Text', 'Four measures describe every diamond. Together they set its beauty and its price.'),
            ] + sd_items(6, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text'), 'image' => $img('Image (optional)')], [
                ['icon' => 'diamond', 'title' => 'Cut', 'text' => 'How well the facets return light. The most important of the four, and the only one made by people.'],
                ['icon' => 'drop', 'title' => 'Colour', 'text' => 'Graded from D (colourless) to Z. The less colour, the more rare the stone.'],
                ['icon' => 'eye', 'title' => 'Clarity', 'text' => 'The size and number of inclusions, graded under 10× magnification from FL to I3.'],
                ['icon' => 'scale', 'title' => 'Carat', 'text' => 'The weight of the stone. One carat is 0.2 grams; price rises steeply with size.'],
            ])],
            'split1' => ['type' => 'split', 'label' => 'Feature with image — grading', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Certification'),
                'title'        => sd_f('text', 'Title', 'Every report, independently issued'),
                'text'         => sd_f('textarea', 'Text', 'Our stones are graded by GIA, IGI or HRD. Each report number can be checked on the laboratory website before you buy.'),
                'image'        => sd_f('image', 'Image', $I . 'grading-report.webp'),
                'image_side'   => sd_f('select', 'Image position', 'left', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', ''),
                'button_url'   => sd_f('url', 'Button link', ''),
            ]],
            'split2' => ['type' => 'split', 'label' => 'Feature with image — fancy colours', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Beyond white'),
                'title'        => sd_f('text', 'Title', 'Fancy colour and natural brown diamonds'),
                'text'         => sd_f('textarea', 'Text', 'Natural colour is one of the rarest things in the trade. We keep a small, carefully chosen collection of yellows, pinks and browns.'),
                'image'        => sd_f('image', 'Image', $I . 'fancy-colours.webp'),
                'image_side'   => sd_f('select', 'Image position', 'right', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', 'See fancy colours in stock'),
                'button_url'   => sd_f('url', 'Button link', 'inventory'),
            ]],
            'credentials' => ['type' => 'credentials', 'label' => 'Laboratories', 'fields' => [
                'title' => sd_f('text', 'Title (optional)', 'Graded by'),
            ] + sd_items(5, ['name' => $txt('Name'), 'text' => $txt('Small line'), 'image' => $img('Logo (optional)')], [
                ['name' => 'GIA', 'text' => 'Gemological Institute of America'], ['name' => 'IGI', 'text' => 'International Gemological Institute'],
                ['name' => 'HRD', 'text' => 'HRD Antwerp'],
            ])],
            'cta' => ['type' => 'cta', 'label' => 'Inventory call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Our inventory is live'),
                'text'          => sd_f('textarea', 'Text', 'Sign in with your trade account to see prices, request memo and reserve stones.'),
                'image'         => sd_f('image', 'Image', $I . 'tweezers.webp'),
                'button1_label' => sd_f('text', 'Main button label', 'Browse inventory'),
                'button1_url'   => sd_f('url', 'Main button link', 'inventory'),
                'button2_label' => sd_f('text', 'Second button label', 'Request a trade account'),
                'button2_url'   => sd_f('url', 'Second button link', 'page:contact'),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'shapes', 'cards', 'split1', 'split2', 'credentials', 'cta'],
            'heritage' => ['hero', 'shapes', 'cards', 'split1', 'credentials', 'cta'],
            'noir'     => ['hero', 'cards', 'shapes', 'split1', 'split2', 'cta'],
        ],
    ],

    // ================================================== RESPONSIBLE PRACTICES
    'responsible' => [
        'label' => 'Responsible Practices',
        'file'  => 'responsible-practices.php',
        'title' => 'Responsible practices',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Our commitment'),
                'title'   => sd_f('text', 'Title', 'Responsible from mine to market'),
                'text'    => sd_f('textarea', 'Text', 'Confidence in a diamond starts with knowing where it came from and how it was handled.'),
                'image'   => sd_f('image', 'Banner image', $I . 'rough-crystal.webp'),
                'image_dark' => sd_f('image', 'Dark banner image (Noir template)', $I . 'rough-crystal.webp'),
            ]],
            'intro' => ['type' => 'intro', 'label' => 'Introduction', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Our approach'),
                'title'   => sd_f('text', 'Title', 'Why it matters to us'),
                'text'    => sd_f('textarea', 'Text', "We buy only from suppliers who share our standards, and we check. Every parcel we sell can be traced back through our records to its source.\n\nWe believe clients should never have to wonder about the stones they sell. So we publish our policies and make them easy to verify."),
                'image'   => sd_f('image', 'Image (optional)', ''),
            ]],
            'cards' => ['type' => 'cards', 'label' => 'Our practices', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Principles'),
                'title'   => sd_f('text', 'Title', 'The standards we work to'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(6, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text'), 'image' => $img('Image (optional)')], [
                ['icon' => 'shield', 'title' => 'Conflict-free sourcing', 'text' => 'All rough is bought in compliance with the Kimberley Process Certification Scheme and the World Diamond Council System of Warranties.'],
                ['icon' => 'people', 'title' => 'Fair working conditions', 'text' => 'Safe workplaces, fair pay and no child or forced labour — for us and for every supplier we work with.'],
                ['icon' => 'check', 'title' => 'Full disclosure', 'text' => 'Every stone is sold with its treatment status and origin clearly stated. We use screening devices on all melee.'],
            ])],
            'split1' => ['type' => 'split', 'label' => 'Feature with image — traceability', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Traceability'),
                'title'        => sd_f('text', 'Title', 'Every parcel has a history'),
                'text'         => sd_f('textarea', 'Text', 'From the rough purchase to the final invoice, we record where each stone has been. Ask for the chain of custody on any diamond we supply.'),
                'image'        => sd_f('image', 'Image', $I . 'diamond-studio.webp'),
                'image_side'   => sd_f('select', 'Image position', 'right', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', ''),
                'button_url'   => sd_f('url', 'Button link', ''),
            ]],
            'credentials' => ['type' => 'credentials', 'label' => 'Memberships & certifications', 'fields' => [
                'title' => sd_f('text', 'Title (optional)', 'Memberships and certifications'),
            ] + sd_items(5, ['name' => $txt('Name'), 'text' => $txt('Small line'), 'image' => $img('Logo (optional)')], [
                ['name' => 'Kimberley Process', 'text' => 'Compliant'], ['name' => 'RJC', 'text' => 'Code of Practices'],
                ['name' => 'WDC', 'text' => 'System of Warranties'], ['name' => 'AWDC', 'text' => 'Registered dealer'],
            ])],
            'faq' => ['type' => 'faq', 'label' => 'Questions', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', ''),
                'title'   => sd_f('text', 'Title', 'Questions about our policies'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(6, ['question' => $txt('Question'), 'answer' => $area('Answer')], [
                ['question' => 'Can I get a copy of your sourcing policy?', 'answer' => 'Yes. Ask through the contact form and we will send our current policy and certificates.'],
                ['question' => 'Do you sell treated or laboratory-grown diamonds?', 'answer' => 'Any treated or laboratory-grown stone is clearly disclosed on the invoice and report. We screen all goods before they are offered.'],
            ])],
            'cta' => ['type' => 'cta', 'label' => 'Call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Questions about a specific stone?'),
                'text'          => sd_f('textarea', 'Text', 'We will share its origin and handling record on request.'),
                'image'         => sd_f('image', 'Image', ''),
                'button1_label' => sd_f('text', 'Main button label', 'Contact us'),
                'button1_url'   => sd_f('url', 'Main button link', 'page:contact'),
                'button2_label' => sd_f('text', 'Second button label', ''),
                'button2_url'   => sd_f('url', 'Second button link', ''),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'intro', 'cards', 'split1', 'credentials', 'cta'],
            'heritage' => ['hero', 'intro', 'cards', 'split1', 'faq', 'credentials'],
            'noir'     => ['hero', 'intro', 'cards', 'split1', 'credentials', 'cta'],
        ],
    ],

    // ======================================================== SUSTAINABILITY
    'sustainability' => [
        'label' => 'Sustainability',
        'file'  => 'sustainability.php',
        'title' => 'Sustainability',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Sustainability'),
                'title'   => sd_f('text', 'Title', 'Made to last, made with care'),
                'text'    => sd_f('textarea', 'Text', 'A diamond lasts for generations. We want the way it was produced to stand up to that.'),
                'image'   => sd_f('image', 'Banner image', $I . 'light-caustic-green.webp'),
                'image_dark' => sd_f('image', 'Dark banner image (Noir template)', $I . 'light-caustic-warm.webp'),
            ]],
            'intro' => ['type' => 'intro', 'label' => 'Introduction', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Our footprint'),
                'title'   => sd_f('text', 'Title', 'Measured, reported, reduced'),
                'text'    => sd_f('textarea', 'Text', "We measure the energy, water and packaging used across our offices and workshop, and set targets to reduce each one every year.\n\nWe report progress openly, including the years we fall short."),
                'image'   => sd_f('image', 'Image (optional)', ''),
            ]],
            'stats' => ['type' => 'stats', 'label' => 'Key figures', 'fields' => sd_items(4,
                ['value' => $txt('Figure'), 'label' => $txt('Label')],
                [['value' => '100%', 'label' => 'Renewable electricity'], ['value' => '−38%', 'label' => 'Energy use since 2019'],
                 ['value' => '0', 'label' => 'Single-use plastic in shipping'], ['value' => '92%', 'label' => 'Polishing water recycled']])],
            'cards' => ['type' => 'cards', 'label' => 'Initiatives', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Initiatives'),
                'title'   => sd_f('text', 'Title', 'What we are doing'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(6, ['icon' => $icon(), 'title' => $txt('Title'), 'text' => $area('Text'), 'image' => $img('Image (optional)')], [
                ['icon' => 'sun', 'title' => 'Renewable energy', 'text' => 'Our offices and cutting workshop run on certified renewable electricity.'],
                ['icon' => 'drop', 'title' => 'Water recycling', 'text' => 'Water from polishing is filtered and reused in a closed loop.'],
                ['icon' => 'leaf', 'title' => 'Recyclable packaging', 'text' => 'Parcel papers, boxes and fillers are paper-based and recyclable.'],
                ['icon' => 'people', 'title' => 'Community support', 'text' => 'We fund training for young cutters in the communities where our rough is polished.'],
            ])],
            'split1' => ['type' => 'split', 'label' => 'Feature with image', 'fields' => [
                'eyebrow'      => sd_f('text', 'Small line above the title', 'Longevity'),
                'title'        => sd_f('text', 'Title', 'The most sustainable diamond is one that is kept'),
                'text'         => sd_f('textarea', 'Text', 'We offer re-polishing and re-certification of older stones, so they can be reset rather than replaced.'),
                'image'        => sd_f('image', 'Image', $I . 'diamond-top-pearl.webp'),
                'image_side'   => sd_f('select', 'Image position', 'left', SD_SIDE),
                'button_label' => sd_f('text', 'Button label', 'Ask about re-polishing'),
                'button_url'   => sd_f('url', 'Button link', 'page:contact'),
            ]],
            'cta' => ['type' => 'cta', 'label' => 'Call to action', 'fields' => [
                'title'         => sd_f('text', 'Title', 'Read our latest sustainability report'),
                'text'          => sd_f('textarea', 'Text', 'Ask us for the full report, including our targets for next year.'),
                'image'         => sd_f('image', 'Image', ''),
                'button1_label' => sd_f('text', 'Main button label', 'Request the report'),
                'button1_url'   => sd_f('url', 'Main button link', 'page:contact'),
                'button2_label' => sd_f('text', 'Second button label', ''),
                'button2_url'   => sd_f('url', 'Second button link', ''),
            ]],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'intro', 'stats', 'cards', 'split1', 'cta'],
            'heritage' => ['hero', 'intro', 'cards', 'stats', 'split1', 'cta'],
            'noir'     => ['hero', 'stats', 'intro', 'cards', 'split1', 'cta'],
        ],
    ],

    // ============================================================== CONTACT
    'contact' => [
        'label' => 'Contact Us',
        'file'  => 'contact.php',
        'title' => 'Contact us',
        'blocks' => [
            'hero' => ['type' => 'hero', 'label' => 'Page banner', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Get in touch'),
                'title'   => sd_f('text', 'Title', 'Contact us'),
                'text'    => sd_f('textarea', 'Text', 'Open a trade account, request stones on memo, or book a visit to our office.'),
                'image'   => sd_f('image', 'Banner image', ''),
                'image_dark' => sd_f('image', 'Dark banner image (Noir template)', $I . 'hero-field.webp'),
            ]],
            'contact' => ['type' => 'contact', 'label' => 'Contact details & form', 'fields' => [
                'title'      => sd_f('text', 'Title', 'Send us a message'),
                'text'       => sd_f('textarea', 'Text', 'We reply to every enquiry within one working day.'),
                'show_form'  => sd_f('select', 'Show the enquiry form', 'yes', SD_YESNO),
                'show_hours' => sd_f('select', 'Show business hours', 'yes', SD_YESNO),
                'map_url'    => sd_f('url', 'Directions link (e.g. a Google Maps link)', ''),
            ]],
            'offices' => ['type' => 'offices', 'label' => 'Offices', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', 'Offices'),
                'title'   => sd_f('text', 'Title', 'Where to find us'),
            ] + sd_items(4, ['name' => $txt('Office name'), 'address' => $area('Address'), 'phone' => $txt('Phone'), 'email' => $txt('Email')], [
                ['name' => 'Head office', 'address' => '{address}', 'phone' => '{phone}', 'email' => '{email}'],
            ])],
            'faq' => ['type' => 'faq', 'label' => 'Questions', 'fields' => [
                'eyebrow' => sd_f('text', 'Small line above the title', ''),
                'title'   => sd_f('text', 'Title', 'Before you write'),
                'text'    => sd_f('textarea', 'Text', ''),
            ] + sd_items(6, ['question' => $txt('Question'), 'answer' => $area('Answer')], [
                ['question' => 'Do you sell to private customers?', 'answer' => 'We work with the jewellery trade. If you are a private buyer, we are happy to recommend a jeweller near you.'],
                ['question' => 'Can I visit without an appointment?', 'answer' => 'For security reasons, visits are by appointment only. Use the form and we will confirm a time.'],
            ])],
        ],
        'layouts' => [
            'atelier'  => ['hero', 'contact', 'offices'],
            'heritage' => ['hero', 'contact', 'offices', 'faq'],
            'noir'     => ['hero', 'offices', 'contact'],
        ],
    ],
];
