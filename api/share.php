<?php
/**
 * POST /api/share.php {"ids": [..], "markupPct": 2.5 (optional)}
 * The same text the website's "Copy" button produces (specs, price,
 * location and a share link per stone), for Android's share sheet.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/diamond_copy.php';

api_require_method('POST');
api_require_user();

$body = api_body();
$ids = api_ids($body['ids'] ?? []);
if ($ids === []) {
    api_error(400, 'Please select at least one stone to share.', 'bad_request');
}
$markup = $body['markupPct'] ?? null;
$markupMode = is_numeric($markup);

api_json(['text' => ds_generate_copy_text($ids, $markupMode, $markupMode ? (float)$markup : 0.0)]);
