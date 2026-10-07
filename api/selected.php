<?php
/**
 * POST /api/selected.php {"ids": [..]}
 * Result cards for specific stones — used for "View Selected" when the
 * site runs without a cart (setup.loginscrn = 'no'), where the app
 * keeps the selection on the device.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';

api_require_method('POST');
api_require_user();

$ids = api_ids(api_body()['ids'] ?? []);
$columns = api_results_columns();
api_json([
    'columns' => $columns,
    'rows'    => api_rows_by_ids($ids, $columns),
]);
