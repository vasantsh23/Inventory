<?php
/**
 * ThemeSettings.php
 * Data access + validation for the theme_settings / theme_sections tables.
 * Every value is validated against its property type before it is saved and
 * again before it is written into CSS, so nothing unsafe reaches the page.
 *
 * One row in theme_settings = one CSS custom property:
 *   setting_key "header-bg" + value "#fff"  ->  --header-bg: #fff;
 * served by /theme.css.php and used by assets/css/style.css as var(--header-bg).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/fonts.php';

final class ThemeSettings
{
    public const TYPES = [
        'color'          => 'Colour',
        'font_family'    => 'Font family',
        'font_size'      => 'Font size',
        'font_weight'    => 'Font weight',
        'font_style'     => 'Font style',
        'text_transform' => 'Text case',
        'size'           => 'Size / spacing',
        'number'         => 'Number (e.g. line height)',
        'color_scheme'   => 'Native control scheme',
    ];

    public const FONT_WEIGHTS    = ['300' => 'Light 300', '400' => 'Regular 400', '500' => 'Medium 500',
                                    '600' => 'Semi-bold 600', '700' => 'Bold 700', '800' => 'Extra-bold 800'];
    public const FONT_STYLES     = ['normal' => 'Normal', 'italic' => 'Italic'];
    public const TEXT_TRANSFORMS = ['none' => 'As typed', 'uppercase' => 'UPPERCASE',
                                    'lowercase' => 'lowercase', 'capitalize' => 'Capitalise Each Word'];
    public const COLOR_SCHEMES   = ['dark' => 'Dark (for dark themes)', 'light' => 'Light (for light themes)',
                                    'light dark' => 'Follow the device setting'];

    // ---------------------------------------------------------------- read

    public static function sections(): array
    {
        return get_db()->query(
            'SELECT s.*, COUNT(t.id) AS setting_count
               FROM theme_sections s
          LEFT JOIN theme_settings t ON t.section_id = s.id
           GROUP BY s.id ORDER BY s.sort_order, s.name'
        )->fetchAll();
    }

    public static function all(?int $sectionId = null, string $search = '', string $type = ''): array
    {
        $sql = 'SELECT t.*, s.name AS section_name, s.section_key
                  FROM theme_settings t
                  JOIN theme_sections s ON s.id = t.section_id
                 WHERE 1=1';
        $args = [];
        if ($sectionId) {
            $sql .= ' AND t.section_id = ?';
            $args[] = $sectionId;
        }
        if ($type !== '' && isset(self::TYPES[$type])) {
            $sql .= ' AND t.property_type = ?';
            $args[] = $type;
        }
        if ($search !== '') {
            $sql .= ' AND (t.setting_key LIKE ? OR t.label LIKE ? OR t.setting_value LIKE ?)';
            $like = '%' . $search . '%';
            array_push($args, $like, $like, $like);
        }
        $sql .= ' ORDER BY s.sort_order, t.sort_order, t.label';
        $st = get_db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = get_db()->prepare('SELECT * FROM theme_settings WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** key => [setting_value, property_type] map used to render the site */
    public static function map(): array
    {
        static $cache = null;
        return $cache ??= get_db()->query('SELECT setting_key, setting_value, property_type FROM theme_settings')
                                  ->fetchAll(PDO::FETCH_UNIQUE);
    }

    /**
     * One validated value for use in PHP (e.g. an inline style), or
     * $fallback when the row is missing, invalid or the DB is unreachable.
     */
    public static function value(string $key, ?string $fallback = null): ?string
    {
        try {
            $row = self::map()[$key] ?? null;
        } catch (Throwable $e) {
            return $fallback;
        }
        if (!$row || !self::isValidValue($row['property_type'], (string) $row['setting_value'])) {
            return $fallback;
        }
        return trim((string) $row['setting_value']);
    }

    /** Changes whenever any setting changes — used for CSS cache-busting */
    public static function version(): string
    {
        $row = get_db()->query('SELECT MAX(updated_at) AS u, COUNT(*) AS c FROM theme_settings')->fetch();
        return substr(md5(($row['u'] ?? '') . '|' . ($row['c'] ?? 0)), 0, 10);
    }

    // --------------------------------------------------------------- write

    /** @return array field => error message; empty when valid */
    public static function validate(array $d, ?int $ignoreId = null): array
    {
        $err = [];

        if (!preg_match('/^[a-z][a-z0-9-]{1,78}$/', (string) ($d['setting_key'] ?? ''))) {
            $err['setting_key'] = 'Use lowercase letters, numbers and hyphens, starting with a letter (e.g. header-bg).';
        } else {
            $st = get_db()->prepare('SELECT id FROM theme_settings WHERE setting_key = ? AND id <> ?');
            $st->execute([$d['setting_key'], $ignoreId ?? 0]);
            if ($st->fetch()) {
                $err['setting_key'] = 'Another setting already uses this key.';
            }
        }

        if (trim((string) ($d['label'] ?? '')) === '') {
            $err['label'] = 'Enter a label so the setting is recognisable in the list.';
        }

        $st = get_db()->prepare('SELECT id FROM theme_sections WHERE id = ?');
        $st->execute([(int) ($d['section_id'] ?? 0)]);
        if (!$st->fetch()) {
            $err['section_id'] = 'Choose the part of the website this setting belongs to.';
        }

        $type = (string) ($d['property_type'] ?? '');
        if (!isset(self::TYPES[$type])) {
            $err['property_type'] = 'Choose a property type.';
        } else {
            foreach (['setting_value' => 'Value', 'default_value' => 'Default value'] as $f => $name) {
                if (!self::isValidValue($type, (string) ($d[$f] ?? ''))) {
                    $err[$f] = $name . ' is not a valid ' . strtolower(self::TYPES[$type]) . '. ' . self::hint($type);
                }
            }
        }

        return $err;
    }

    public static function isValidValue(string $type, string $v): bool
    {
        $v = trim($v);
        $unitNum = '\d{1,4}(\.\d{1,3})?';
        return match ($type) {
            'color' => (bool) preg_match(
                '/^(#[0-9a-f]{3}|#[0-9a-f]{4}|#[0-9a-f]{6}|#[0-9a-f]{8}|transparent|'
                . 'rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\))$/i', $v),
            'font_family'    => (bool) preg_match('/^[A-Za-z0-9 \-\',"]{2,120}$/', $v) && !str_contains($v, ';'),
            'font_size'      => (bool) preg_match('/^' . $unitNum . '(px|rem|em|%|vw)$/', $v),
            'size'           => (bool) preg_match('/^-?' . $unitNum . '(px|rem|em|%|vw|vh)$|^0$/', $v),
            'font_weight'    => in_array($v, ['100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold'], true),
            'font_style'     => isset(self::FONT_STYLES[$v]),
            'text_transform' => isset(self::TEXT_TRANSFORMS[$v]),
            'number'         => (bool) preg_match('/^' . $unitNum . '$/', $v),
            'color_scheme'   => isset(self::COLOR_SCHEMES[$v]),
            default          => false,
        };
    }

    public static function hint(string $type): string
    {
        return match ($type) {
            'color'          => 'Use #RRGGBB, rgb(), rgba() or transparent.',
            'font_family'    => "Use a font stack such as 'Lora', serif.",
            'font_size'      => 'Use a number with px, rem, em, % or vw — e.g. 16px.',
            'size'           => 'Use a number with px, rem, em, %, vw or vh — e.g. 24px.',
            'font_weight'    => 'Use 100–900.',
            'font_style'     => 'Use normal or italic.',
            'text_transform' => 'Use none, uppercase, lowercase or capitalize.',
            'number'         => 'Use a plain number — e.g. 1.6.',
            'color_scheme'   => 'Use dark, light or "light dark".',
            default          => '',
        };
    }

    public static function create(array $d): int
    {
        $st = get_db()->prepare(
            'INSERT INTO theme_settings
                (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $st->execute(self::params($d));
        return (int) get_db()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $st = get_db()->prepare(
            'UPDATE theme_settings SET section_id=?, setting_key=?, label=?, property_type=?,
                    setting_value=?, default_value=?, description=?, sort_order=?
              WHERE id=?'
        );
        $st->execute([...self::params($d), $id]);
    }

    /** Bulk-save values only (used by the quick-edit grid) */
    public static function updateValues(array $idToValue): array
    {
        $saved = 0;
        $errors = [];
        $st = get_db()->prepare('UPDATE theme_settings SET setting_value = ? WHERE id = ?');
        foreach ($idToValue as $id => $value) {
            $value = (string) $value;
            $row = self::find((int) $id);
            if (!$row || $row['setting_value'] === $value) {
                continue;
            }
            if (!self::isValidValue($row['property_type'], $value)) {
                $errors[] = $row['label'] . ' (' . $row['setting_key'] . '): ' . self::hint($row['property_type']);
                continue;
            }
            $st->execute([trim($value), (int) $id]);
            $saved++;
        }
        return [$saved, $errors];
    }

    public static function delete(int $id): void
    {
        get_db()->prepare('DELETE FROM theme_settings WHERE id = ?')->execute([$id]);
    }

    public static function resetToDefault(?int $id = null, ?int $sectionId = null): int
    {
        if ($id) {
            $st = get_db()->prepare('UPDATE theme_settings SET setting_value = default_value WHERE id = ?');
            $st->execute([$id]);
        } elseif ($sectionId) {
            $st = get_db()->prepare('UPDATE theme_settings SET setting_value = default_value WHERE section_id = ?');
            $st->execute([$sectionId]);
        } else {
            $st = get_db()->query('UPDATE theme_settings SET setting_value = default_value');
        }
        return $st->rowCount();
    }

    private static function params(array $d): array
    {
        return [
            (int) $d['section_id'],
            trim((string) $d['setting_key']),
            trim((string) $d['label']),
            (string) $d['property_type'],
            trim((string) $d['setting_value']),
            trim((string) $d['default_value']),
            trim((string) ($d['description'] ?? '')) ?: null,
            (int) ($d['sort_order'] ?? 0),
        ];
    }
}
