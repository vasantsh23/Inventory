<?php
/**
 * GET /api/search_form.php
 * The Diamond Search filters, exactly as the website's search page
 * builds them (same tables, same order, same Fancy Filter handling).
 * Every option carries its ready-to-submit `value`, so the app never
 * has to know the "__ALL__" / "__OTHERS__" / "from|to" conventions.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/diamond_search_form.php';

api_require_method('GET');
api_require_user();

[$main, $advanced] = ds_build_form_sections();

// Each field may appear only once across both lists (duplicate rows in
// diamond_search / adv_filter would otherwise give the app two
// controls fighting over the same filter key). First occurrence wins.
$seen = [];
$dedupe = function (array $sections) use (&$seen): array {
    $out = [];
    foreach ($sections as $s) {
        if (!isset($seen[$s['fldname']])) {
            $seen[$s['fldname']] = true;
            $out[] = $s;
        }
    }
    return $out;
};
$main = $dedupe($main);
$advanced = $dedupe($advanced);

$toApi = function (array $section): array {
    $fld = $section['fldname'];
    $out = [
        'field' => $fld,
        'label' => ds_format_label((string)$section['label']),
        'kind'  => $section['kind'], // pill | shape | checkbox_grid | checkbox | range | generic_range | generic_text
    ];
    if (isset($section['options'])) {
        $out['options'] = array_map(function (array $opt) use ($section, $fld): array {
            $value = ds_option_submit_value($section, $opt);
            return [
                'label'      => (string)$opt['label'],
                'value'      => $value,
                'isAll'      => $value === '__ALL__',
                'isFancy'    => $fld === 'Color' && strcasecmp((string)$opt['label'], 'fancy') === 0,
                'imageUrl'   => isset($opt['image']) ? api_absolute_url($opt['image']) : null,
            ];
        }, $section['options']);
    }
    // Carat gets an extra manual From/To range under its buckets.
    $out['hasManualRange'] = $fld === 'Weight';
    return $out;
};

$latestUpload = get_latest_upload_log();

api_json([
    'sections'         => array_map($toApi, $main),
    'advancedSections' => array_map($toApi, $advanced),
    'dataUpdatedAt'    => $latestUpload === null ? null : trim(
        ($latestUpload['data_uplddate'] ?? '') . ' ' . ($latestUpload['data_upldtime'] ?? '')
    ),
]);
