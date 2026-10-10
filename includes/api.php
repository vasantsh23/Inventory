<?php
/**
 * api.php
 * Shared bootstrap for the JSON endpoints under /api/, used by the
 * Android app. Reuses the web app's existing session + "remember me"
 * login (auth.php), search query builder and result formatting, so the
 * app always sees exactly what the website shows.
 *
 * Security model
 * - Authentication: the normal PHP session cookie plus the existing
 *   30-day "remember me" cookie (selector/validator, rotated on use).
 *   The app stores both in its private cookie store.
 * - Cross-site request forgery: every endpoint requires the custom
 *   header `X-Requested-With: DiamondApp`. A browser can only attach a
 *   custom header to a cross-site request after a CORS preflight, and
 *   these endpoints never send any Access-Control-Allow-* headers, so
 *   no other website can make a logged-in visitor's browser call them.
 *   (The website's own forms keep using their csrf_token as before.)
 * - Input: request bodies are JSON, size-limited, and every filter
 *   value is reduced to strings / lists of strings before it reaches
 *   build_maindata_search_where(), which already validates column
 *   names and binds every value.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/diamond_search_query.php';

const API_VERSION = 1;
const API_MAX_BODY_BYTES = 256 * 1024;
const API_MAX_IDS = 1000;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

set_exception_handler(function (Throwable $e): void {
    error_log('API error: ' . $e);
    api_error(500, 'Something went wrong on the server. Please try again.');
});

/** Send a JSON response and stop. */
function api_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/** Send a JSON error ({"error": {...}}) and stop. */
function api_error(int $status, string $message, string $code = 'error'): void
{
    api_json(['error' => ['code' => $code, 'message' => $message]], $status);
}

/** Reject any request that didn't come from the app (see header comment). */
function api_require_app_header(): void
{
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'DiamondApp') {
        api_error(403, 'This endpoint is only available to the mobile app.', 'forbidden');
    }
}

function api_require_method(string ...$methods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        api_error(405, 'Method not allowed.', 'method_not_allowed');
    }
}

/** The decoded JSON request body (an object), or [] when empty. */
function api_body(): array
{
    $raw = file_get_contents('php://input', false, null, 0, API_MAX_BODY_BYTES + 1);
    if ($raw === false || $raw === '') {
        return [];
    }
    if (strlen($raw) > API_MAX_BODY_BYTES) {
        api_error(413, 'Request is too large.', 'too_large');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_error(400, 'Request body must be a JSON object.', 'bad_request');
    }
    return $data;
}

/**
 * Same rule as require_module_access('user'), but answering with JSON
 * (401 / 403) instead of an HTML redirect to the login page.
 */
function api_require_user(): void
{
    if (!is_logged_in() && is_guest_browsing_enabled()) {
        return; // setup.loginscrn = 'no': the user module is open to guests
    }
    if (!is_logged_in()) {
        api_error(401, 'Please sign in to continue.', 'unauthenticated');
    }
    $allowed = modules_for_level((int)(current_user()['level'] ?? 0));
    if (!in_array('user', $allowed, true)) {
        api_error(403, 'Your account does not have access to diamond search.', 'forbidden');
    }
}

/** A validated, de-duplicated list of maindata ids (digit strings). */
function api_ids(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $ids = [];
    foreach ($raw as $v) {
        $v = trim((string)(is_scalar($v) ? $v : ''));
        if ($v !== '' && ctype_digit($v)) {
            $ids[$v] = true;
        }
    }
    return array_slice(array_keys($ids), 0, API_MAX_IDS);
}

/**
 * Reduce an untrusted filters object to the exact shape the web form
 * posts as $_POST['f']: each key maps to a string or a list of strings.
 * Anything else (nested objects, numbers in odd places) is dropped or
 * stringified, so build_maindata_search_where() never sees surprises.
 */
function api_sanitize_filters(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $key => $value) {
        $key = (string)$key;
        if ($key === '' || strlen($key) > 100) {
            continue;
        }
        // Range / text keys are always single values, never lists.
        $mustBeScalar = (bool)preg_match('/_(from|to|text)$/', $key);
        if (is_array($value)) {
            if ($mustBeScalar) {
                continue;
            }
            $list = [];
            foreach ($value as $item) {
                if (is_scalar($item)) {
                    $s = (string)$item;
                    if (strlen($s) <= 255) {
                        $list[] = $s;
                    }
                }
            }
            if ($list !== []) {
                $out[$key] = array_values(array_unique($list));
            }
        } elseif (is_scalar($value)) {
            $s = trim((string)$value);
            if ($s !== '' && strlen($s) <= 255) {
                $out[$key] = $s;
            }
        }
    }
    return $out;
}

/** Turn a site-relative path into an absolute URL the app can load. */
function api_absolute_url(?string $pathOrUrl): ?string
{
    $pathOrUrl = trim((string)$pathOrUrl);
    if ($pathOrUrl === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $pathOrUrl)) {
        return $pathOrUrl;
    }
    // Paths from asset_url() already include BASE_URL; avoid doubling it.
    if (BASE_URL !== '' && str_starts_with($pathOrUrl, BASE_URL . '/')) {
        $pathOrUrl = substr($pathOrUrl, strlen(BASE_URL));
    }
    return full_url($pathOrUrl);
}

/** Columns for the Results / Cart screens, in the shape the app expects. */
function api_results_columns(): array
{
    return array_map(fn($c) => ['field' => $c['field'], 'label' => $c['label']], get_results_columns());
}

/**
 * SELECT list for result rows: the configured columns, plus the fields
 * the app always needs for the stock-number colour, certificate link
 * and card headline, whether or not they're visible columns.
 */
function api_results_field_list(array $columns): string
{
    $validCols = get_maindata_columns();
    $always = ['StockNo', 'avail', 'notforweb', 'Lab', 'CertificateNo',
        'Shape', 'Weight', 'Color', 'Clarity', 'CutGrade', 'Polish', 'Symmetry', 'FluorescenceIntensity', 'totamt'];
    $fields = array_values(array_unique(array_merge(
        array_column($columns, 'field'),
        array_intersect($always, $validCols),
        media_placeholder_fields() // {placeholders} used in the `path` table
    )));
    $list = '`id`';
    foreach ($fields as $f) {
        $list .= ', `' . $f . '`';
    }
    return $list;
}

/** Display value for one result cell — same formatting as results.php. */
function api_cell_value(array $row, string $field): string
{
    return match ($field) {
        'CertificateNo' => format_certificate_no_display($row['CertificateNo'] ?? null),
        'Measurements'  => format_measurements_display($row['Measurements'] ?? null),
        'totamt'        => number_format(ds_display_amount($row['totamt'] ?? 0), 2),
        default         => (string)($row[$field] ?? ''),
    };
}

/** One maindata row as the app's result card. */
function api_result_row(array $row, array $columns): array
{
    $headlineParts = [];
    foreach (['Shape', 'Weight', 'Color', 'Clarity'] as $f) {
        $v = trim((string)($row[$f] ?? ''));
        if ($v !== '') {
            $headlineParts[] = $v;
        }
    }
    $gradeParts = [];
    foreach (['CutGrade', 'Polish', 'Symmetry', 'FluorescenceIntensity', 'Lab'] as $f) {
        $v = trim((string)($row[$f] ?? ''));
        if ($v !== '') {
            $gradeParts[] = $v;
        }
    }
    $amount = array_key_exists('totamt', $row) && $row['totamt'] !== null
        ? number_format(ds_display_amount($row['totamt']), 2)
        : null;

    return [
        'id'          => (int)$row['id'],
        'stockNo'     => (string)($row['StockNo'] ?? ''),
        'stockNoBg'   => get_stockno_bg_color($row['avail'] ?? null, $row['notforweb'] ?? null),
        'avail'       => trim((string)($row['avail'] ?? '')) ?: null,
        'headline'    => implode(' ', $headlineParts),
        'grades'      => implode(' · ', $gradeParts),
        'amount'      => $amount,
        'certUrl'     => build_certificate_url($row['Lab'] ?? null, $row['CertificateNo'] ?? null, $row['StockNo'] ?? null, $row),
        'cells'       => array_map(fn($c) => api_cell_value($row, $c['field']), $columns),
    ];
}

/** Fetch result cards for a list of ids, in rsetup's configured sort order. */
function api_rows_by_ids(array $ids, array $columns): array
{
    if ($ids === [] || $columns === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = get_db()->prepare(
        'SELECT ' . api_results_field_list($columns) . " FROM maindata WHERE id IN ($placeholders) ORDER BY " . build_rsetup_order_by()
    );
    $stmt->execute($ids);
    return array_map(fn($r) => api_result_row($r, $columns), $stmt->fetchAll());
}

/** Logged-in user's email (the cart is keyed by it), or ''. */
function api_cart_email(): string
{
    return (string)(current_user()['emailid'] ?? '');
}

/** Number of distinct stones in this user's cart. */
function api_cart_count(string $emailid): int
{
    if ($emailid === '') {
        return 0;
    }
    $stmt = get_db()->prepare('SELECT COUNT(DISTINCT stockno) AS c FROM selection WHERE emailid = :e');
    $stmt->execute([':e' => $emailid]);
    return (int)$stmt->fetch()['c'];
}

api_require_app_header();
