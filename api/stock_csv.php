<?php
/**
 * api/stock_csv.php
 * Stock list as a CSV file, for customers with an API key (Admin > API).
 *
 *   https://<site>/api/stock_csv.php?apikey=<key>
 * or send the key in a header instead of the URL:
 *   X-API-Key: <key>
 *
 * Columns: the Results columns configured for APIKEY_CSV_USERTYPE_ID
 * (Standard User by default), with their labels as headings.
 * Rows: what that user type sees on the Results page — held stones are
 * left out according to Admin > Site Setup > Hold Restriction — sorted
 * like the Results page. See includes/apikey.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/apikey.php';

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer'); // the key may be in the URL

function stock_csv_fail(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message, "\n";
    exit;
}

$key = (string)($_SERVER['HTTP_X_API_KEY'] ?? $_GET['apikey'] ?? '');
try {
    $client = apikey_find($key);
} catch (Throwable $e) {
    error_log('stock_csv: key lookup failed: ' . $e->getMessage());
    stock_csv_fail(503, 'The stock list is temporarily unavailable. Please try again later.');
}
if ($client === null) {
    stock_csv_fail(401, $key === ''
        ? 'API key missing. Use the full link from your email, including ?apikey=…'
        : 'Invalid API key. It may have been replaced: please use the link from your most recent email, or contact us.');
}

try {
    $columns = apikey_csv_columns();
    if ($columns === []) {
        error_log('stock_csv: no active Results columns for user type ' . APIKEY_CSV_USERTYPE_ID);
        stock_csv_fail(503, 'The stock list is not set up yet. Please contact us.');
    }
    $where = apikey_csv_where();
    $orderBy = build_rsetup_order_by();
    $fieldList = implode(', ', array_map(fn($c) => '`' . $c['field'] . '`', $columns));

    // Stream rows straight from MySQL instead of loading the whole table
    // into memory. No other query may run until the loop finishes.
    $db = get_db();
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $stmt = $db->query("SELECT $fieldList FROM maindata WHERE $where ORDER BY $orderBy");
} catch (Throwable $e) {
    error_log('stock_csv: query failed: ' . $e->getMessage());
    stock_csv_fail(503, 'The stock list is temporarily unavailable. Please try again later.');
}

/**
 * Stop spreadsheet programs from running a cell as a formula (CSV
 * injection): text starting with = + @ or a tab, or a "-" that isn't a
 * number, gets a leading apostrophe.
 */
function stock_csv_cell(mixed $value): string
{
    $text = (string)($value ?? '');
    if ($text !== '' && (strpbrk($text[0], "=+@\t\r") !== false || ($text[0] === '-' && !is_numeric($text)))) {
        return "'" . $text;
    }
    return $text;
}

$setup = get_setup() ?? [];
$prefix = preg_replace('/[^A-Za-z0-9]+/', '_', trim((string)($setup['company'] ?? ''))) ?: 'stock';
$filename = trim($prefix, '_') . '_stock_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 marker so Excel shows accents correctly
fputcsv($out, array_map(fn($c) => stock_csv_cell($c['label']), $columns), ',', '"', '');
while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
    fputcsv($out, array_map('stock_csv_cell', $row), ',', '"', '');
}
fclose($out);
