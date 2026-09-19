<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/backup.php';

require_module_access('admin');

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify($_POST['csrf_token'] ?? null)) {
    $message = 'Your session expired — please try again.';
    $messageType = 'error';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup') {
    try {
        $filename = create_backup(current_user()['username']);
        $message = "Backup created: $filename";
    } catch (Throwable $e) {
        $message = 'Backup failed: ' . $e->getMessage();
        $messageType = 'error';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore') {
    // Restoring is destructive (DROP TABLE + re-create). Restrict to superadmin.
    if ((int)(current_user()['level'] ?? 0) < 9) {
        $message = 'Only a superadmin can restore a backup.';
        $messageType = 'error';
    } elseif (empty($_FILES['restore_file']) || $_FILES['restore_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please choose a .sql backup file to restore.';
        $messageType = 'error';
    } else {
        try {
            $count = restore_backup($_FILES['restore_file']['tmp_name']);
            $message = "Restore complete — $count statements executed. You may need to log in again.";
        } catch (Throwable $e) {
            $message = 'Restore failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$backups = list_backups();

$pageTitle = 'Backup & Restore';
$pageSubtitle = 'Download a full snapshot of your data, or restore from a previous backup.';
$activeNav = 'backup';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>Create a Backup</h2>
        <p class="panel-desc">Generates a complete .sql dump of every table (structure + data) in the database.</p>
        <form method="post" action="<?= e(asset_url('/modules/admin/backup.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn btn-accent">Create Backup Now</button>
        </form>
    </div>

    <div class="panel">
        <h2>Previous Backups</h2>
        <?php if ($backups === []): ?>
            <p class="panel-desc">No backups have been created yet.</p>
        <?php else: ?>
            <ul class="backup-list">
                <?php foreach ($backups as $b): ?>
                    <li>
                        <span><?= e($b['filename']) ?></span>
                        <span class="backup-meta">
                            <?= e(number_format(($b['size_bytes'] ?? 0) / 1024, 1)) ?> KB
                            &middot; <?= e($b['created_at']) ?>
                            <?php if (!empty($b['created_by'])): ?> &middot; by <?= e($b['created_by']) ?><?php endif; ?>
                        </span>
                        <a class="btn btn-sm" href="<?= e(asset_url('/modules/admin/download_backup.php?file=' . urlencode($b['filename']))) ?>">Download</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Restore from Backup</h2>
        <p class="panel-desc">
            <strong style="color:#ffb0c0;">Warning:</strong> restoring replaces existing tables with the contents
            of the uploaded file. This cannot be undone. Restricted to superadmin accounts.
        </p>
        <?php if ((int)(current_user()['level'] ?? 0) >= 9): ?>
            <form method="post" action="<?= e(asset_url('/modules/admin/backup.php')) ?>" enctype="multipart/form-data"
                  data-confirm="This will overwrite existing data with the uploaded backup. Continue?">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="restore">
                <div class="dropzone">
                    <input type="file" name="restore_file" accept=".sql" required>
                    <p style="margin:10px 0 0;"><strong>Choose a .sql backup file</strong> to restore.</p>
                </div>
                <div style="margin-top:14px;">
                    <button type="submit" class="btn btn-danger">Restore from File</button>
                </div>
            </form>
        <?php else: ?>
            <p class="panel-desc">Sign in as a superadmin to restore a backup.</p>
        <?php endif; ?>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
