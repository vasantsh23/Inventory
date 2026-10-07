<?php
/**
 * GET /api/diamond.php?id=123
 * One stone's Diamond Details page: the admin-configured Diamond Info /
 * Price Info / Measurement fields, media links and certificate link.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('GET');
api_require_user();

$id = (string)($_GET['id'] ?? '');
if ($id === '' || !ctype_digit($id)) {
    api_error(400, 'Missing or invalid diamond id.', 'bad_request');
}

$sectionDefs = [
    'DI' => 'Diamond Info',
    'PI' => 'Price Info',
    'MI' => 'Measurement',
];
$sectionFields = [];
foreach ($sectionDefs as $type => $_title) {
    $sectionFields[$type] = get_diamond_details_sections($type);
}

$validCols = get_maindata_columns();
$wanted = array_values(array_unique(array_merge(
    ...array_map(fn($s) => array_column($s, 'field'), array_values($sectionFields)),
    ...[array_intersect(['StockNo', 'imglink', 'avail', 'notforweb', 'Lab', 'CertificateNo',
        'Shape', 'Weight', 'Color', 'Clarity'], $validCols)]
)));
$fieldList = '`id`' . implode('', array_map(fn($f) => ", `$f`", $wanted));
$stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) {
    api_error(404, "That diamond couldn't be found — it may have been removed.", 'not_found');
}

$display = function (string $field) use ($d): string {
    $raw = (string)($d[$field] ?? '');
    if ($field === 'CertificateNo') {
        $raw = format_certificate_no_display($raw);
    } elseif ($field === 'Measurements') {
        $raw = format_measurements_display($raw);
    }
    return $raw !== '' ? $raw : '—';
};

$sections = [];
foreach ($sectionDefs as $type => $title) {
    $sections[] = [
        'title'  => $title,
        'fields' => array_map(fn($f) => ['label' => $f['label'], 'value' => $display($f['field'])], $sectionFields[$type]),
    ];
}

// Same media sources as modules/user/diamond_details.php.
$stockNo = trim((string)($d['StockNo'] ?? ''));
$media = [];
if ($stockNo !== '') {
    $enc = urlencode($stockNo);
    $media = [
        ['kind' => 'image',  'label' => 'Image', 'url' => 'https://v3601425.v360.in/imaged/' . $enc . '/still.jpg'],
        ['kind' => 'web360', 'label' => '360°',  'url' => 'https://v3601425.v360.in/vision360.html?d=' . $enc],
        ['kind' => 'video',  'label' => 'Video', 'url' => 'https://onlinemediafiles.com/info-videos/' . $enc . '.mp4'],
        ['kind' => 'video',  'label' => 'Hand',  'url' => 'https://onlinemediafiles.com/hvideos/' . $enc . '.mp4'],
    ];
} elseif (!empty($d['imglink'])) {
    $media = [['kind' => 'image', 'label' => 'Image', 'url' => api_absolute_url((string)$d['imglink'])]];
}

$headline = implode(' ', array_filter(
    array_map(fn($f) => trim((string)($d[$f] ?? '')), ['Shape', 'Weight', 'Color', 'Clarity']),
    fn($v) => $v !== ''
));

api_json([
    'id'        => (int)$d['id'],
    'stockNo'   => $stockNo,
    'headline'  => $headline,
    'avail'     => trim((string)($d['avail'] ?? '')) ?: null,
    'availColor' => get_stockno_bg_color($d['avail'] ?? null, $d['notforweb'] ?? null),
    'media'     => $media,
    'sections'  => $sections,
    'certUrl'   => build_certificate_url($d['Lab'] ?? null, $d['CertificateNo'] ?? null, $stockNo),
    'webUrl'    => full_url('/modules/user/diamond_details.php?id=' . (int)$d['id']),
]);
