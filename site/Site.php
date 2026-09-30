<?php
/**
 * Site.php
 * Engine for the public website (Home, About, Services, Diamonds,
 * Responsible Practices, Sustainability, Contact Us).
 *
 *   Templates  site_templates  -> which of the 3 designs is live
 *   Layouts    site_blocks     -> which sections each template shows per page
 *   Content    site_content    -> every text / image / link (falls back to
 *                                 site/content_defaults.php)
 *   Colours    theme_settings  -> sections "Website - <template>" (Admin -> Theme Settings)
 *
 * Every lookup degrades gracefully: if the migration has not been run yet the
 * website still renders with the Atelier template and the default content.
 * Nothing in this file touches the inventory tables.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ThemeSettings.php';

final class Site
{
    /**
     * Style families: each one ships a header, footer, hero and a version of
     * every section type in code (site/templates/<family>/ + assets/site/css/<family>.css).
     * A TEMPLATE (table site_templates) picks one family per part and has its
     * own colour/font palette, so admins can create new templates by mixing
     * parts without writing code.
     */
    public const FAMILIES = [
        'atelier' => [
            'name'  => 'Atelier',
            'about' => 'Light and editorial. Centred logo, full-width photography, fine serif headings and a black contact band.',
            'header' => 'Centred logo with the menu underneath',
            'hero'   => 'Full-width photograph with the title over it',
            'footer' => 'Centred black band',
        ],
        'heritage' => [
            'name'  => 'Heritage',
            'about' => 'Clear and trustworthy. Sticky menu with a button, brand-colour feature band, step-by-step timeline, reviews and FAQs.',
            'header' => 'Logo on the left, sticky menu and button',
            'hero'   => 'Title beside a cut-out diamond image',
            'footer' => 'Four columns on the brand colour',
        ],
        'noir' => [
            'name'  => 'Noir',
            'about' => 'Dark and dramatic. Top contact bar, full-screen hero with key figures, gold detailing and office cards.',
            'header' => 'Contact bar on top, menu over the hero',
            'hero'   => 'Full-screen image with centred title',
            'footer' => 'Dark footer in columns',
        ],
    ];

    /** Section types whose style can be chosen per template */
    public const SECTION_TYPES = [
        'intro' => 'Introduction', 'split' => 'Feature with image', 'features' => 'Feature list',
        'cards' => 'Cards', 'steps' => 'Steps', 'stats' => 'Key figures', 'shapes' => 'Shapes',
        'testimonials' => 'Reviews', 'faq' => 'FAQ', 'credentials' => 'Memberships',
        'cta' => 'Call to action', 'contact' => 'Contact form', 'offices' => 'Offices',
    ];

    /** Built-in templates: key => [settings prefix]. Their parts all use their own family. */
    public const BUILTIN = ['atelier' => 'at', 'heritage' => 'he', 'noir' => 'no'];
    public const DEFAULT_TEMPLATE = 'atelier';

    /**
     * Palette tokens every template has (theme_settings key = <prefix>-<token>).
     * token => [label, property_type, description]
     */
    public const TOKENS = [
        'bg'             => ['Page background', 'color', null],
        'surface'        => ['Alternate section background', 'color', 'Bands between plain sections'],
        'card'           => ['Card background', 'color', 'Cards, form panels, office boxes'],
        'ink'            => ['Main text', 'color', null],
        'muted'          => ['Secondary text', 'color', null],
        'accent'         => ['Accent', 'color', 'Labels, icons, links, small details'],
        'on-accent'      => ['Text on accent', 'color', null],
        'line'           => ['Borders and hairlines', 'color', null],
        'brand'          => ['Brand band background', 'color', 'Feature band, reviews band, brand footer, main buttons'],
        'on-brand'       => ['Text on brand band', 'color', null],
        'dark'           => ['Dark band background', 'color', 'Contact band and dark footers'],
        'on-dark'        => ['Text on dark band', 'color', null],
        'topbar-bg'      => ['Top contact bar background', 'color', 'Used by the Noir header'],
        'heading-font'   => ['Heading font', 'font_family', null],
        'body-font'      => ['Body font', 'font_family', null],
        'heading-weight' => ['Heading weight', 'font_weight', null],
        'base-size'      => ['Base text size', 'font_size', null],
        'btn-radius'     => ['Button corner radius', 'size', '999px gives pill-shaped buttons'],
        'radius'         => ['Card corner radius', 'size', null],
        'section-space'  => ['Space between sections', 'size', 'Shrinks automatically on phones'],
    ];

    public const PALETTES = [
        'atelier' => [
            'bg' => '#ffffff', 'surface' => '#f2f1ee', 'card' => '#ffffff', 'ink' => '#1d1c1a', 'muted' => '#6b6861',
            'accent' => '#a8895a', 'on-accent' => '#ffffff', 'line' => '#e2dfd8', 'brand' => '#1d1c1a', 'on-brand' => '#f4f2ed',
            'dark' => '#000000', 'on-dark' => '#f4f2ed', 'topbar-bg' => '#1d1c1a',
            'heading-font' => "'Cormorant Garamond', serif", 'body-font' => "'Jost', sans-serif",
            'heading-weight' => '400', 'base-size' => '16px', 'btn-radius' => '0', 'radius' => '0', 'section-space' => '112px',
        ],
        'heritage' => [
            'bg' => '#ffffff', 'surface' => '#edf0f1', 'card' => '#ffffff', 'ink' => '#132b33', 'muted' => '#52656b',
            'accent' => '#c9a45c', 'on-accent' => '#132b33', 'line' => '#d6dcde', 'brand' => '#0b3a47', 'on-brand' => '#ffffff',
            'dark' => '#0b3a47', 'on-dark' => '#ffffff', 'topbar-bg' => '#082c36',
            'heading-font' => "'Playfair Display', serif", 'body-font' => "'Mulish', sans-serif",
            'heading-weight' => '600', 'base-size' => '16px', 'btn-radius' => '999px', 'radius' => '6px', 'section-space' => '96px',
        ],
        'noir' => [
            'bg' => '#0d0d0c', 'surface' => '#141412', 'card' => '#1b1a17', 'ink' => '#ece8df', 'muted' => '#a29d92',
            'accent' => '#c6a15b', 'on-accent' => '#14120e', 'line' => 'rgba(255, 255, 255, 0.10)', 'brand' => '#1b1a17', 'on-brand' => '#ece8df',
            'dark' => '#070706', 'on-dark' => '#ece8df', 'topbar-bg' => '#070706',
            'heading-font' => "'Libre Baskerville', serif", 'body-font' => "'Poppins', sans-serif",
            'heading-weight' => '400', 'base-size' => '15px', 'btn-radius' => '2px', 'radius' => '2px', 'section-space' => '104px',
        ],
    ];

    /** Field types the content editor understands */
    public const FIELD_TYPES = ['text', 'textarea', 'image', 'url', 'select'];

    private static ?array $defaults = null;
    private static ?array $content = null;
    private static ?array $dbTemplates = null;
    private static ?array $setup = null;

    // ------------------------------------------------------------ defaults

    public static function defaults(): array
    {
        return self::$defaults ??= require __DIR__ . '/content_defaults.php';
    }

    /** page_key => definition, excluding the site-wide "global" group */
    public static function pages(): array
    {
        return array_filter(self::defaults(), fn ($p, $k) => $k !== 'global', ARRAY_FILTER_USE_BOTH);
    }

    public static function pageDef(string $page): ?array
    {
        return self::defaults()[$page] ?? null;
    }

    public static function blockDef(string $page, string $block): ?array
    {
        return self::defaults()[$page]['blocks'][$block] ?? null;
    }

    // ----------------------------------------------------------- templates

    /**
     * Every template, built-in and admin-created, keyed by template_key:
     * [key, id, name, description, builtin, active, prefix, base, parts, thumbnail]
     */
    public static function templates(): array
    {
        if (self::$dbTemplates !== null) {
            return self::$dbTemplates;
        }
        $rows = [];
        try {
            foreach (get_db()->query('SELECT * FROM site_templates ORDER BY sort_order, id')->fetchAll() as $r) {
                $rows[$r['template_key']] = $r;
            }
        } catch (Throwable $e) {
            $rows = [];
        }
        $out = [];
        foreach (self::BUILTIN as $key => $prefix) {
            $r = $rows[$key] ?? [];
            $out[$key] = [
                'key' => $key, 'id' => (int) ($r['id'] ?? 0),
                'name' => (string) ($r['name'] ?? self::FAMILIES[$key]['name']),
                'description' => (string) ($r['description'] ?? self::FAMILIES[$key]['about']),
                'builtin' => true, 'active' => (int) ($r['is_active'] ?? ($key === self::DEFAULT_TEMPLATE && !$rows ? 1 : 0)) === 1,
                'prefix' => $prefix, 'base' => $key, 'parts' => self::familyParts($key),
                'thumbnail' => '/assets/site/img/templates/' . $key . '.webp',
                'sort' => (int) ($r['sort_order'] ?? 0),
            ];
        }
        foreach ($rows as $key => $r) {
            if (isset(self::BUILTIN[$key]) || !preg_match('/^t\d+$/', $key)) {
                continue;
            }
            $base = isset(self::BUILTIN[$r['base_template'] ?? '']) ? $r['base_template'] : self::DEFAULT_TEMPLATE;
            $thumb = (string) ($r['thumbnail'] ?? '');
            $out[$key] = [
                'key' => $key, 'id' => (int) $r['id'], 'name' => (string) $r['name'],
                'description' => (string) ($r['description'] ?? ''), 'builtin' => false,
                'active' => (int) $r['is_active'] === 1, 'prefix' => (string) $r['settings_prefix'],
                'base' => $base, 'parts' => self::normaliseParts(json_decode((string) ($r['parts'] ?? ''), true), $base),
                'thumbnail' => self::isValidImage($thumb) && $thumb !== '' ? $thumb : '/assets/site/img/templates/' . $base . '.webp',
                'sort' => (int) $r['sort_order'],
            ];
        }
        return self::$dbTemplates = $out;
    }

    public static function get(string $key): ?array
    {
        return self::templates()[$key] ?? null;
    }

    public static function isTemplate(string $key): bool
    {
        return isset(self::templates()[$key]);
    }

    /** Every part of a family's own design */
    public static function familyParts(string $family): array
    {
        return ['header' => $family, 'hero' => $family, 'footer' => $family,
                'sections' => array_fill_keys(array_keys(self::SECTION_TYPES), $family)];
    }

    /** Clean a parts array so only known families are used */
    public static function normaliseParts(mixed $parts, string $fallback = self::DEFAULT_TEMPLATE): array
    {
        $parts = is_array($parts) ? $parts : [];
        $ok = fn ($v) => is_string($v) && isset(self::FAMILIES[$v]) ? $v : $fallback;
        $out = ['header' => $ok($parts['header'] ?? null), 'hero' => $ok($parts['hero'] ?? null),
                'footer' => $ok($parts['footer'] ?? null), 'sections' => []];
        foreach (array_keys(self::SECTION_TYPES) as $t) {
            $out['sections'][$t] = $ok($parts['sections'][$t] ?? null);
        }
        return $out;
    }

    /** Family used for a part: 'header' | 'footer' | 'hero' | a section type */
    public static function family(string $template, string $part): string
    {
        $parts = self::get($template)['parts'] ?? self::familyParts(self::DEFAULT_TEMPLATE);
        return $parts[$part] ?? ($parts['sections'][$part] ?? self::DEFAULT_TEMPLATE);
    }

    /** Families whose stylesheet the page needs */
    public static function familiesUsed(string $template): array
    {
        $p = self::get($template)['parts'] ?? self::familyParts(self::DEFAULT_TEMPLATE);
        return array_values(array_unique(array_merge([$p['header'], $p['hero'], $p['footer']], array_values($p['sections']))));
    }

    /** The template saved as live in the admin (ignores admin preview) */
    public static function liveTemplate(): string
    {
        foreach (self::templates() as $key => $t) {
            if ($t['active']) {
                return $key;
            }
        }
        return self::DEFAULT_TEMPLATE;
    }

    /** Admins (level 8+) can preview another template without publishing it */
    public static function canPreview(): bool
    {
        return function_exists('current_user') && (int) (current_user()['level'] ?? 0) >= 8;
    }

    public static function previewTemplate(): ?string
    {
        $p = $_SESSION['site_preview_template'] ?? null;
        return (is_string($p) && self::isTemplate($p) && self::canPreview()) ? $p : null;
    }

    /** Template used for this request */
    public static function template(): string
    {
        return self::previewTemplate() ?? self::liveTemplate();
    }

    public static function forget(): void
    {
        self::$dbTemplates = null;
    }

    /** Make $key the live template */
    public static function activate(string $key): bool
    {
        if (!self::isTemplate($key)) {
            return false;
        }
        self::ensureBuiltins();
        $db = get_db();
        $db->beginTransaction();
        try {
            $db->exec('UPDATE site_templates SET is_active = 0');
            $db->prepare('UPDATE site_templates SET is_active = 1 WHERE template_key = ?')->execute([$key]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        self::forget();
        return true;
    }

    /** Make sure the 3 built-in templates and their palettes exist in the database */
    public static function ensureBuiltins(): void
    {
        $db = get_db();
        $ins = $db->prepare('INSERT IGNORE INTO site_templates (template_key, name, description, is_builtin, is_active, settings_prefix, base_template, sort_order)
                             VALUES (?,?,?,1,?,?,?,?)');
        $hasActive = (int) $db->query('SELECT COUNT(*) FROM site_templates WHERE is_active = 1')->fetchColumn() > 0;
        $i = 0;
        foreach (self::BUILTIN as $key => $prefix) {
            $ins->execute([$key, self::FAMILIES[$key]['name'], self::FAMILIES[$key]['about'],
                           (!$hasActive && $key === self::DEFAULT_TEMPLATE) ? 1 : 0, $prefix, $key, ++$i]);
            self::ensurePalette($prefix, self::FAMILIES[$key]['name'], self::PALETTES[$key], 'site_' . $key, 20 + $i);
        }
        self::forget();
    }

    /** Create the Theme Settings section + one setting per token (missing ones only). Returns section id. */
    public static function ensurePalette(string $prefix, string $name, array $values, string $sectionKey, int $sort): int
    {
        $db = get_db();
        $db->prepare('INSERT IGNORE INTO theme_sections (section_key, name, description, sort_order) VALUES (?,?,?,?)')
           ->execute([$sectionKey, 'Website - ' . mb_substr($name, 0, 60) . ' template',
                      'Colours and fonts of the public website when the ' . mb_substr($name, 0, 60) . ' template is used', $sort]);
        $st = $db->prepare('SELECT id FROM theme_sections WHERE section_key = ?');
        $st->execute([$sectionKey]);
        $sid = (int) $st->fetchColumn();
        $ins = $db->prepare('INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order)
                             VALUES (?,?,?,?,?,?,?,?)');
        $n = 0;
        foreach (self::TOKENS as $token => [$label, $type, $desc]) {
            $v = (string) ($values[$token] ?? self::PALETTES[self::DEFAULT_TEMPLATE][$token]);
            if (!ThemeSettings::isValidValue($type, $v)) {
                $v = self::PALETTES[self::DEFAULT_TEMPLATE][$token];
            }
            $ins->execute([$sid, $prefix . '-' . $token, $label, $type, $v, $v, $desc, ++$n]);
        }
        return $sid;
    }

    /** Current palette values of a template (from theme_settings, falling back to its base) */
    public static function paletteValues(string $template): array
    {
        $t = self::get($template);
        $base = self::PALETTES[$t['base'] ?? self::DEFAULT_TEMPLATE];
        $out = [];
        foreach (self::TOKENS as $token => [, $type]) {
            $out[$token] = ThemeSettings::value(($t['prefix'] ?? 'at') . '-' . $token, $base[$token]) ?? $base[$token];
        }
        return $out;
    }

    /** Theme Settings section id of a template's palette */
    public static function paletteSectionId(string $template): ?int
    {
        $t = self::get($template);
        if (!$t) {
            return null;
        }
        $st = get_db()->prepare('SELECT section_id FROM theme_settings WHERE setting_key = ? LIMIT 1');
        $st->execute([$t['prefix'] . '-bg']);
        $id = $st->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /**
     * New template copied from $sourceKey: same parts, same palette values,
     * same section arrangement on every page. Returns the new key (t<id>).
     */
    public static function duplicate(string $sourceKey, string $name, string $description = ''): string
    {
        $src = self::get($sourceKey);
        if (!$src) {
            throw new InvalidArgumentException('Unknown template.');
        }
        self::ensureBuiltins();
        $db = get_db();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO site_templates (template_key, name, description, is_builtin, is_active, settings_prefix, base_template, parts, sort_order)
                          VALUES (?,?,?,0,0,?,?,?,?)')
               ->execute(['tmp-' . bin2hex(random_bytes(6)), $name, $description ?: null, 'tmp', $src['base'],
                          json_encode($src['parts']), 100]);
            $id = (int) $db->lastInsertId();
            $key = 't' . $id;
            $db->prepare('UPDATE site_templates SET template_key = ?, settings_prefix = ?, sort_order = ? WHERE id = ?')
               ->execute([$key, $key, 100 + $id, $id]);

            self::ensurePalette($key, $name, self::paletteValues($sourceKey), 'site_' . $key, 30 + $id);

            $ins = $db->prepare('INSERT INTO site_blocks (template_key, page_key, block_key, is_enabled, sort_order) VALUES (?,?,?,?,?)');
            foreach (array_keys(self::pages()) as $page) {
                foreach (self::layout($sourceKey, $page, true) as $row) {
                    $ins->execute([$key, $page, $row['key'], $row['enabled'] ? 1 : 0, $row['sort']]);
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        self::forget();
        return $key;
    }

    /** Save name / description / parts / thumbnail of an admin-created template */
    public static function saveCustom(string $key, string $name, string $description, array $parts, ?string $thumbnail): void
    {
        $t = self::get($key);
        if (!$t || $t['builtin']) {
            throw new InvalidArgumentException('Only templates you created can be changed here.');
        }
        get_db()->prepare('UPDATE site_templates SET name = ?, description = ?, parts = ?, thumbnail = ? WHERE template_key = ?')
            ->execute([$name, $description ?: null, json_encode(self::normaliseParts($parts, $t['base'])),
                       ($thumbnail !== null && $thumbnail !== '' && self::isValidImage($thumbnail)) ? $thumbnail : null, $key]);
        get_db()->prepare('UPDATE theme_sections s JOIN theme_settings t ON t.section_id = s.id AND t.setting_key = ?
                              SET s.name = ? ')
            ->execute([$t['prefix'] . '-bg', 'Website - ' . mb_substr($name, 0, 60) . ' template']);
        self::forget();
    }

    /** Delete an admin-created template (not the live one) with its palette and layouts */
    public static function deleteCustom(string $key): void
    {
        $t = self::get($key);
        if (!$t || $t['builtin']) {
            throw new InvalidArgumentException('Built-in templates cannot be deleted.');
        }
        if ($t['active']) {
            throw new InvalidArgumentException('This template is live. Make another template live first.');
        }
        $db = get_db();
        $sid = self::paletteSectionId($key);
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM site_blocks WHERE template_key = ?')->execute([$key]);
            $db->prepare('DELETE FROM theme_settings WHERE setting_key LIKE ?')->execute([$t['prefix'] . '-%']);
            if ($sid) {
                $db->prepare('DELETE FROM theme_sections WHERE id = ? AND NOT EXISTS (SELECT 1 FROM theme_settings WHERE section_id = ?)')
                   ->execute([$sid, $sid]);
            }
            $db->prepare('DELETE FROM site_templates WHERE template_key = ? AND is_builtin = 0')->execute([$key]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        if (!empty($_SESSION['site_preview_template']) && $_SESSION['site_preview_template'] === $key) {
            unset($_SESSION['site_preview_template']);
        }
        self::forget();
    }

    /** Inline CSS mapping the generic design tokens onto this template's palette */
    public static function tokenCss(string $template): string
    {
        $t = self::get($template);
        $prefix = $t['prefix'] ?? 'at';
        $base = self::PALETTES[$t['base'] ?? self::DEFAULT_TEMPLATE];
        $css = 'body.site{';
        foreach (self::TOKENS as $token => [, $type]) {
            $fallback = $base[$token];
            $css .= '--' . $token . ':var(--' . $prefix . '-' . $token . ',' . $fallback . ');';
        }
        $scheme = self::isDarkColor(self::paletteValues($template)['bg']) ? 'dark' : 'light';
        return $css . 'color-scheme:' . $scheme . ';}';
    }

    /** Rough luminance test for #rrggbb / #rgb / rgb() colours */
    public static function isDarkColor(string $c): bool
    {
        $c = trim($c);
        if (preg_match('/^#([0-9a-f]{3})$/i', $c, $m)) {
            $c = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $c, $m)) {
            [$r, $g, $b] = [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])];
        } elseif (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $c, $m)) {
            [$r, $g, $b] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return false;
        }
        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) < 110;
    }

    // ------------------------------------------------------------- layouts

    /**
     * Ordered list of block keys shown on $page for $template.
     * Uses the admin's saved arrangement (site_blocks) when there is one,
     * otherwise the template's default layout from content_defaults.php.
     * @return array<int, array{key:string, enabled:bool}>
     */
    public static function layout(string $template, string $page, bool $includeDisabled = false): array
    {
        $def = self::pageDef($page);
        if (!$def) {
            return [];
        }
        $base = self::get($template)['base'] ?? $template;
        $defaultOrder = $def['layouts'][$template] ?? $def['layouts'][$base] ?? array_keys($def['blocks']);
        $saved = [];
        try {
            $st = get_db()->prepare('SELECT block_key, is_enabled, sort_order FROM site_blocks WHERE template_key = ? AND page_key = ?');
            $st->execute([$template, $page]);
            foreach ($st->fetchAll() as $r) {
                $saved[$r['block_key']] = ['enabled' => (int) $r['is_enabled'] === 1, 'sort' => (int) $r['sort_order']];
            }
        } catch (Throwable $e) {
            $saved = [];
        }

        $rows = [];
        $i = 0;
        foreach (array_keys($def['blocks']) as $blockKey) {
            $pos = array_search($blockKey, $defaultOrder, true);
            $enabled = $pos !== false;
            $sort = $pos !== false ? ($pos + 1) * 10 : 1000 + (++$i);
            if (isset($saved[$blockKey])) {
                $enabled = $saved[$blockKey]['enabled'];
                $sort = $saved[$blockKey]['sort'];
            }
            if ($enabled || $includeDisabled) {
                $rows[] = ['key' => $blockKey, 'enabled' => $enabled, 'sort' => $sort];
            }
        }
        usort($rows, fn ($a, $b) => $a['sort'] <=> $b['sort']);
        return $rows;
    }

    // ------------------------------------------------------------- content

    private static function contentMap(): array
    {
        if (self::$content !== null) {
            return self::$content;
        }
        self::$content = [];
        try {
            $rows = get_db()->query('SELECT page_key, block_key, field_key, content_value FROM site_content')->fetchAll();
            foreach ($rows as $r) {
                if ($r['content_value'] !== null) {
                    self::$content[$r['page_key']][$r['block_key']][$r['field_key']] = (string) $r['content_value'];
                }
            }
        } catch (Throwable $e) {
            // table not created yet -> defaults only
        }
        return self::$content;
    }

    /** Raw stored value (or default) of one field, placeholders NOT replaced */
    public static function raw(string $page, string $block, string $field): string
    {
        $map = self::contentMap();
        if (isset($map[$page][$block][$field])) {
            return $map[$page][$block][$field];
        }
        return (string) (self::blockDef($page, $block)['fields'][$field]['default'] ?? '');
    }

    /** Value with {company}, {year}... replaced — still plain text, escape on output */
    public static function text(string $page, string $block, string $field): string
    {
        return self::fill(self::raw($page, $block, $field));
    }

    public static function global(string $block, string $field): string
    {
        return self::text('global', $block, $field);
    }

    public static function setup(): array
    {
        return self::$setup ??= (function () {
            try {
                return get_setup() ?? [];
            } catch (Throwable $e) {
                return [];
            }
        })();
    }

    public static function company(): string
    {
        return (string) (self::setup()['company'] ?? APP_NAME);
    }

    public static function fill(string $s): string
    {
        if (!str_contains($s, '{')) {
            return $s;
        }
        $setup = self::setup();
        return strtr($s, [
            '{company}' => self::company(),
            '{year}'    => date('Y'),
            '{phone}'   => primary_phone($setup),
            '{email}'   => primary_email($setup),
            '{address}' => format_address($setup),
        ]);
    }

    /** Insert a site_content row for every field in the defaults that has none yet */
    public static function syncContent(): int
    {
        $st = get_db()->prepare(
            'INSERT IGNORE INTO site_content (page_key, block_key, field_key, field_type, label, content_value, default_value, sort_order)
             VALUES (?,?,?,?,?,NULL,?,?)'
        );
        $n = 0;
        foreach (self::defaults() as $pageKey => $page) {
            $order = 0;
            foreach ($page['blocks'] as $blockKey => $block) {
                foreach ($block['fields'] as $fieldKey => $f) {
                    $st->execute([$pageKey, $blockKey, $fieldKey, $f['type'], mb_substr($f['label'], 0, 120), $f['default'], ++$order]);
                    $n += $st->rowCount();
                }
            }
        }
        self::$content = null;
        return $n;
    }

    // ------------------------------------------------------ links & images

    /** URL of a page by key (home, about, ...) */
    public static function pageUrl(string $page): string
    {
        $file = self::pageDef($page)['file'] ?? 'index.php';
        return asset_url('/' . $file);
    }

    /** Resolve a link field to a safe href ('' when empty or not allowed) */
    public static function url(string $value): string
    {
        $v = trim(self::fill($value));
        if ($v === '') {
            return '';
        }
        if ($v === 'inventory') {
            return asset_url('/inventory.php');
        }
        if (str_starts_with($v, 'page:')) {
            $page = substr($v, 5);
            return self::pageDef($page) && $page !== 'global' ? self::pageUrl($page) : '';
        }
        if (preg_match('#^(https?://|mailto:|tel:)#i', $v)) {
            return $v;
        }
        if ($v[0] === '#') {
            return $v;
        }
        if ($v[0] === '/' && !str_starts_with($v, '//')) {
            return asset_url($v);
        }
        return '';
    }

    /** true when a link field may be saved */
    public static function isValidUrl(string $v): bool
    {
        $v = trim($v);
        return $v === '' || self::url($v) !== '';
    }

    /** Only images inside this site's asset folders can be used (the CSP blocks remote images) */
    public static function isValidImage(string $v): bool
    {
        $v = trim($v);
        return $v === '' || (bool) preg_match('#^/assets/(site/(img|uploads)|img)/[A-Za-z0-9._/-]+\.(webp|png|jpe?g|gif|svg)$#', $v)
            && !str_contains($v, '..');
    }

    public static function imageUrl(string $value): string
    {
        $v = trim($value);
        return ($v !== '' && self::isValidImage($v)) ? asset_url($v) : '';
    }

    /** Logo URL for light or dark backgrounds ('' when none is set) */
    public static function logoUrl(bool $onDark = false): string
    {
        if ($onDark) {
            $dark = self::imageUrl(self::raw('global', 'brand', 'logo_dark'));
            if ($dark !== '') {
                return $dark;
            }
            return '';
        }
        try {
            $path = get_logo_path();
        } catch (Throwable $e) {
            return '';
        }
        return preg_match('#^https?://#i', $path) ? $path : asset_url($path);
    }

    /** Menu items: [page_key, label, url, is_current] — pages with an empty label are hidden */
    public static function nav(string $current): array
    {
        $items = [];
        foreach (array_keys(self::pages()) as $page) {
            $label = trim(self::global('nav', $page));
            if ($label !== '') {
                $items[] = ['key' => $page, 'label' => $label, 'url' => self::pageUrl($page), 'current' => $page === $current];
            }
        }
        return $items;
    }

    // ------------------------------------------------------------ helpers

    /** Plain text -> escaped paragraphs; single newlines become <br> */
    public static function paragraphs(string $text, string $class = ''): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $out = '';
        foreach (preg_split("/\R{2,}/", $text) as $p) {
            $out .= '<p' . ($class !== '' ? ' class="' . e($class) . '"' : '') . '>' . nl2br(e(trim($p)), false) . '</p>';
        }
        return $out;
    }

    /** Asset URL with file-modified cache busting */
    public static function asset(string $path): string
    {
        return asset_url_versioned($path);
    }
}

/**
 * Read-only view of one section's content, passed to block partials as $b.
 */
final class SiteBlock
{
    public function __construct(
        public readonly string $page,
        public readonly string $key,
        public readonly string $type,
        public readonly int $index,
    ) {}

    public function t(string $field): string
    {
        return Site::text($this->page, $this->key, $field);
    }

    /** Escaped text */
    public function e(string $field): string
    {
        return e($this->t($field));
    }

    public function has(string $field): bool
    {
        return trim($this->t($field)) !== '';
    }

    public function p(string $field, string $class = ''): string
    {
        return Site::paragraphs($this->t($field), $class);
    }

    public function url(string $field): string
    {
        return Site::url(Site::raw($this->page, $this->key, $field));
    }

    public function img(string $field): string
    {
        return Site::imageUrl(Site::raw($this->page, $this->key, $field));
    }

    /** First non-empty image among $fields */
    public function imgFirst(string ...$fields): string
    {
        foreach ($fields as $f) {
            if (($u = $this->img($f)) !== '') {
                return $u;
            }
        }
        return '';
    }

    /**
     * Repeated items (item1_title, item2_title...). An item is included only
     * when its first field ($required, default: first field defined) has text.
     * @return array<int, array<string,string>>
     */
    public function items(?string $required = null): array
    {
        $fields = Site::blockDef($this->page, $this->key)['fields'] ?? [];
        $items = [];
        foreach ($fields as $name => $def) {
            if (preg_match('/^item(\d+)_(\w+)$/', $name, $m)) {
                $items[(int) $m[1]][$m[2]] = $def['type'];
            }
        }
        $out = [];
        foreach ($items as $n => $spec) {
            $required ??= array_key_first($spec);
            $row = ['n' => (string) $n];
            foreach ($spec as $f => $type) {
                $raw = Site::raw($this->page, $this->key, "item{$n}_{$f}");
                $row[$f] = match ($type) {
                    'image' => Site::imageUrl($raw),
                    'url'   => Site::url($raw),
                    default => Site::fill($raw),
                };
            }
            if (trim($row[$required] ?? '') !== '') {
                $out[] = $row;
            }
        }
        return $out;
    }

    /** Buttons: list of [label, url, primary] for fields buttonN_label/_url or button_label/_url */
    public function buttons(): array
    {
        $out = [];
        foreach ([['button1_label', 'button1_url'], ['button2_label', 'button2_url'], ['button_label', 'button_url']] as $i => [$l, $u]) {
            $fields = Site::blockDef($this->page, $this->key)['fields'] ?? [];
            if (!isset($fields[$l])) {
                continue;
            }
            $label = trim($this->t($l));
            $href = $this->url($u);
            if ($label !== '' && $href !== '') {
                $out[] = ['label' => $label, 'url' => $href, 'primary' => $out === []];
            }
        }
        return $out;
    }

    /** Heading id for aria-labelledby */
    public function id(string $suffix = 'title'): string
    {
        return 'b-' . $this->key . '-' . $suffix;
    }
}
