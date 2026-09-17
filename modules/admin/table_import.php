<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';

require_module_access('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$table = (string)($_POST['table'] ?? '');
crud_assert_table($table);

$stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
$fatalError = '';

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    $fatalError = 'Your session expired — please go back and try again.';
} elseif (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $fatalError = 'No file was uploaded, or the upload failed.';
} else {
    $tmpPath = $_FILES['import_file']['tmp_name'];
    $origName = (string)$_FILES['import_file']['name'];

    if (!str_ends_with(strtolower($origName), '.xlsx')) {
        $fatalError = 'Please upload a .xlsx file.';
    } else {
        try {
            [$headerRow, $dataRows] = XlsxReader::read($tmpPath);
            if ($headerRow === []) {
                $fatalError = 'The uploaded file appears to be empty.';
            } else {
                $stats = crud_import_rows($table, $headerRow, $dataRows);
            }
        } catch (Throwable $e) {
            $fatalError = 'Import failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Import Results — ' . CRUD_TABLES[$table];
$pageSubtitle = '';
$activeNav = 'table:' . $table;
$dashActionsHtml = '<a class="btn" href="' . e(asset_url('/modules/admin/table_view.php?table=' . urlencode($table))) . '">&larr; Back to list</a>';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($fatalError !== ''): ?>
        <div class="alert alert-error"><?= e($fatalError) ?></div>
    <?php else: ?>
        <div class="stat-grid">
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['inserted'] ?></div><div class="stat-label">Rows inserted</div></div>
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['updated'] ?></div><div class="stat-label">Rows updated</div></div>
            <div class="stat-card"><div class="stat-value"><?= (int)$stats['skipped'] ?></div><div class="stat-label">Rows skipped</div></div>
        </div>

        <?php if ($stats['errors'] !== []): ?>
            <div class="panel">
                <h2>Notes</h2>
                <ul>
                    <?php foreach ($stats['errors'] as $err): ?>
                        <li style="color:var(--text-mid); margin-bottom:6px;"><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-success">Import completed with no issues.</div>
        <?php endif; ?>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
