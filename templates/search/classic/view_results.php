<?php
/**
 * templates/search/classic/view_results.php — Classic layout (the design
 * these pages had before the Solitaire Station redesign).
 * Included by modules/user/view_results.php after it has prepared all the
 * data (see includes/search_templates.php). Colours and fonts come
 * from Admin → Theme Settings.
 */
declare(strict_types=1);
if (!function_exists('search_template_view')) { http_response_code(404); exit; } // only via modules/user/*.php
$searchTemplatePage = true; // header.php: preview bar for admins

$pageTitle = $viewTitle;
$pageSubtitle = '';
$activeNav = 'diamond_search';
$wideContent = true; // this table can have many columns — use the full viewport width
$bodyClass = 'compact-footer no-footer-gap'; // tight footer, no band above it

require_once __DIR__ . '/../../../includes/header.php';
?>
    <section class="ds-page">
        <div class="ds-hero">
            <h1 class="ds-title"><?= e($viewTitle) ?></h1>
            <div class="ds-actions">
                <a class="btn" href="<?= e(asset_url('/modules/user/results.php')) ?>">&larr; Back to Results</a>
                <?php if ($total > 0): ?>
                    <a class="btn btn-accent" href="#" id="exportExcelBtn">Export to Excel</a>
                    <button type="button" class="btn" id="copyBtn">Copy</button>
                    <button type="button" class="btn" id="markupCopyBtn">Markup Copy</button>
                    <button type="button" class="btn" id="clearSelectionBtn">Clear Selection</button>
                    <?php if (!$showSelected): ?>
                        <form method="post" action="<?= e(asset_url('/modules/user/view_results.php')) ?>" style="display:inline;"
                              data-confirm="Clear your entire cart? This cannot be undone.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-danger">Clear Cart</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($total > 0): ?>
            <div class="copy-status" id="copyStatus" hidden></div>
            <div class="copy-markup-row" id="markupCopyRow" hidden>
                <label class="copy-markup-field">
                    Markup %
                    <input type="number" step="any" id="markupCopyInput" placeholder="e.g. 5" style="width:80px;">
                </label>
                <button type="button" class="btn btn-accent" id="markupCopyConfirmBtn">Copy</button>
            </div>
            <form method="post" action="<?= e(asset_url('/modules/user/copy_generate.php')) ?>" id="copyForm" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ids" id="copyFormIds">
                <input type="hidden" name="mk" id="copyFormMk">
                <input type="hidden" name="mv" id="copyFormMv">
            </form>
        <?php endif; ?>

        <?php if (($_GET['cleared'] ?? '') === '1'): ?>
            <div class="alert alert-success" style="margin-bottom:18px;">Your cart has been cleared.</div>
        <?php endif; ?>

        <?php if ($resultsError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($resultsError) ?></div>
        <?php elseif ($columns === []): ?>
            <div class="panel">
                <p class="panel-desc">No result columns are configured yet. An administrator can enable columns in the Results table (active = yes) to control what appears here.</p>
            </div>
        <?php elseif ($total === 0): ?>
            <div class="panel">
                <?php if (!$showSelected): ?>
                    <p class="panel-desc">Your cart is empty. Go to Results, select some rows, and click "Add to Cart".</p>
                <?php else: ?>
                    <p class="panel-desc">No diamonds selected. Go to Results, tick some rows, and click "View Selected".</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="results-count"><?= number_format($total) ?> diamond<?= $total === 1 ? '' : 's' ?> <?= !$showSelected ? 'in your cart' : 'selected' ?></p>

            <div class="results-table-wrap">
                <table class="data-table results-table">
                    <thead>
                        <tr>
                            <th class="results-checkbox-col"><input type="checkbox" id="memoSelectAll" title="Select all"></th>
                            <?php foreach ($columns as $col): ?>
                                <th><?= e($col['label']) ?></th>
                                <?php if ($col['field'] === 'StockNo'): ?>
                                    <th class="results-media-col"><?= render_media_icons(null) ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="results-checkbox-col" data-label="Select"><input type="checkbox" class="memo-row-select" value="<?= e((string)$row['id']) ?>"></td>
                                <?php
                                $stockNoBg = get_stockno_bg_color($row['avail'] ?? null, $row['notforweb'] ?? null);
                                $rowMedia = $mediaLinks[(string)$row['id']] ?? ['image' => null, 'video' => null, 'handvideo' => null, 'infovideo' => null, 'cert' => null];
                                $rowCertUrl = $rowMedia['cert'];
                                ?>
                                <?php foreach ($columns as $col): ?>
                                    <?php if ($col['field'] === 'StockNo'): ?>
                                        <td data-label="<?= e($col['label']) ?>"<?= $stockNoBg !== null ? ' style="background-color:' . e($stockNoBg) . ';"' : '' ?>>
                                            <a class="results-stockno-link" href="<?= e(asset_url('/modules/user/diamond_details.php?id=' . urlencode((string)$row['id']))) ?>"><?= e((string)($row[$col['field']] ?? '')) ?></a>
                                        </td>
                                        <td class="results-media-col" data-label="Media"><?= render_media_icons($rowMedia) ?></td>
                                    <?php elseif ($col['field'] === 'CertificateNo'):
                                        $certDisplay = format_certificate_no_display($row['CertificateNo'] ?? null);
                                    ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?php if ($certDisplay !== '' && $rowCertUrl !== null): ?>
                                                <a href="<?= e($rowCertUrl) ?>" target="_blank" rel="noopener" title="View certificate"><?= e($certDisplay) ?></a>
                                            <?php else: ?>
                                                <?= e($certDisplay) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php elseif ($col['field'] === 'Measurements'): ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e(format_measurements_display($row['Measurements'] ?? null)) ?>
                                        </td>
                                    <?php elseif (strcasecmp($col['field'], 'location') === 0): ?>
                                        <td class="results-flag-col" data-label="<?= e($col['label']) ?>">
                                            <?= render_location_flag(isset($row[$col['field']]) ? (string)$row[$col['field']] : null) ?>
                                        </td>
                                    <?php elseif ($col['field'] === 'totamt'): ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e(number_format(ds_display_amount($row['totamt'] ?? 0), 2)) ?>
                                        </td>
                                    <?php else: ?>
                                        <td data-label="<?= e($col['label']) ?>">
                                            <?= e((string)($row[$col['field']] ?? '')) ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <script src="<?= e(asset_url_versioned('/assets/js/memo_actions.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/results_fit.js')) ?>"></script>
    <script src="<?= e(asset_url_versioned('/assets/js/confirm_submit.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../../includes/footer.php';
