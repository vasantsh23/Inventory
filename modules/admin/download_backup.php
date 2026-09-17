<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/backup.php';

require_module_access('admin');

$filename = (string)($_GET['file'] ?? '');
$path = backup_file_path($filename);

if ($path === null) {
    http_response_code(404);
    exit('Backup not found.');
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, must-revalidate');
readfile($path);
exit;
