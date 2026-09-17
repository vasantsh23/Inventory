<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/crud_engine.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';
require_once __DIR__ . '/../../includes/auth.php';

require_module_access('admin');

$table = (string)($_GET['table'] ?? '');
crud_assert_table($table);

$data = crud_export_data($table);
$meta = get_table_meta($table);
$headers = array_map(fn($c) => $meta['columns'][$c]['label'], $data['headers']);

XlsxWriter::download($table . '-export-' . date('Ymd-His') . '.xlsx', $headers, $data['rows']);
