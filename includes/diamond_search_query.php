<?php
/**
 * diamond_search_query.php
 * Turns the Diamond Search form's submitted filters into a safe SQL
 * WHERE clause against `maindata`. Every field name used in the
 * query is validated against maindata's REAL columns (via
 * information_schema) before use — fldname is admin-editable data,
 * so it must never be trusted directly as a SQL identifier.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php'; // get_maindata_columns(), get_maindata_column_types(), etc.

/**
 * Build [whereSql, params] for maindata from the Diamond Search
 * form's submitted filters ($filters = $_GET['f'] ?? []).
 */
function build_maindata_search_where(array $filters): array
{
    $validCols = get_maindata_columns();
    $lookupMap = ds_lookup_map();
    $checkboxMap = ds_checkbox_map();
    $rangeMap = ds_range_map();

    $clauses = [];
    $params = [];
    $pIndex = 0;

    // --- Stock No quick search: multiple values separated by
    //     whitespace, matched exactly (case-sensitive as stored). ---
    if (in_array('StockNo', $validCols, true)) {
        $stockNoRaw = trim((string)($filters['stockno_search'] ?? ''));
        if ($stockNoRaw !== '') {
            $stockNos = array_values(array_unique(array_filter(preg_split('/\s+/', $stockNoRaw))));
            if ($stockNos !== []) {
                $inKeys = [];
                foreach ($stockNos as $val) {
                    $key = ':p' . $pIndex++;
                    $inKeys[] = $key;
                    $params[$key] = $val;
                }
                $clauses[] = '`StockNo` IN (' . implode(', ', $inKeys) . ')';
            }
        }
    }

    // --- Lookup-backed sections (Shape, Color, Clarity, Cut, Polish,
    //     Symmetry, Fluorescence, Lab, Location, Availability, Weight) ---
    foreach ($lookupMap as $fldname => $def) {
        if (!in_array($fldname, $validCols, true)) {
            continue; // fldname doesn't match a real maindata column — ignore
        }
        $selected = $filters[$fldname] ?? [];
        if (!is_array($selected) || $selected === []) {
            continue; // nothing checked for this criteria — no filter
        }

        // Weight/Carat: each value is "from|to"; combine selected
        // buckets with OR.
        if ($fldname === 'Weight') {
            $rangeClauses = [];
            foreach ($selected as $val) {
                $parts = explode('|', (string)$val, 2);
                if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                    continue;
                }
                $fromKey = ':p' . $pIndex++;
                $toKey = ':p' . $pIndex++;
                $rangeClauses[] = "(`$fldname` BETWEEN $fromKey AND $toKey)";
                $params[$fromKey] = $parts[0];
                $params[$toKey] = $parts[1];
            }
            if ($rangeClauses !== []) {
                $clauses[] = '(' . implode(' OR ', $rangeClauses) . ')';
            }
            continue;
        }

        // "All" selected -> no filter at all for this criteria.
        if (in_array('__ALL__', $selected, true)) {
            continue;
        }

        // Color is special-cased ahead of the normal matching below:
        // "Fancy" replaces Color matching with fancy = 'yes' (and
        // takes priority over anything else selected here, matching
        // the page's own JS which disables the other color pills the
        // moment Fancy is chosen — every OTHER filter section still
        // applies normally, this only replaces the Color criterion
        // itself); "Others" replaces the normal "not in the lookup
        // table" exclusion with the specific srtcol = 35 AND
        // fancy = 'no' condition requested for this field.
        if ($fldname === 'Color') {
            if (in_array('Fancy', $selected, true) && in_array('fancy', $validCols, true)) {
                $key = ':p' . $pIndex++;
                $clauses[] = "`fancy` = $key";
                $params[$key] = 'yes';
                continue;
            }
            if (in_array('__OTHERS__', $selected, true) && in_array('srtcol', $validCols, true) && in_array('fancy', $validCols, true)) {
                $srtcolKey = ':p' . $pIndex++;
                $fancyKey = ':p' . $pIndex++;
                $clauses[] = "(`srtcol` = $srtcolKey AND `fancy` = $fancyKey)";
                $params[$srtcolKey] = 35;
                $params[$fancyKey] = 'no';
                continue;
            }
        }

        $isShapeOrColor = in_array($fldname, ['Shape', 'Color'], true);
        $othersSelected = $isShapeOrColor && in_array('__OTHERS__', $selected, true);
        $specificValues = array_filter($selected, fn($v) => $v !== '__OTHERS__' && $v !== '__ALL__');

        $orParts = [];
        if ($specificValues !== []) {
            $inKeys = [];
            foreach (array_values($specificValues) as $val) {
                $key = ':p' . $pIndex++;
                $inKeys[] = $key;
                $params[$key] = $val;
            }
            $orParts[] = "`$fldname` IN (" . implode(', ', $inKeys) . ')';
        }
        if ($othersSelected) {
            // "Others" = not present in the corresponding lookup table
            // (Shape -> shape.shape, Color -> color.color). Color's
            // own "Others" is handled above instead — this remains
            // for Shape (and any other future lookup field that gets
            // an "Others" option).
            $lookupTable = $def['table'];
            $lookupCol = $def['match'];
            $orParts[] = "`$fldname` NOT IN (SELECT `$lookupCol` FROM `$lookupTable`)";
        }
        if ($orParts !== []) {
            $clauses[] = '(' . implode(' OR ', $orParts) . ')';
        }
    }

    // --- Manual Carat (Weight) From/To range, entered alongside the
    //     pill buckets above — applied as an additional AND constraint
    //     regardless of whether any pill is also checked. ---
    if (in_array('Weight', $validCols, true)) {
        $manualFrom = trim((string)($filters['Weight_from'] ?? ''));
        $manualTo = trim((string)($filters['Weight_to'] ?? ''));
        if ($manualFrom !== '' && is_numeric($manualFrom)) {
            $key = ':p' . $pIndex++;
            $clauses[] = "`Weight` >= $key";
            $params[$key] = $manualFrom;
        }
        if ($manualTo !== '' && is_numeric($manualTo)) {
            $key = ':p' . $pIndex++;
            $clauses[] = "`Weight` <= $key";
            $params[$key] = $manualTo;
        }
    }

    // --- Single Yes/No checkboxes (Heart) ---
    foreach ($checkboxMap as $fldname => $mainField) {
        if (!in_array($mainField, $validCols, true)) {
            continue;
        }
        if (!empty($filters[$fldname])) {
            $key = ':p' . $pIndex++;
            $clauses[] = "`$mainField` = $key";
            $params[$key] = 'yes';
        }
    }

    // --- Numeric ranges (Price, Amount) ---
    foreach ($rangeMap as $fldname => $mainField) {
        if (!in_array($mainField, $validCols, true)) {
            continue;
        }
        $from = trim((string)($filters[$fldname . '_from'] ?? ''));
        $to = trim((string)($filters[$fldname . '_to'] ?? ''));
        if ($from !== '' && is_numeric($from)) {
            $key = ':p' . $pIndex++;
            $clauses[] = "`$mainField` >= $key";
            $params[$key] = $from;
        }
        if ($to !== '' && is_numeric($to)) {
            $key = ':p' . $pIndex++;
            $clauses[] = "`$mainField` <= $key";
            $params[$key] = $to;
        }
    }

    // --- Generic fields (not in any of the maps above, but active in
    //     diamond_search or adv_filter and a real maindata column) ---
    $genericSections = array_merge(
        get_diamond_search_sections('diamond_search'),
        get_diamond_search_sections('adv_filter')
    );
    $seenGeneric = [];
    foreach ($genericSections as $section) {
        $fldname = $section['fldname'];
        if (isset($seenGeneric[$fldname])) {
            continue;
        }
        $seenGeneric[$fldname] = true;

        if ($section['kind'] === 'generic_range' && in_array($fldname, $validCols, true)) {
            $from = trim((string)($filters[$fldname . '_from'] ?? ''));
            $to = trim((string)($filters[$fldname . '_to'] ?? ''));
            if ($from !== '' && is_numeric($from)) {
                $key = ':p' . $pIndex++;
                $clauses[] = "`$fldname` >= $key";
                $params[$key] = $from;
            }
            if ($to !== '' && is_numeric($to)) {
                $key = ':p' . $pIndex++;
                $clauses[] = "`$fldname` <= $key";
                $params[$key] = $to;
            }
        } elseif ($section['kind'] === 'generic_text' && in_array($fldname, $validCols, true)) {
            $text = trim((string)($filters[$fldname . '_text'] ?? ''));
            if ($text !== '') {
                $key = ':p' . $pIndex++;
                $clauses[] = "`$fldname` LIKE $key";
                $params[$key] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $text) . '%';
            }
        }
    }

    $where = $clauses !== [] ? implode(' AND ', $clauses) : '1=1';
    return [$where, $params];
}

/**
 * Build an ORDER BY clause from rsetup's configured sort fields
 * (sortfld1..6 / sortorder1..6, 'A' = ASC, 'D' = DESC), for the
 * Results and View Cart screens. Every sortfld is validated against
 * maindata's real columns before use. Falls back to `id ASC` when no
 * sort fields are configured (or none are valid).
 */
function build_rsetup_order_by(): string
{
    $rsetup = get_rsetup();
    $validCols = get_maindata_columns();
    $parts = [];

    if ($rsetup !== null) {
        for ($i = 1; $i <= 6; $i++) {
            $fld = trim((string)($rsetup["sortfld$i"] ?? ''));
            if ($fld === '' || !in_array($fld, $validCols, true)) {
                continue;
            }
            $dir = strtoupper(trim((string)($rsetup["sortorder$i"] ?? '')));
            $dir = $dir === 'D' ? 'DESC' : 'ASC'; // default to ascending for anything else, including blank
            $parts[] = "`$fld` $dir";
        }
    }

    if ($parts === []) {
        return in_array('id', $validCols, true) ? '`id` ASC' : '1';
    }

    return implode(', ', $parts);
}

/**
 * The background color to apply to a Stock No cell on Results/View
 * Cart: matched from `availability.shortnm` against maindata's own
 * `avail` value, with `notforweb = 'true'` (checked case-
 * insensitively) always overriding to a fixed alert color. Returns
 * null when no color should be applied (avail blank and not flagged
 * "not for web").
 */
function get_stockno_bg_color(?string $avail, ?string $notforweb): ?string
{
    if (strcasecmp(trim((string)$notforweb), 'true') === 0) {
        return '#f33b2b';
    }

    $avail = trim((string)$avail);
    if ($avail === '') {
        return null;
    }

    static $cache = [];
    if (array_key_exists($avail, $cache)) {
        return $cache[$avail];
    }

    $stmt = get_db()->prepare('SELECT color FROM availability WHERE shortnm = :a LIMIT 1');
    $stmt->execute([':a' => $avail]);
    $row = $stmt->fetch();
    $color = ($row && trim((string)$row['color']) !== '') ? $row['color'] : null;
    $cache[$avail] = $color;
    return $color;
}

/**
 * The certificate PDF URL for a diamond, based on Lab: HRD/IGI use a
 * path on this site's own domain (keyed by CertificateNo); GIA uses
 * a fixed external domain (keyed by StockNo instead). Any other lab,
 * or a CertificateNo of exactly "0" (treated the same as blank),
 * returns null — nothing to link to.
 */
function build_certificate_url(?string $lab, ?string $certNo, ?string $stockNo): ?string
{
    $lab = strtoupper(trim((string)$lab));
    $certNo = trim((string)$certNo);
    $stockNo = trim((string)$stockNo);
    if ($certNo !== '' && is_numeric($certNo) && (float)$certNo === 0.0) {
        $certNo = '';
    }

    if ($certNo !== '' && in_array($lab, ['HRD', 'IGI'], true)) {
        return full_url('/cert/' . strtolower($lab) . '/' . rawurlencode($certNo) . '.pdf');
    }
    if ($lab === 'GIA' && $stockNo !== '') {
        return 'https://veeradimon.be/diamond_update/' . rawurlencode($stockNo) . '.pdf';
    }
    return null;
}

/**
 * Display value for CertificateNo — blank instead of a literal "0"
 * (a cert number of 0 means "none on file", not a real number).
 */
function format_certificate_no_display(?string $certNo): string
{
    $certNo = trim((string)$certNo);
    if ($certNo !== '' && is_numeric($certNo) && (float)$certNo === 0.0) {
        return '';
    }
    return $certNo;
}

/**
 * Display value for Measurements — blank instead of a literal
 * "0.00 x 0.00 x 0.00" (all-zero dimensions mean no measurement on
 * file, not a real 0mm stone).
 */
function format_measurements_display(?string $measurements): string
{
    $measurements = trim((string)$measurements);
    if ($measurements === '') {
        return '';
    }
    if (preg_match('/^([\d.]+)\s*[xX×]\s*([\d.]+)\s*[xX×]\s*([\d.]+)$/', $measurements, $m)
        && (float)$m[1] === 0.0 && (float)$m[2] === 0.0 && (float)$m[3] === 0.0) {
        return '';
    }
    return $measurements;
}

/**
 * Read the `results` table's active columns (label = colname, data
 * source = fldname), validated against maindata's real columns —
 * fldname is admin-editable, so it must never be trusted directly as
 * a SQL identifier. Shared by the Results page and the Cart/View
 * Results page, so both render the same set of columns.
 */
function get_results_columns(): array
{
    $validCols = get_maindata_columns();
    $stmt = get_db()->query("SELECT colname, fldname FROM results WHERE active = 'yes' ORDER BY orderid ASC");
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        if (in_array($row['fldname'], $validCols, true)) {
            $out[] = ['label' => $row['colname'], 'field' => $row['fldname']];
        }
    }
    return $out;
}

/**
 * A human-readable summary of the filters actually applied, e.g.
 * [['label' => 'Color', 'value' => 'D, E, Others'], ...] — built from
 * the same submitted $filters used to run the search, for display
 * next to the result count. Criteria left on "All" (i.e. no real
 * constraint) are omitted, since they didn't affect the results.
 */
function build_applied_filters_summary(array $filters): array
{
    // Merge both sources so an Advanced Filter selection gets a
    // correctly labeled tag too, not just the main search fields.
    $mainSections = get_diamond_search_sections('diamond_search');
    $advancedSections = get_diamond_search_sections('adv_filter');
    $seenFldnames = array_column($mainSections, 'fldname');
    $sections = $mainSections;
    foreach ($advancedSections as $advSection) {
        if (!in_array($advSection['fldname'], $seenFldnames, true)) {
            $sections[] = $advSection;
        }
    }
    $summary = [];

    // Stock No quick search isn't part of the configurable sections —
    // add its own tag directly when present.
    $stockNoRaw = trim((string)($filters['stockno_search'] ?? ''));
    if ($stockNoRaw !== '') {
        $summary[] = ['label' => 'Stock No', 'value' => $stockNoRaw];
    }

    foreach ($sections as $section) {
        $fld = $section['fldname'];

        if ($section['kind'] === 'checkbox') {
            if (!empty($filters[$fld])) {
                $summary[] = ['label' => ds_format_label($section['label']), 'value' => 'Yes'];
            }
            continue;
        }

        if ($section['kind'] === 'range' || $section['kind'] === 'generic_range') {
            $from = trim((string)($filters[$fld . '_from'] ?? ''));
            $to = trim((string)($filters[$fld . '_to'] ?? ''));
            if ($from === '' && $to === '') {
                continue;
            }
            $parts = [];
            if ($from !== '') {
                $parts[] = 'from ' . $from;
            }
            if ($to !== '') {
                $parts[] = 'to ' . $to;
            }
            $summary[] = ['label' => ds_format_label($section['label']), 'value' => implode(' ', $parts)];
            continue;
        }

        if ($section['kind'] === 'generic_text') {
            $text = trim((string)($filters[$fld . '_text'] ?? ''));
            if ($text !== '') {
                $summary[] = ['label' => ds_format_label($section['label']), 'value' => '"' . $text . '"'];
            }
            continue;
        }

        // Pill/shape sections.
        $selected = $filters[$fld] ?? [];
        if (!is_array($selected) || $selected === []) {
            continue;
        }
        if (in_array('__ALL__', $selected, true)) {
            continue; // "All" = no real constraint, nothing to summarize
        }

        $labels = [];
        foreach ($selected as $val) {
            if ($val === '__OTHERS__') {
                $labels[] = 'Others';
                continue;
            }
            if ($fld === 'Weight') {
                $parts = explode('|', (string)$val, 2);
                if (count($parts) === 2) {
                    $labels[] = $parts[0] . '-' . $parts[1];
                }
                continue;
            }
            foreach ($section['options'] as $opt) {
                if (($opt['value'] ?? $opt['label']) === $val) {
                    $labels[] = $opt['label'];
                    break;
                }
            }
        }
        if ($labels !== []) {
            $summary[] = ['label' => ds_format_label($section['label']), 'value' => implode(', ', $labels)];
        }
    }

    return $summary;
}

/**
 * Read diamond_details' active columns for one data_type section
 * (DI = Diamond Info, PI = Price Info, MI = Measurement), sorted by
 * orderid — label = colname, data source = fldname, validated
 * against maindata's real columns (fldname is admin-editable data).
 */
function get_diamond_details_sections(string $dataType): array
{
    $validCols = get_maindata_columns();
    $stmt = get_db()->prepare(
        "SELECT colname, fldname FROM diamond_details WHERE active = 'yes' AND data_type = ? ORDER BY orderid ASC"
    );
    $stmt->execute([$dataType]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        if (in_array($row['fldname'], $validCols, true)) {
            $out[] = ['label' => $row['colname'], 'field' => $row['fldname']];
        }
    }
    return $out;
}
