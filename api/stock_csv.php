<?php
/**
 * api/stock_csv.php
 * Stock list download for customers with an API key (Admin > API).
 *
 * Opening https://<site>/api/stock_csv.php shows a form; the customer
 * types their API key and clicks Download. The key is sent in the form
 * body (POST), never in the URL, so it doesn't end up in browser
 * history, bookmarks or server logs.
 *
 * Software can skip the form by sending the key in a header:
 *   X-API-Key: <key>
 * Keys in the URL (?apikey=…) are not accepted.
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

/**
 * Send the CSV for a valid key and stop. Returns an error message
 * instead if the stock list can't be produced.
 */
function stock_csv_send(): string
{
    try {
        $columns = apikey_csv_columns();
        if ($columns === []) {
            error_log('stock_csv: no active Results columns for user type ' . APIKEY_CSV_USERTYPE_ID);
            return 'The stock list is not set up yet. Please contact us.';
        }
        $where = apikey_csv_where();
        $orderBy = build_rsetup_order_by();
        $fieldList = implode(', ', array_map(fn($c) => '`' . $c['field'] . '`', $columns));

        // Stream rows straight from MySQL instead of loading the whole
        // table into memory. No other query may run until the loop ends.
        $db = get_db();
        $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $stmt = $db->query("SELECT $fieldList FROM maindata WHERE $where ORDER BY $orderBy");
    } catch (Throwable $e) {
        error_log('stock_csv: query failed: ' . $e->getMessage());
        return 'The stock list is temporarily unavailable. Please try again later.';
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
    exit;
}

/** Look a key up; on a wrong key, wait a moment to slow down guessing. */
function stock_csv_check_key(string $key): ?array
{
    try {
        $client = apikey_find($key);
    } catch (Throwable $e) {
        error_log('stock_csv: key lookup failed: ' . $e->getMessage());
        return null;
    }
    if ($client === null) {
        sleep(1);
    }
    return $client;
}

// --- Software: key in the X-API-Key header, no form -------------------------
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $message = stock_csv_check_key((string)$_SERVER['HTTP_X_API_KEY']) === null
        ? 'Invalid API key.'
        : stock_csv_send();
    http_response_code($message === 'Invalid API key.' ? 401 : 503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message, "\n";
    exit;
}

// --- People: the form -------------------------------------------------------
$error = '';
$notice = '';
if (isset($_GET['apikey'])) {
    $notice = 'For security, API keys are no longer accepted in links. Please type your key below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim((string)($_POST['apikey'] ?? ''));
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'This page expired. Please enter your API key again.';
    } elseif ($key === '') {
        $error = 'Please enter your API key.';
    } elseif (stock_csv_check_key($key) === null) {
        $error = 'Invalid API key. It may have been replaced: please use the key from your most recent email, or contact us.';
    } else {
        $error = stock_csv_send(); // only returns if something went wrong
    }
}

$useDefaultTheme = true; // same look as the login screen
require_once __DIR__ . '/../includes/header.php';
?>
    <section class="login-section">
        <h1>Download stock list</h1>
        <p>Enter the API key from your email to download our current stock list as a CSV file (opens in Excel).</p>

        <?php if ($notice !== '' && $error === ''): ?>
            <div class="alert alert-warning" role="status"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(asset_url('/api/stock_csv.php')) ?>" class="login-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="apikey">API key</label>
            <input type="text" id="apikey" name="apikey" maxlength="25" required autofocus
                   autocomplete="off" autocapitalize="off" spellcheck="false">
            <button type="submit">Download</button>
        </form>
    </section>
<?php
require_once __DIR__ . '/../includes/footer.php';
