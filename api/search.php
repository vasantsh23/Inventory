<?php
/**
 * POST /api/search.php
 *   {"filters": {"Shape": ["BR"], "Weight_from": "1.00", ...},
 *    "page": 1, "perPage": 50}
 * `filters` uses exactly the keys/values the website's search form
 * posts as f[...]. Returns one page of result cards plus the total
 * count and a human-readable summary of the filters applied.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('POST');
api_require_user();

$body = api_body();
$filters = api_sanitize_filters($body['filters'] ?? []);
$page = max(1, (int)($body['page'] ?? 1));
$choices = rows_per_page_choices();
$perPage = (int)($body['perPage'] ?? 50);
if (!in_array($perPage, $choices, true)) {
    $perPage = in_array(50, $choices, true) ? 50 : $choices[0];
}

// Keep the website's "Back to Search" in sync with the app's last search.
$_SESSION['ds_last_filters'] = $filters;

$columns = api_results_columns();
if ($columns === []) {
    api_json([
        'total' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 1,
        'columns' => [], 'rows' => [], 'appliedFilters' => [],
        'notice' => 'No result columns are configured yet. Ask an administrator to enable columns in the Results table.',
    ]);
}

[$where, $params] = build_maindata_search_where($filters);

$countStmt = get_db()->prepare("SELECT COUNT(*) AS c FROM maindata WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['c'];
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);

$stmt = get_db()->prepare(
    'SELECT ' . api_results_field_list($columns) . " FROM maindata WHERE $where ORDER BY " . build_rsetup_order_by() . ' LIMIT :lim OFFSET :off'
);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();

api_json([
    'total'          => $total,
    'page'           => $page,
    'perPage'        => $perPage,
    'totalPages'     => $totalPages,
    'columns'        => $columns,
    'rows'           => array_map(fn($r) => api_result_row($r, $columns), $stmt->fetchAll()),
    'appliedFilters' => build_applied_filters_summary($filters),
]);
