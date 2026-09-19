<?php
/**
 * functions.php
 * Shared helpers for reading the site's public configuration
 * (company info + logo) used to render the home page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Shared pagination control for every paginated table (Results,
 * Customer List, Admin > table_view.php, ...). Renders: « Prev, a
 * window of page numbers around the current page (with first/last
 * page + "…" gaps when the total is large), Next ».
 *
 * @param int      $page        Current page (1-based).
 * @param int      $totalPages  Total number of pages.
 * @param callable $urlFor      fn(int $page): string — builds the href for a given page number.
 */
function render_pagination(int $page, int $totalPages, callable $urlFor): void
{
    if ($totalPages <= 1) {
        return;
    }

    // Which page numbers to show as actual links: the first page,
    // the last page, and a small window around the current page.
    // Everything else collapses into a "…" gap.
    $window = 2;
    $show = [1, $totalPages];
    for ($p = $page - $window; $p <= $page + $window; $p++) {
        if ($p >= 1 && $p <= $totalPages) {
            $show[] = $p;
        }
    }
    $show = array_unique($show);
    sort($show);

    echo '<div class="pagination">';

    echo $page > 1
        ? '<a class="pagination-nav" href="' . e($urlFor($page - 1)) . '" aria-label="Previous page">&laquo; Prev</a>'
        : '<span class="pagination-nav is-disabled" aria-disabled="true">&laquo; Prev</span>';

    $prevShown = 0;
    foreach ($show as $p) {
        if ($prevShown !== 0 && $p - $prevShown > 1) {
            echo '<span class="pagination-ellipsis">&hellip;</span>';
        }
        echo $p === $page
            ? '<span class="current">' . $p . '</span>'
            : '<a href="' . e($urlFor($p)) . '">' . $p . '</a>';
        $prevShown = $p;
    }

    echo $page < $totalPages
        ? '<a class="pagination-nav" href="' . e($urlFor($page + 1)) . '" aria-label="Next page">Next &raquo;</a>'
        : '<span class="pagination-nav is-disabled" aria-disabled="true">Next &raquo;</span>';

    echo '</div>';
}

/**
 * The choices offered by the "Rows per page" dropdown on paginated
 * user-facing tables (Results, View Cart). Kept as one shared list so
 * every such table offers the same options.
 */
function rows_per_page_choices(): array
{
    return [25, 50, 100, 250];
}

/**
 * Resolve how many rows to show per page for a given $sessionKey:
 * an explicit ?per_page= on this request wins (and is remembered in
 * the session so it "sticks" across subsequent plain page reloads /
 * pagination clicks that don't repeat the query param); otherwise the
 * previously remembered value is used; otherwise $default. Always
 * clamped to rows_per_page_choices() so an edited/garbage URL can't
 * force an arbitrarily large page size.
 */
function resolve_per_page(string $sessionKey, int $default = 100): int
{
    $choices = rows_per_page_choices();

    $requested = $_GET['per_page'] ?? null;
    if ($requested !== null && in_array((int)$requested, $choices, true)) {
        $_SESSION[$sessionKey] = (int)$requested;
        return (int)$requested;
    }

    $remembered = $_SESSION[$sessionKey] ?? null;
    if ($remembered !== null && in_array((int)$remembered, $choices, true)) {
        return (int)$remembered;
    }

    return in_array($default, $choices, true) ? $default : $choices[0];
}

/** The real, current column names of `maindata` — the only names ever used as SQL identifiers here. */
function get_maindata_columns(): array
{
    static $cols = null;
    if ($cols !== null) {
        return $cols;
    }
    $stmt = get_db()->query(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maindata'"
    );
    $cols = array_column($stmt->fetchAll(), 'COLUMN_NAME');
    return $cols;
}

/**
 * Registry of rounding methods `rounding_rules.method` can select.
 * Adding a genuinely new rounding ALGORITHM (not just a different
 * increment for an existing one, like "10 cents" instead of "5
 * cents" — that's just a new row) means adding a case here, since a
 * database row can pick among these but can't invent new math on its
 * own — that's a deliberate safety/correctness boundary, not an
 * oversight. $increment is only used by the *_increment methods;
 * every other method ignores it.
 */
function round_amount_apply(string $method, float $amount, ?float $increment): float
{
    switch ($method) {
        case 'ceil_whole':
            return ceil($amount);
        case 'round_whole':
            return round($amount);
        case 'floor_whole':
            return floor($amount);
        case 'nearest_increment':
            $inc = $increment !== null && $increment > 0 ? $increment : 1.0;
            return round($amount / $inc) * $inc;
        case 'ceil_increment':
            $inc = $increment !== null && $increment > 0 ? $increment : 1.0;
            return ceil($amount / $inc) * $inc;
        case 'floor_increment':
            $inc = $increment !== null && $increment > 0 ? $increment : 1.0;
            return floor($amount / $inc) * $inc;
        case 'none':
            return $amount;
        default:
            // An unrecognised method (e.g. a row references a method
            // whose code was since removed) fails safe to today's
            // existing behavior rather than guessing.
            return ceil($amount);
    }
}

/**
 * The currently-selected row from `rounding_rules` (active='yes'),
 * cached for the rest of this request. Falls back to today's
 * existing "round up to whole dollar" behavior if the table is
 * missing, empty, or nothing is marked active — so this feature is
 * fully backward-compatible until an admin actually picks a
 * different rule via its CRUD screen ("Amount Rounding Rules").
 */
function get_active_rounding_rule(): array
{
    static $rule = null;
    if ($rule !== null) {
        return $rule;
    }
    $default = ['method' => 'ceil_whole', 'increment' => null];
    try {
        $row = get_db()->query("SELECT method, increment FROM rounding_rules WHERE active = 'yes' ORDER BY id ASC LIMIT 1")->fetch();
        $rule = $row ?: $default;
    } catch (Throwable $e) {
        $rule = $default; // table doesn't exist yet, etc.
    }
    return $rule;
}

/**
 * Amount for display on the Results and View Cart (view_results.php)
 * screens: the raw `totamt` value as stored, rounded per whichever
 * rule is currently active in `rounding_rules` (see
 * get_active_rounding_rule() / round_amount_apply()).
 */
function ds_display_amount(mixed $totamt): float
{
    $raw = (float)$totamt;
    $rule = get_active_rounding_rule();
    return round_amount_apply((string)$rule['method'], $raw, $rule['increment'] !== null ? (float)$rule['increment'] : null);
}

/** column_name => DATA_TYPE (e.g. 'int', 'decimal', 'varchar', 'text')
 * for every real column of `maindata` — used to decide how to render
 * a filter for a field that isn't one of the specially-handled ones
 * (Shape, Color, Price, etc.): numeric-ish columns get a From/To
 * range, everything else gets a free-text search box. */
function get_maindata_column_types(): array
{
    static $types = null;
    if ($types !== null) {
        return $types;
    }
    $stmt = get_db()->query(
        "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maindata'"
    );
    $types = [];
    foreach ($stmt->fetchAll() as $row) {
        $types[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
    }
    return $types;
}

/** Whether a maindata DATA_TYPE (from information_schema) is numeric. */
function is_numeric_maindata_type(string $dataType): bool
{
    return in_array(strtolower($dataType), ['int', 'decimal', 'float', 'double', 'bigint', 'smallint', 'tinyint', 'numeric'], true);
}

/**
 * Fetch the single setup record — company info, address, phone,
 * email, and (used elsewhere) the social-media SM1-6 fields.
 */
function get_setup(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = get_db()->query('SELECT * FROM setup ORDER BY id ASC LIMIT 1');
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

/**
 * Display settings for the Results and View Cart screens (font
 * family + size). Always the latest row — admins manage this like a
 * single settings record, even though the table technically allows
 * more than one.
 */
function get_rsetup(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = get_db()->query('SELECT * FROM rsetup ORDER BY id DESC LIMIT 1');
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

/**
 * Look up a file path from the `path` table by matching `description`.
 * Used specifically to resolve the logo file: `description = 'logo'`.
 */
function get_path_by_description(string $description): ?string
{
    $stmt = get_db()->prepare(
        'SELECT `path` FROM path WHERE `description` = :d LIMIT 1'
    );
    $stmt->execute([':d' => $description]);
    $row = $stmt->fetch();
    return $row['path'] ?? null;
}

/** Convenience wrapper: the company logo path specifically. */
function get_logo_path(): string
{
    return get_path_by_description('logo') ?? '/assets/img/default-logo.png';
}

/** Build a single "address" display string from the non-empty address lines. */
function format_address(array $setup): string
{
    $lines = [];
    foreach (['address-1', 'address-2', 'address-3', 'address-4', 'address-5', 'address-6'] as $key) {
        if (!empty($setup[$key])) {
            $lines[] = $setup[$key];
        }
    }
    return implode(', ', $lines);
}

/** First non-empty phone number configured in setup. */
function primary_phone(array $setup): string
{
    foreach (['telno-1', 'telno-2', 'telno-3'] as $key) {
        if (!empty($setup[$key])) {
            return $setup[$key];
        }
    }
    return '';
}

/** First non-empty email address configured in setup. */
function primary_email(array $setup): string
{
    foreach (['emailid1', 'emailid2'] as $key) {
        if (!empty($setup[$key])) {
            return $setup[$key];
        }
    }
    return '';
}

/**
 * Turns a raw diamond_search.colname like "CutGrade" or
 * "FluorescenceIntensity" into a readable heading ("Cut Grade",
 * "Fluorescence Intensity") — still literally derived from colname,
 * just with spacing inserted for display.
 */
function ds_format_label(string $colname): string
{
    return trim((string)preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $colname));
}

/** Fldname => lookup-table-backed pill/shape section definition. */
function ds_lookup_map(): array
{
    return [
        'Shape'                 => ['table' => 'shape', 'label' => 'Display _nm', 'match' => 'shape', 'hasActive' => true, 'orderCol' => 'order', 'image' => 'imgpath'],
        'Weight'                => ['table' => 'size', 'label' => 'sizedesc', 'hasActive' => false, 'orderCol' => 'sizefr'],
        'Color'                 => ['table' => 'color', 'label' => 'color', 'match' => 'color', 'hasActive' => true, 'orderCol' => 'id'],
        'Clarity'               => ['table' => 'clarity', 'label' => 'clarity', 'match' => 'clarity', 'hasActive' => true, 'orderCol' => 'id'],
        'CutGrade'              => ['table' => 'cut', 'label' => 'cut', 'match' => 'cut', 'hasActive' => true, 'orderCol' => 'order'],
        'Polish'                => ['table' => 'polish', 'label' => 'pol', 'match' => 'pol', 'hasActive' => true, 'orderCol' => 'order'],
        'Symmetry'              => ['table' => 'symmetry', 'label' => 'sym', 'match' => 'sym', 'hasActive' => true, 'orderCol' => 'order'],
        'FluorescenceIntensity' => ['table' => 'fluorescence', 'label' => 'flu', 'match' => 'flu', 'hasActive' => true, 'orderCol' => 'order'],
        'Lab'                   => ['table' => 'lab', 'label' => 'lab', 'match' => 'lab', 'hasActive' => true, 'orderCol' => 'id'],
        'location'              => ['table' => 'location', 'label' => 'location', 'match' => 'location', 'hasActive' => true, 'orderCol' => 'order', 'style' => 'checkbox_grid'],
        'avail'                 => ['table' => 'availability', 'label' => 'avail', 'match' => 'avail', 'hasActive' => true, 'orderCol' => 'order', 'style' => 'checkbox_grid'],
    ];
}

/** Fldname => a single Yes/No checkbox tied directly to a maindata field. */
function ds_checkbox_map(): array
{
    return ['heart' => 'heart'];
}

/** Fldname => a numeric From/To range filter tied to a maindata field. */
function ds_range_map(): array
{
    return [
        'Price' => 'Price',
        'totamt' => 'totamt', // best-guess mapping for "Amount" — flagged for confirmation
    ];
}

/**
 * Build a filter screen's sections from $sourceTable (either
 * `diamond_search` for the main filters, or `adv_filter` for the
 * "Advanced Filter" extras). Which sections appear, and in what
 * order, is driven entirely by that table (active = 'yes', ordered
 * by orderid) — this function only knows HOW to render each
 * recognised fldname; both tables share the same structure and the
 * same set of recognised fields (Shape, Color, Clarity, etc.), so a
 * field just needs to be active in whichever table to show up there.
 */
function get_diamond_search_sections(string $sourceTable = 'diamond_search'): array
{
    static $validSourceTables = ['diamond_search', 'adv_filter'];
    if (!in_array($sourceTable, $validSourceTables, true)) {
        $sourceTable = 'diamond_search'; // never interpolate an unvalidated table name into SQL
    }

    $lookupMap = ds_lookup_map();
    $checkboxMap = ds_checkbox_map();
    $rangeMap = ds_range_map();

    // IMPORTANT: match against `fldname`, not `colname`. `colname` is
    // the admin-editable DISPLAY label (e.g. renamed to "Availability",
    // "Cut", "Amount", "Carat") and can be changed freely; `fldname`
    // is the stable technical reference this code relies on to know
    // which lookup table/rendering applies. Matching on colname would
    // silently break the moment an admin renames a label.
    $allFldnames = array_merge(array_keys($lookupMap), array_keys($checkboxMap), array_keys($rangeMap));
    $stmt = get_db()->prepare(
        "SELECT colname, fldname FROM `$sourceTable` WHERE active = 'yes' ORDER BY orderid ASC"
    );
    $stmt->execute();
    $activeRows = $stmt->fetchAll();

    $maindataCols = get_maindata_columns();
    $maindataTypes = get_maindata_column_types();

    $sections = [];
    foreach ($activeRows as $activeRow) {
        $fldname = $activeRow['fldname'];
        $displayLabel = $activeRow['colname']; // current admin-set label, shown on screen

        if (isset($lookupMap[$fldname])) {
            $def = $lookupMap[$fldname];
            $table = $def['table'];

            $sql = "SELECT * FROM `$table`";
            if ($def['hasActive']) {
                $sql .= " WHERE active = 'yes'";
            }
            $sql .= " ORDER BY `{$def['orderCol']}` ASC";

            $rows = get_db()->query($sql)->fetchAll();

            $options = [];
            foreach ($rows as $row) {
                $option = [
                    'id'    => $row['id'],
                    'label' => (string)($row[$def['label']] ?? ''),
                    // The value actually matched against maindata — usually
                    // the same field as the label, except Shape (which uses
                    // its short code, not the display name).
                    'value' => (string)($row[$def['match'] ?? $def['label']] ?? ''),
                ];
                if (!empty($def['image'])) {
                    $img = trim((string)($row[$def['image']] ?? ''));
                    $option['image'] = $img !== ''
                        ? (preg_match('#^https?://#i', $img) ? $img : asset_url($img))
                        : null;
                }
                if ($table === 'size') {
                    $option['from'] = $row['sizefr'];
                    $option['to'] = $row['sizeto'];
                }
                $options[] = $option;
            }

            $kind = 'pill';
            if (!empty($def['image'])) {
                $kind = 'shape';
            } elseif (($def['style'] ?? '') === 'checkbox_grid') {
                $kind = 'checkbox_grid';
            }

            $sections[] = [
                'kind'    => $kind,
                'fldname' => $fldname,
                'label'   => $displayLabel,
                'options' => $options,
            ];
        } elseif (isset($checkboxMap[$fldname])) {
            $sections[] = [
                'kind'    => 'checkbox',
                'fldname' => $fldname,
                'label'   => $displayLabel,
                'field'   => $checkboxMap[$fldname],
            ];
        } elseif (isset($rangeMap[$fldname])) {
            $sections[] = [
                'kind'    => 'range',
                'fldname' => $fldname,
                'label'   => $displayLabel,
                'field'   => $rangeMap[$fldname],
            ];
        } elseif (in_array($fldname, $maindataCols, true)) {
            // Not one of the specially-handled fields (no lookup table,
            // not a known checkbox/range) — but it IS a real maindata
            // column, so offer a generic filter for it: a numeric
            // From/To range for number-ish columns, or a free-text
            // search box for everything else.
            $isNumeric = is_numeric_maindata_type($maindataTypes[$fldname] ?? '');
            $sections[] = [
                'kind'    => $isNumeric ? 'generic_range' : 'generic_text',
                'fldname' => $fldname,
                'label'   => $displayLabel,
                'field'   => $fldname,
            ];
        }
    }

    return $sections;
}

/**
 * Business hours from the `timings` table, ordered Monday through
 * Sunday regardless of insertion order. Each row may have up to 3
 * start/end time slots (for split shifts) and a holiday flag.
 */
function get_business_hours(): array
{
    $stmt = get_db()->query(
        "SELECT * FROM timings
         ORDER BY FIELD(`Day`, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')"
    );
    return $stmt->fetchAll();
}

/** Format one timings row's slots into a display string, e.g. "9:00 AM – 5:00 PM". */
function format_business_hours_row(array $row): string
{
    if (strcasecmp(trim((string)($row['holiday'] ?? '')), 'Yes') === 0) {
        return 'Closed';
    }
    $slots = [];
    foreach ([1, 2, 3] as $n) {
        $start = trim((string)($row["start time{$n}"] ?? ''));
        $end = trim((string)($row["end time{$n}"] ?? ''));
        if ($start === '' && $end === '') {
            continue;
        }
        $slots[] = trim($start . ($end !== '' ? ' – ' . $end : ''));
    }
    return $slots !== [] ? implode(', ', $slots) : '—';
}

/**
 * Social media icon links for the home page, built from the setup
 * table's SM1-6 fields. A slot is shown only when its "card" field
 * (the platform name, e.g. "Facebook") is non-blank. The icon image
 * filename comes from that slot's "Image" field; the folder it lives
 * in comes from the `path` table's row where description = 'smedia'.
 * Slots with no configured icon folder, or no image filename, are
 * skipped rather than showing a broken image.
 */
function get_social_media_links(array $setup): array
{
    $basePath = get_path_by_description('smedia');
    if ($basePath === null || trim($basePath) === '') {
        return [];
    }
    $basePath = rtrim($basePath, '/');

    $links = [];
    for ($i = 1; $i <= 6; $i++) {
        $card = trim((string)($setup["SM{$i} card"] ?? ''));
        if ($card === '') {
            continue;
        }
        $image = trim((string)($setup["SM{$i} Image"] ?? ''));
        if ($image === '') {
            continue; // no icon filename configured for this slot
        }
        $title = trim((string)($setup["SM{$i} title"] ?? '')) ?: $card;
        $links[] = [
            'card'  => $card,
            'title' => $title,
            'url'   => trim((string)($setup["SM{$i} url"] ?? '')),
            'image' => $basePath . '/' . ltrim($image, '/'),
        ];
    }
    return $links;
}

/**
 * Turn a loosely-entered CSS length into something browsers will
 * actually accept. A bare number like "9" is invalid CSS for
 * font-size (silently ignored by the browser) — this adds "px" if
 * no unit was given. Values that already look like valid CSS
 * (units, "inherit", "1.2em", etc.) are passed through unchanged.
 */
function normalize_css_length(string $value): string
{
    $value = trim($value);
    return preg_match('/^\d+(\.\d+)?$/', $value) ? $value . 'px' : $value;
}

/**
 * Resolve the currently-active theme from the font_and_color table.
 * The table stores 5 numbered slots each for font type/size/fore
 * color/back color; the selected_* columns say which slot is active.
 * Returns null values for anything not configured, so callers can
 * fall back to the site's default styling.
 */
function get_active_theme(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $default = ['fontFamily' => null, 'fontSize' => null, 'foreColor' => null, 'backColor' => null];

    $stmt = get_db()->query('SELECT * FROM font_and_color ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch();
    if (!$row) {
        $cache = $default;
        return $cache;
    }

    $resolve = function (?string $slotName) use ($row): ?string {
        if ($slotName === null || $slotName === '' || !array_key_exists($slotName, $row)) {
            return null;
        }
        $value = trim((string)$row[$slotName]);
        return $value !== '' ? $value : null;
    };

    $cache = [
        'fontFamily' => $resolve($row['selected_fonttype'] ?? null),
        'fontSize'   => $resolve($row['selected_fontsize'] ?? null),
        'foreColor'  => $resolve($row['selected_forecolor'] ?? null),
        'backColor'  => $resolve($row['selected_backcolor'] ?? null),
    ];
    return $cache;
}
