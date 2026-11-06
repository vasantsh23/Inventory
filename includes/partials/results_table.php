<?php
/**
 * includes/partials/results_table.php
 * The stones table shared by Results and View Cart / View Selected:
 * the "Total Stones | Selected | Total Weight | Total Amount" line,
 * "Select All", and the bordered table with a blue header and the
 * four media icons (video, hand video, info video, certificate) as
 * their own columns right after Stock#.
 *
 * Columns, their order and their labels still come from the `results`
 * table for the viewer's user type (get_results_columns()).
 */
declare(strict_types=1);

require_once __DIR__ . '/media_icons.php';

/** Fields every table query must fetch besides the visible columns:
 * StockNo colour (avail/notforweb), certificate link (Lab,
 * CertificateNo, StockNo), media path placeholders, and Weight/totamt
 * for the Selected totals. */
function results_table_extra_fields(array $columns): array
{
    $valid = get_maindata_columns();
    return array_values(array_diff(
        array_intersect(
            array_unique(array_merge(['avail', 'notforweb', 'Lab', 'CertificateNo', 'StockNo', 'Weight', 'totamt'], media_placeholder_fields())),
            $valid
        ),
        array_column($columns, 'field')
    ));
}

/**
 * @param array $opts total (int), persist (bool: remember ticks across
 *                    pages — Results only), reset (bool: start with no
 *                    ticks — a fresh search), toolbarExtra (HTML shown
 *                    beside "Select All", already escaped)
 */
function render_results_table(array $columns, array $rows, array $mediaLinks, array $opts = []): void
{
    $total = (int)($opts['total'] ?? count($rows));
    $persist = !empty($opts['persist']);
    $reset = !empty($opts['reset']);
    $emptyRow = ['video' => null, 'handvideo' => null, 'infovideo' => null, 'cert' => null];
    $colCount = 1 + count($columns) + (in_array('StockNo', array_column($columns, 'field'), true) ? 4 : 0);
    ?>
    <div class="rs-summary" aria-live="polite">
        Total Stones : <strong><?= number_format($total) ?></strong>
        | Selected : <strong id="rsSelCount">0</strong>
        | Total Weight : <strong id="rsSelWeight">0.00</strong>
        | Total Amount: <strong id="rsSelAmount">0.00</strong>
    </div>
    <div class="rs-selectall-row">
        <label class="rs-selectall"><input type="checkbox" id="memoSelectAll"> Select All</label>
        <?= $opts['toolbarExtra'] ?? '' ?>
    </div>

    <div class="results-table-wrap">
        <table class="data-table results-table rs-table"<?= $persist ? ' data-persist-selection="1"' : '' ?><?= $reset ? ' data-reset-selection="1"' : '' ?>>
            <thead>
                <tr>
                    <th class="results-checkbox-col"><span class="rs-sr-only">Select</span></th>
                    <?php foreach ($columns as $col): ?>
                        <th><?= e($col['label']) ?></th>
                        <?php if ($col['field'] === 'StockNo'): ?>
                            <?= render_media_cells(null) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= $colCount ?>" class="results-empty">No diamonds match your criteria.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row):
                    $stockNoBg = get_stockno_bg_color($row['avail'] ?? null, $row['notforweb'] ?? null);
                    $rowMedia = $mediaLinks[(string)$row['id']] ?? $emptyRow;
                    $rowCertUrl = $rowMedia['cert'];
                    $weight = is_numeric($row['Weight'] ?? null) ? (float)$row['Weight'] : 0.0;
                    $amount = isset($row['totamt']) && is_numeric($row['totamt']) ? ds_display_amount($row['totamt']) : 0.0;
                ?>
                    <tr>
                        <td class="results-checkbox-col" data-label="Select">
                            <input type="checkbox" class="memo-row-select" value="<?= e((string)$row['id']) ?>"
                                   data-stockno="<?= e((string)($row['StockNo'] ?? '')) ?>"
                                   data-weight="<?= e((string)$weight) ?>" data-amount="<?= e((string)$amount) ?>"
                                   aria-label="Select <?= e((string)($row['StockNo'] ?? '')) ?>">
                        </td>
                        <?php foreach ($columns as $col):
                            $f = $col['field'];
                        ?>
                            <?php if ($f === 'StockNo'): ?>
                                <td class="rs-stockno" data-label="<?= e($col['label']) ?>"<?= $stockNoBg !== null ? ' style="background-color:' . e($stockNoBg) . ';"' : '' ?>>
                                    <a class="results-stockno-link" href="<?= e(asset_url('/modules/user/diamond_details.php?id=' . urlencode((string)$row['id']))) ?>"><?= e((string)($row[$f] ?? '')) ?></a>
                                </td>
                                <?= render_media_cells($rowMedia) ?>
                            <?php elseif ($f === 'CertificateNo'):
                                $certDisplay = format_certificate_no_display($row['CertificateNo'] ?? null);
                            ?>
                                <td data-label="<?= e($col['label']) ?>">
                                    <?php if ($certDisplay !== '' && $rowCertUrl !== null): ?>
                                        <a class="rs-cert-link" href="<?= e($rowCertUrl) ?>" target="_blank" rel="noopener" title="View certificate"><?= e($certDisplay) ?></a>
                                    <?php else: ?>
                                        <?= e($certDisplay) ?>
                                    <?php endif; ?>
                                </td>
                            <?php elseif ($f === 'Measurements'): ?>
                                <td data-label="<?= e($col['label']) ?>"><?= e(format_measurements_display($row['Measurements'] ?? null)) ?></td>
                            <?php elseif (strcasecmp($f, 'location') === 0): ?>
                                <td class="results-flag-col" data-label="<?= e($col['label']) ?>"><?= render_location_flag(isset($row[$f]) ? (string)$row[$f] : null) ?></td>
                            <?php elseif ($f === 'totamt'): ?>
                                <td data-label="<?= e($col['label']) ?>"><?= (isset($row['totamt']) && is_numeric($row['totamt'])) ? e(number_format($amount, 2)) : '' ?></td>
                            <?php else: ?>
                                <td data-label="<?= e($col['label']) ?>"><?= e((string)($row[$f] ?? '')) ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Small white icon for the blue toolbar buttons (decorative). */
function rs_icon(string $name): string
{
    $paths = [
        'xls'    => '<path d="M6 2h8l5 5v15H6zm7 1.5V8h4.5zM8.6 11l1.9 3-1.9 3h1.6l1.1-1.9 1.1 1.9H14l-1.9-3 1.9-3h-1.6l-1.1 1.9-1.1-1.9z" fill-rule="evenodd"/>',
        'filter' => '<path d="M3 4h18l-7 8.5V19l-4 2v-8.5z"/>',
        'cart'   => '<path d="M2 3h3l2.2 10.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.9-1.4L22 7H6.6l-.5-2.4A2 2 0 0 0 4.1 3zM9.5 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
        'copy'   => '<path d="M8 2h9l4 4v12H8zm8 1.5V7h3.5zM3 6h3v14h10v2H3z" fill-rule="evenodd"/>',
        'eye'    => '<path d="M12 5c5 0 9 4.5 10 7-1 2.5-5 7-10 7S3 14.5 2 12c1-2.5 5-7 10-7zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4z" fill-rule="evenodd"/>',
        'back'   => '<path d="M10 5 3 12l7 7v-4.5h11v-5H10z"/>',
        'trash'  => '<path d="M9 3h6l1 2h4v2H4V5h4zm-3 6h12l-1 12H7z"/>',
    ];
    return '<svg class="rs-btn-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}
