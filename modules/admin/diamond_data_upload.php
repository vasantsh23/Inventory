<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
// Sort-order rebuild (srtcol/srtcla/srtshp/...), run after every
// successful upload below. Included, so it only defines its functions.
require_once __DIR__ . '/../../update_id26.php';

require_module_access('admin');

// The import itself (shared with the batch/cron version, cron/diamond_upload_cron.php).
require_once __DIR__ . '/../../includes/diamond_upload.php';

$uploadSource = resolve_diamond_upload_source();

$stats = null;
$fatalError = '';
$sortRebuildCount = null; // rows update_id26 re-sorted, when it succeeded
$sortRebuildError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $fatalError = 'Your session expired — please reload this page and try again.';
    } else {
        $mode = (string)($_POST['upload_mode'] ?? '');
        if (!in_array($mode, ['replace', 'add'], true)) {
            $fatalError = 'Please choose "Replace data" or "Add data".';
        } else {
            // Where the CSV comes from: the `path` table's 'upload' row
            // when it holds a path, otherwise the file the user chose.
            $csvPath = null;
            $fileLastModMs = null;
            $sourcePathShown = '';
            $sourceFileShown = '';

            if ($uploadSource['use_server_file']) {
                if ($uploadSource['error'] !== '') {
                    $fatalError = $uploadSource['error'];
                } else {
                    $csvPath = $uploadSource['resolved'];
                    $mtime = @filemtime($csvPath);
                    $fileLastModMs = $mtime !== false ? (string)($mtime * 1000) : null;
                    $sourcePathShown = $uploadSource['dir'];
                    $sourceFileShown = $uploadSource['filename'];
                }
            } elseif (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $fatalError = ($_FILES['csv_file']['error'] ?? null) === UPLOAD_ERR_INI_SIZE
                    ? 'That file is larger than this server allows for uploads.'
                    : 'No file was uploaded, or the upload failed.';
            } else {
                $origName = (string)$_FILES['csv_file']['name'];
                if (!str_ends_with(strtolower($origName), '.csv')) {
                    $fatalError = 'Please upload a .csv file.';
                } else {
                    $csvPath = $_FILES['csv_file']['tmp_name'];
                    $fileLastModMs = (string)($_POST['csv_file_lastmod'] ?? '') !== '' ? (string)$_POST['csv_file_lastmod'] : null;
                    $sourcePathShown = 'Uploaded from this computer';
                    $sourceFileShown = $origName;
                }
            }

            if ($fatalError === '' && $csvPath !== null) {
                try {
                    $stats = process_diamond_upload($csvPath, $mode);
                    $stats['source_path'] = $sourcePathShown;
                    $stats['source_file'] = $sourceFileShown;
                    record_diamond_upload_log($fileLastModMs, current_user()['username'] ?? null);
                } catch (Throwable $e) {
                    $fatalError = 'Upload failed: ' . $e->getMessage();
                }

                // The upload itself is committed at this point, so a
                // failure here is reported as a warning, not as a failed
                // upload. update_id26_run() rolls back its own changes
                // and logs the error to prglog before throwing.
                if ($stats !== null) {
                    try {
                        $sortRebuildCount = update_id26_run(get_db());
                    } catch (Throwable $e) {
                        error_log('update_id26 after diamond upload failed: ' . $e->getMessage());
                        $sortRebuildError = $e->getMessage();
                    }
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
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['fancy_colored'] ?></div><div class="stat-label">Fancy-colored rows</div></div>
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['skipped_errors'] ?></div><div class="stat-label">Skipped (errors)</div></div>
            <?php if ($sortRebuildCount !== null): ?>
                <div class="stat-card"><div class="stat-value"><?= (int)$sortRebuildCount ?></div><div class="stat-label">Sort orders rebuilt</div></div>
            <?php endif; ?>
        </div>
        <?php if ($sortRebuildError !== ''): ?>
            <div class="alert alert-error">
                The diamond data was imported, but rebuilding the sort orders (update_id26) failed, so
                listings may sort incorrectly until it's run again: <?= e($sortRebuildError) ?>
            </div>
        <?php endif; ?>
        <p class="panel-desc">
            Path: <strong><?= e($stats['source_path'] !== '' ? $stats['source_path'] : '(application folder)') ?></strong><br>
            File name: <strong><?= e($stats['source_file']) ?></strong><br>
            Mode: <strong><?= $stats['mode'] === 'replace' ? 'Replace data (existing rows were deleted first)' : 'Add data (appended to existing rows)' ?></strong><br>
            Columns populated from this file: <?= e(implode(', ', $stats['mapped_columns'])) ?>
        </p>
        <?php if (!empty($stats['unmapped_headers'])): ?>
            <p class="panel-desc">
                <strong>File columns not imported</strong> (no active Upload Field Mapping has this CSV Header Name):
                <?= e(implode(', ', $stats['unmapped_headers'])) ?>
            </p>
        <?php endif; ?>
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
            any header that doesn't match an active mapping is ignored. When Color starts with "Fancy"
            (e.g. "Fancy Deep Orange"), the Fancy flag, Nat Fancy Color and Nat Fancy Color Intensity are
            derived from it automatically — the word right after "Fancy" is the intensity, the last word is
            the color ("Fancy Deep Orange" &rarr; color "Orange", intensity "Deep"; "Fancy Blue" &rarr; color
            "Blue", intensity blank). If Total Amount is blank or zero, it's computed as Weight &times;
            Price. If Measurements is blank, it's derived as Length x Width x Height from those three fields.
            After a successful upload, the sort orders (color, clarity, shape, cut, fluorescence, polish,
            symmetry, carat) are rebuilt for every record automatically.
        </p>
        <form method="post" enctype="multipart/form-data" class="crud-form" id="diamondUploadForm"<?= $uploadSource['use_server_file'] ? ' data-source-file="' . e($uploadSource['raw']) . '"' : '' ?>>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label>Upload Mode</label>
                <label style="font-weight:400; display:block; margin-bottom:6px;">
                    <input type="radio" name="upload_mode" value="replace" id="uploadModeReplace"> Replace data — delete all existing records first, then import
                </label>
                <label style="font-weight:400; display:block;">
                    <input type="radio" name="upload_mode" value="add" id="uploadModeAdd" checked> Add data — append these records to what's already there
                </label>
                <p id="uploadModeIndicator" style="margin-top:8px; font-size:0.85rem; color: var(--text-muted);"></p>
            </div>

            <?php if ($uploadSource['use_server_file']): ?>
                <div class="form-group">
                    <label>CSV File (from File Paths &rarr; "upload")</label>
                    <p style="margin:0;">
                        Path: <code><?= e($uploadSource['dir'] !== '' ? $uploadSource['dir'] : '(application folder)') ?></code><br>
                        File name: <code><?= e($uploadSource['filename'] !== '' ? $uploadSource['filename'] : '(none)') ?></code>
                    </p>
                    <?php if ($uploadSource['error'] !== ''): ?>
                        <div class="alert alert-warning"><?= e($uploadSource['error']) ?></div>
                    <?php endif; ?>
                    <p style="margin-top:6px; font-size:0.85rem; color: var(--text-muted);">
                        This file is read directly from the server. To choose a file yourself instead, clear the path on the
                        <a href="<?= e(asset_url('/modules/admin/table_view.php?table=path')) ?>">File Paths</a> row whose description is "upload".
                    </p>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="csv_file">Enter the file name to upload</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
                    <input type="hidden" id="csvFileLastmod" name="csv_file_lastmod" value="">
                    <p id="csvFileNameDisplay" style="margin-top:6px; font-size:0.85rem; color: var(--text-muted);">No file chosen</p>
                    <p style="margin-top:6px; font-size:0.85rem; color: var(--text-muted);">
                        No upload path is set in <a href="<?= e(asset_url('/modules/admin/table_view.php?table=path')) ?>">File Paths</a>
                        (description "upload"), so please choose the file.
                    </p>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-accent" id="diamondUploadSubmitBtn" style="grid-column: 1 / -1; justify-self: start;"<?= ($uploadSource['use_server_file'] && $uploadSource['error'] !== '') ? ' disabled' : '' ?>>Upload</button>
        </form>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/diamond_data_upload.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
