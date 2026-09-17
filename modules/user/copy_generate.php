<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';

require_module_access('user');

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please reload the page and try again.');
}

$idsRaw = (string)($_POST['ids'] ?? '');
$ids = array_values(array_unique(array_filter(
    array_map('trim', explode(',', $idsRaw)),
    fn($v) => $v !== '' && ctype_digit($v)
)));

if ($ids === []) {
    http_response_code(400);
    exit('Please select at least one row to copy.');
}

// --- Log this batch (one row per diamond, sharing one timestamp),
//     matching the reference program's main_self_copy.php behaviour.
//     Each insert's own id becomes the "urlid" encoded into that
//     diamond's share URL below. ---
$time = (string)time();
$logStmt = get_db()->prepare('INSERT INTO self_short_urls (ids, timeon) VALUES (:id, :t)');
$urlIdByMaindataId = [];
foreach ($ids as $id) {
    $logStmt->execute([':id' => $id, ':t' => $time]);
    $urlIdByMaindataId[$id] = (string)get_db()->lastInsertId();
}

// --- Fetch the selected diamonds, sorted the same way as the
//     reference program (by shape/size/color/clarity sort-order
//     columns, then weight descending). ---
$validCols = get_maindata_columns();
$wanted = ['id', 'StockNo', 'Shape', 'Weight', 'Color', 'Clarity', 'CutGrade', 'Polish', 'Symmetry',
    'FluorescenceIntensity', 'Lab', 'Rap', 'RapOff', 'Price', 'avail', 'location'];
$wanted = array_values(array_intersect($wanted, $validCols));
$hasNotForWeb = in_array('notforweb', $validCols, true);
if ($hasNotForWeb) {
    $wanted[] = 'notforweb';
}
$fieldList = implode(', ', array_map(fn($f) => "`$f`", $wanted));

$sortParts = [];
foreach (['srtshp' => 'ASC', 'srtsiz' => 'DESC', 'srtcol' => 'ASC', 'srtcla' => 'ASC'] as $col => $dir) {
    if (in_array($col, $validCols, true)) {
        $sortParts[] = "`$col` $dir";
    }
}
$sortParts[] = in_array('Weight', $validCols, true) ? '`Weight` DESC' : '`id` ASC';
$orderBy = implode(', ', $sortParts);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id IN ($placeholders) ORDER BY $orderBy");
$stmt->execute($ids);
$rows = $stmt->fetchAll();

// Optional markup pricing: when the person supplies a markup % (mv),
// the copied text shows a recalculated rap%/price/total instead of
// the stone's own values — matches the reference program's "markup"
// mode exactly (mk=markup + mv=<percentage>).
$markupMode = (string)($_POST['mk'] ?? '') === 'markup';
$mv = (float)($_POST['mv'] ?? 0);

$detailsBaseUrl = full_url('/d.php');

$out = '';
foreach ($rows as $row) {
    $price = (float)($row['Price'] ?? 0);
    $weight = (float)($row['Weight'] ?? 0);
    $rap = (float)($row['Rap'] ?? 0);
    $rapOff = (float)($row['RapOff'] ?? 0);
    $amt = $price * $weight;

    $status = '';
    $avail = strtolower((string)($row['avail'] ?? ''));
    if (str_contains($avail, 'hold')) {
        $status = 'Hold';
    } elseif (str_contains($avail, 'memo')) {
        $status = 'Memo';
    }
    if ($hasNotForWeb && strcasecmp((string)($row['notforweb'] ?? ''), 'True') === 0) {
        $status = 'Hold';
    }

    $out .= 'Stone ID:' . ($row['StockNo'] ?? '') . "\r\n";
    $out .= implode(' ', array_filter([
        $row['Shape'] ?? '', $row['Weight'] ?? '', $row['Color'] ?? '', $row['Clarity'] ?? '',
        $row['CutGrade'] ?? '', $row['Polish'] ?? '', $row['Symmetry'] ?? '', $row['FluorescenceIntensity'] ?? '',
    ], fn($v) => trim((string)$v) !== '')) . ' ' . ($row['Lab'] ?? '') . "\r\n";

    if ($markupMode) {
        // Exactly the original program's markup formula: adjust the
        // displayed rap% by the supplied markup, then back-calculate
        // a price-per-carat and total from that adjusted rap%.
        $dispct = $rap + $mv;
        $disamt = ($dispct * $rapOff * -1) / 100;
        $mkprice = $rapOff - $disamt;
        $mkamt = $weight * $mkprice;
        $out .= number_format($dispct, 2) . ' % rap / ' . number_format($mkprice, 2) . " $ per carat\r\n";
        $out .= 'Total=' . number_format($mkamt, 2) . ' $' . ($status !== '' ? '  ' . $status : '') . "\r\n";
    } else {
        $dispct = $rap;
        $mkprice = $rapOff;
        $mkamt = $amt;
        $out .= number_format($rap, 2) . ' % rap / ' . number_format($price, 2) . " $ per carat\r\n";
        $out .= 'Total=' . number_format($amt, 2) . ' $' . ($status !== '' ? '  ' . $status : '') . "\r\n";
    }
    $out .= 'LOC: ' . ($row['location'] ?? '') . "\r\n";
    // Short share URL: a = the diamond's own id, base64-encoded.
    // (The self_short_urls log row above is still recorded for audit
    // purposes, matching the reference program, even though its id
    // isn't part of this shortened link.)
    $rowId = (string)$row['id'];
    $out .= $detailsBaseUrl . '?a=' . urlencode(base64_encode($rowId)) . "\r\n\r\n";

    // Persist the last-computed values back onto maindata, matching
    // the reference program exactly (mkprice/mkamt/mkdis reflect
    // whichever mode — plain or markup — just generated this text).
    if (in_array('mkprice', $validCols, true) && in_array('mkamt', $validCols, true) && in_array('mkdis', $validCols, true)) {
        $updStmt = get_db()->prepare(
            'UPDATE maindata SET mkprice = :mp, mkamt = :ma, mkdis = :md WHERE id = :id'
        );
        $updStmt->execute([':mp' => $mkprice, ':ma' => $mkamt, ':md' => $dispct, ':id' => $row['id']]);
    }
}

echo $out;
