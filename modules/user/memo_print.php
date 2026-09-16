<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';

$level = require_memo_level();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please go back to Results and try again.');
}

$idsRaw = (string)($_POST['ids'] ?? '');
$ids = array_values(array_unique(array_filter(
    array_map('trim', explode(',', $idsRaw)),
    fn($v) => $v !== '' && ctype_digit($v)
)));
$customerId = (string)($_POST['customer_id'] ?? '');
$copyopt = (int)($_POST['copyopt'] ?? 0);

if ($ids === [] || $customerId === '' || !in_array($copyopt, [1, 3], true)) {
    http_response_code(400);
    exit('Missing or invalid selection — please go back to Results, select at least one row and a customer, then try again.');
}
if ($copyopt === 3 && count($ids) > 6) {
    http_response_code(400);
    exit('Memo-3 (3 copies per page) is limited to 6 rows. Please go back and select 6 or fewer.');
}

$custStmt = get_db()->prepare('SELECT custnm, address FROM customer WHERE custid = ?');
$custStmt->execute([$customerId]);
$customer = $custStmt->fetch();
if (!$customer) {
    http_response_code(400);
    exit('The selected customer could not be found — please go back and select one again.');
}

// Level 4 uses `dmemo` for the footer/signature block, level 5 uses `memo`.
$footerTable = $level === 4 ? 'dmemo' : 'memo';
$footerRow = get_db()->query("SELECT * FROM `$footerTable` ORDER BY id DESC LIMIT 1")->fetch();
$footer = $footerRow ?: ['company' => '', 'address' => '', 'telno' => '', 'fax' => '', 'gsm' => '', 'email' => '', 'web' => '',
    'field1' => '', 'field2' => '', 'field3' => '', 'field4' => '', 'field5' => '', 'field6' => ''];

$validCols = get_maindata_columns();
$wantedFields = ['id', 'StockNo', 'Shape', 'Weight', 'Color', 'Clarity', 'Lab', 'CutGrade', 'Polish', 'Symmetry',
    'FluorescenceIntensity', 'Price', 'RapOff', 'Rap', 'CertificateNo'];
$wantedFields = array_values(array_intersect($wantedFields, $validCols));
$fieldList = implode(', ', array_map(fn($f) => "`$f`", $wantedFields));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = get_db()->prepare("SELECT $fieldList FROM maindata WHERE id IN ($placeholders) ORDER BY id ASC");
$stmt->execute($ids);
$diamondRows = $stmt->fetchAll();

/**
 * Per-row display values, replicating the exact formatting rules
 * from the original memo program: discount shown with a +/- sign
 * (blank if zero), amount blank if zero, price/Rapaport rounded only
 * when price > 0, "Make" is Cut+Polish+Symmetry+Fluorescence joined.
 */
function memo_row_calc(array $row): array
{
    $price = (float)($row['Price'] ?? 0);
    $weight = (float)($row['Weight'] ?? 0);
    $rapOff = (float)($row['RapOff'] ?? 0);
    $rap = (float)($row['Rap'] ?? 0);
    $amtx = $price * $weight;

    if ($rapOff < 0) {
        $discDisplay = number_format($rapOff, 2);
    } elseif ($rapOff == 0.0) {
        $discDisplay = '';
    } else {
        $discDisplay = '+' . number_format($rapOff, 2);
    }

    $amtDisplay = $amtx == 0.0 ? '' : number_format($amtx, 2);
    $ctsDisplay = $weight == 0.0 ? '' : number_format($weight, 2);
    $priceDisplay = $price == 0.0 ? '' : (string)round($price);
    $rapDisplay = $price > 0 ? (string)round($rap) : ($rap == 0.0 ? '' : (string)$rap);
    $certRaw = trim((string)($row['CertificateNo'] ?? ''));
    $certDisplay = ($certRaw === '' || $certRaw === '0') ? '' : $certRaw;

    $makeParts = array_filter(
        [$row['CutGrade'] ?? '', $row['Polish'] ?? '', $row['Symmetry'] ?? '', $row['FluorescenceIntensity'] ?? ''],
        fn($v) => trim((string)$v) !== ''
    );
    $make = implode(' ', $makeParts);

    return [
        'stockno' => (string)($row['StockNo'] ?? ''),
        'shape'   => (string)($row['Shape'] ?? ''),
        'cts'     => $ctsDisplay,
        'color'   => (string)($row['Color'] ?? ''),
        'clarity' => (string)($row['Clarity'] ?? ''),
        'lab'     => (string)($row['Lab'] ?? ''),
        'make'    => $make,
        'price'   => $priceDisplay,
        'disc'    => $discDisplay,
        'amt'     => $amtDisplay,
        'rap'     => $rapDisplay,
        'cert'    => $certDisplay,
        'amtx_raw'    => $amtx,
        'weight_raw'  => $weight,
    ];
}

$logoPath = get_logo_path();
$logoUrl = preg_match('#^https?://#i', $logoPath) ? $logoPath : asset_url($logoPath);

/** Renders one full memo copy (header, item table, totals, footer).
 * $skipHeader drops the entire masthead block (logo/MEMORANDUM/
 * company info/date/to/commission line) — used for the 3rd copy in
 * Memo-3 mode, which repeats only the item table + totals + footer. */
function render_memo_copy(array $rows, array $customer, array $footer, array $company, int $padToRows, bool $skipHeader = false, bool $faintFooter = false): void
{
    $tcts = 0.0;
    $tamt = 0.0;
    $logoMax = '80px';
    $logoMaxH = '55px';
    $h2Size = '13px';
    $faintClass = $faintFooter ? ' class="memo3-faint-border"' : '';

    // Masthead line 2: Tel / Fax / GSM, only the parts that are set.
    $line2Parts = [];
    if ($company['telno'] !== '') { $line2Parts[] = 'Tel.: ' . e($company['telno']); }
    if ($company['fax'] !== '') { $line2Parts[] = 'Fax :' . e($company['fax']); }
    if ($company['gsm'] !== '') { $line2Parts[] = 'GSM : ' . e($company['gsm']); }
    $line2 = implode(' &nbsp; ', $line2Parts);

    // Masthead line 3: Email / Web, only the parts that are set.
    $line3Parts = [];
    if ($company['email'] !== '') { $line3Parts[] = 'Email : ' . e($company['email']) . '.'; }
    if ($company['web'] !== '') { $line3Parts[] = 'Web.: ' . e($company['web']); }
    $line3 = implode(' &nbsp; ', $line3Parts);

    if (!$skipHeader):
    ?>
    <table class="memo-header-table">
        <tr>
            <td style="width:18%;"><?php if ($company['logoUrl']): ?><img src="<?= e($company['logoUrl']) ?>" alt="" style="max-width:<?= $logoMax ?>;max-height:<?= $logoMaxH ?>;"><?php endif; ?></td>
            <td style="width:24%;"><h2 style="margin:0;font-size:<?= $h2Size ?>;">MEMORANDUM</h2></td>
            <td style="text-align:right;">
                <?= e($company['address']) ?><br>
                <?= $line2 !== '' ? $line2 . '<br>' : '' ?>
                <?= $line3 ?>
            </td>
        </tr>
        <tr><td colspan="3" style="text-align:right;">Date: <?= e(date('d/m/Y H:i:s')) ?></td></tr>
        <tr><td colspan="3">To <u><?= e($customer['custnm']) ?></u>,<br>Received on COMMISSION base, the following goods from Firm <strong><?= e($company['name']) ?></strong>.</td></tr>
    </table>
    <?php endif; ?>
    <table class="memo-items-table">
        <tr>
            <th>SNo</th><th>Stone Id</th><th>Shape</th><th>Cts</th><th>Color</th><th>Clarity</th>
            <th>Lab</th><th>Make</th><th>Per/ct USD</th><th>Disc%</th><th>Amt USD</th><th>Rapaport</th><th>Cert</th>
        </tr>
        <?php $sno = 1; foreach ($rows as $row): $c = memo_row_calc($row); $tcts += $c['weight_raw']; $tamt += $c['amtx_raw']; ?>
            <tr>
                <td style="text-align:center;"><?= $sno++ ?></td>
                <td style="text-align:center;"><?= e($c['stockno']) ?></td>
                <td style="text-align:center;"><?= e($c['shape']) ?></td>
                <td style="text-align:center;"><?= e($c['cts']) ?></td>
                <td style="text-align:center;"><?= e($c['color']) ?></td>
                <td style="text-align:center;"><?= e($c['clarity']) ?></td>
                <td style="text-align:center;"><?= e($c['lab']) ?></td>
                <td style="text-align:center;" class="memo-make-cell"><?= e($c['make']) ?></td>
                <td style="text-align:center;"><?= e($c['price']) ?></td>
                <td style="text-align:center;"><?= e($c['disc']) ?></td>
                <td style="text-align:center;"><?= e($c['amt']) ?></td>
                <td style="text-align:center;"><?= e($c['rap']) ?></td>
                <td style="text-align:center;"><?= e($c['cert']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($padToRows > 0 && count($rows) < $padToRows): ?>
            <?php for ($p = 0; $p < $padToRows - count($rows); $p++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        <?php endif; ?>
        <tr<?= $faintClass ?>>
            <td></td><td>Total</td><td>Carats:</td><td><?= number_format($tcts, 2) ?></td>
            <td></td><td></td><td></td><td></td><td></td>
            <td>Total USD:</td><td><?= number_format($tamt, 2) ?></td><td></td><td></td>
        </tr>
        <tr<?= $faintClass ?>>
            <td colspan="7"><?= e($footer['field1']) ?>&nbsp;&nbsp;<?= e($customer['custnm']) ?></td>
            <td colspan="6" style="text-align:right;"><?= e($footer['field2']) ?></td>
        </tr>
        <tr<?= $faintClass ?>>
            <td colspan="7"><?= e($footer['field3']) ?></td>
            <td colspan="6" style="text-align:right;"><?= e($footer['field4']) ?></td>
        </tr>
        <tr<?= $faintClass ?>>
            <td colspan="7"></td>
            <td colspan="6"><?= e($footer['field5']) ?></td>
        </tr>
        <tr<?= $faintClass ?>>
            <td colspan="13"><strong><?= $footer['field6'] ?></strong></td>
        </tr>
    </table>
    <?php
}

$pageTitle = 'Memo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Memo — <?= e($customer['custnm']) ?></title>
<style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9px;
        color: #000;
        background: #e6e6e6; /* on-screen "paper on desk" backdrop, removed for print */
    }
    .memo-page {
        width: 210mm;
        min-height: 297mm;
        margin: 16px auto;
        background: #fff;
        padding: 8mm;
        box-shadow: 0 4px 24px rgba(0,0,0,0.25);
    }
    table { border-collapse: collapse !important; width: 100%; margin: 0 0 4px; }
    table, tr, td, th { border: 1.5px solid #000 !important; }
    td, th { padding: 1px 4px; }
    /* Faint gray borders for the Total row + footer rows on the 2nd
       and 3rd copies of a Memo-3 printout — matches the original
       program exactly, which keeps the actual stone rows solid black
       in every copy but de-emphasizes the totals/receipt/terms
       section on the repeated copies. */
    tr.memo3-faint-border td,
    tr.memo3-faint-border th {
        border-color: #d8d8d8 !important;
    }
    /* The combined Cut/Polish/Symmetry/Fluorescence "Make" column
       needs a smaller size so it doesn't force the row too tall. */
    .memo-make-cell { font-size: 10px; line-height: 1.1; white-space: nowrap; }
    @media print {
        table, tr, td, th { border: 1.5px solid #000 !important; color: #000 !important; }
        tr.memo3-faint-border td,
        tr.memo3-faint-border th { border-color: #d8d8d8 !important; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
    .memo-header-table td { vertical-align: top; line-height: 1.15; }
    .memo-toolbar { text-align: center; margin: 0 0 16px; }
    .memo-toolbar button { padding: 8px 22px; font-size: 0.95rem; cursor: pointer; }
    @media print {
        .memo-toolbar { display: none; }
        body { background: #fff; }
        .memo-page { box-shadow: none; margin: 0; width: auto; min-height: 0; padding: 0; }
    }
</style>
</head>
<body>
    <div class="memo-toolbar"><button type="button" id="memoPrintBtn">Print</button></div>
    <div class="memo-page">
    <?php
    $company = [
        'name' => (string)($footer['company'] ?? ''),
        'address' => (string)($footer['address'] ?? ''),
        'telno' => (string)($footer['telno'] ?? ''),
        'fax' => (string)($footer['fax'] ?? ''),
        'gsm' => (string)($footer['gsm'] ?? ''),
        'email' => (string)($footer['email'] ?? ''),
        'web' => (string)($footer['web'] ?? ''),
        'logoUrl' => $logoUrl,
    ];
    if ($copyopt === 1) {
        render_memo_copy($diamondRows, $customer, $footer, $company, 0, false, false);
    } else {
        for ($copy = 0; $copy < 3; $copy++) {
            render_memo_copy($diamondRows, $customer, $footer, $company, 6, $copy === 2, $copy >= 1);
        }
    }
    ?>
    </div>
    <script src="<?= e(asset_url_versioned('/assets/js/memo_print.js')) ?>"></script>
</body>
</html>
