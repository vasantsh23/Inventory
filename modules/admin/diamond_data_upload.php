<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('admin');

/**
 * Runs the actual CSV import: matches header names against the
 * uploadref table (active rows only), inserts into maindata using
 * only the matched columns, and skips any row whose mapped Color
 * value contains "fancy" (case-insensitive) — fancy-colored diamonds
 * are graded/priced completely differently and are intentionally
 * excluded from this import.
 */
function process_diamond_upload(string $csvPath, string $mode): array
{
    set_time_limit(0); // large CSVs can take a while; don't let PHP's default timeout cut the import short

    $validCols = get_maindata_columns();

    // header name (lowercased) => maindata column, active mappings only
    $mapStmt = get_db()->query("SELECT colname, excolname FROM uploadref WHERE active = 'yes' AND excolname != ''");
    $headerToCol = [];
    foreach ($mapStmt->fetchAll() as $row) {
        if (in_array($row['colname'], $validCols, true)) {
            $headerToCol[strtolower(trim((string)$row['excolname']))] = $row['colname'];
        }
    }
    if ($headerToCol === []) {
        throw new RuntimeException(
            'No active Upload Field Mapping entries have a CSV Header Name set — configure at least one under Admin → Upload Field Mapping before importing.'
        );
    }

    $fh = fopen($csvPath, 'r');
    if ($fh === false) {
        throw new RuntimeException('Could not open the uploaded file.');
    }

    // Strip a UTF-8 BOM if present — common in CSVs exported from Excel,
    // and would otherwise corrupt the very first header name.
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fh);
    }

    $headerRow = fgetcsv($fh);
    if ($headerRow === false || $headerRow === null) {
        fclose($fh);
        throw new RuntimeException('The uploaded file appears to be empty.');
    }

    // CSV column index => maindata column name (only for headers that
    // matched an active mapping; everything else is ignored).
    $indexToCol = [];
    foreach ($headerRow as $idx => $header) {
        $key = strtolower(trim((string)$header));
        if (isset($headerToCol[$key])) {
            $indexToCol[$idx] = $headerToCol[$key];
        }
    }
    if ($indexToCol === []) {
        fclose($fh);
        throw new RuntimeException('None of this file\'s column headers matched an active Upload Field Mapping entry.');
    }

    $mappedCols = array_values(array_unique($indexToCol));
    // colname => the CSV index that supplies it (first match if more
    // than one header somehow mapped to the same maindata column).
    $colToIndex = [];
    foreach ($indexToCol as $idx => $col) {
        if (!isset($colToIndex[$col])) {
            $colToIndex[$col] = $idx;
        }
    }
    $colorIdx = $colToIndex['Color'] ?? $colToIndex['color'] ?? null;

    $db = get_db();
    if ($mode === 'replace') {
        $db->exec('DELETE FROM maindata');
    }

    $colList = implode(', ', array_map(fn($c) => "`$c`", $mappedCols));
    $placeholders = implode(', ', array_fill(0, count($mappedCols), '?'));
    $insertStmt = $db->prepare("INSERT INTO maindata ($colList) VALUES ($placeholders)");

    $inserted = 0;
    $skippedFancy = 0;
    $skippedErrors = 0;
    $errors = [];
    $rowNum = 1; // the header row itself was line 1

    $db->beginTransaction();
    try {
        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if ($row === [null]) {
                continue; // a blank line in the file
            }

            if ($colorIdx !== null && isset($row[$colorIdx]) && stripos((string)$row[$colorIdx], 'fancy') !== false) {
                $skippedFancy++;
                continue;
            }

            $values = [];
            foreach ($mappedCols as $col) {
                $srcIdx = $colToIndex[$col];
                $val = isset($row[$srcIdx]) ? trim((string)$row[$srcIdx]) : '';
                $values[] = $val === '' ? null : $val;
            }

            try {
                $insertStmt->execute($values);
                $inserted++;
            } catch (Throwable $e) {
                $skippedErrors++;
                if (count($errors) < 20) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        fclose($fh);
        throw $e;
    }
    fclose($fh);

    return [
        'mode' => $mode,
        'mapped_columns' => $mappedCols,
        'inserted' => $inserted,
        'skipped_fancy' => $skippedFancy,
        'skipped_errors' => $skippedErrors,
        'errors' => $errors,
    ];
}

$stats = null;
$fatalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $fatalError = 'Your session expired — please reload this page and try again.';
    } else {
        $mode = (string)($_POST['upload_mode'] ?? '');
        if (!in_array($mode, ['replace', 'add'], true)) {
            $fatalError = 'Please choose "Replace data" or "Add data".';
        } elseif (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $fatalError = $_FILES['csv_file']['error'] === UPLOAD_ERR_INI_SIZE
                ? 'That file is larger than this server allows for uploads.'
                : 'No file was uploaded, or the upload failed.';
        } else {
            $origName = (string)$_FILES['csv_file']['name'];
            if (!str_ends_with(strtolower($origName), '.csv')) {
                $fatalError = 'Please upload a .csv file.';
            } else {
                try {
                    $stats = process_diamond_upload($_FILES['csv_file']['tmp_name'], $mode);
                } catch (Throwable $e) {
                    $fatalError = 'Upload failed: ' . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = 'Diamond Data Upload';
$pageSubtitle = '';
$activeNav = 'diamond_data_upload';
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/modules/admin/table_view.php?table=uploadref')) . '">Upload Field Mapping</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($fatalError !== ''): ?>
        <div class="alert alert-error"><?= e($fatalError) ?></div>
    <?php endif; ?>

    <?php if ($stats !== null): ?>
        <div class="stat-grid">
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['inserted'] ?></div><div class="stat-label">Rows inserted</div></div>
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['skipped_fancy'] ?></div><div class="stat-label">Skipped (Fancy color)</div></div>
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['skipped_errors'] ?></div><div class="stat-label">Skipped (errors)</div></div>
        </div>
        <p class="panel-desc">
            Mode: <strong><?= $stats['mode'] === 'replace' ? 'Replace data (existing rows were deleted first)' : 'Add data (appended to existing rows)' ?></strong><br>
            Columns populated from this file: <?= e(implode(', ', $stats['mapped_columns'])) ?>
        </p>
        <?php if ($stats['errors'] !== []): ?>
            <div class="alert alert-error">
                <strong>First <?= count($stats['errors']) ?> row error<?= count($stats['errors']) === 1 ? '' : 's' ?>:</strong>
                <ul>
                    <?php foreach ($stats['errors'] as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="panel">
        <h2>Upload Diamond Data</h2>
        <p class="panel-desc">
            Upload a CSV file of diamond records. Column headers in the file are matched against
            <a href="<?= e(asset_url('/modules/admin/table_view.php?table=uploadref')) ?>">Upload Field Mapping</a>'s
            "CSV Header Name" entries to decide which <code>maindata</code> field each column populates —
            any header that doesn't match an active mapping is ignored. Rows whose Color value contains
            "Fancy" are always skipped (fancy-colored diamonds aren't imported by this program).
        </p>
        <form method="post" enctype="multipart/form-data" class="crud-form" id="diamondUploadForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label>Upload Mode</label>
                <label style="font-weight:400; display:block; margin-bottom:6px;">
                    <input type="radio" name="upload_mode" value="replace" id="uploadModeReplace"> Replace data — delete all existing records first, then import
                </label>
                <label style="font-weight:400; display:block;">
                    <input type="radio" name="upload_mode" value="add" id="uploadModeAdd" checked> Add data — append these records to what's already there
                </label>
            </div>

            <div class="form-group">
                <label for="csv_file">CSV File</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
            </div>

            <button type="submit" class="btn btn-accent" id="diamondUploadSubmitBtn" style="justify-self: start; align-self: start;">Upload</button>
        </form>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_data_upload.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
