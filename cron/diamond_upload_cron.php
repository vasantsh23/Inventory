<?php
/**
 * cron/diamond_upload_cron.php — batch version of Admin → Diamond Data Upload.
 *
 * Imports the diamond CSV into maindata without a browser, for a cron job.
 * Does exactly what the admin page does: the same Upload Field Mapping,
 * Fancy colour handling, "last update" record (upload_date) and sort-order
 * rebuild (update_id26) — the import code is shared (includes/diamond_upload.php).
 *
 * USAGE (command line only):
 *   php /path/to/site/cron/diamond_upload_cron.php [options]
 *
 * OPTIONS
 *   --mode=replace|add  replace = delete the old stock and load the file (default)
 *                       add     = append the file's rows to the existing stock
 *   --file=PATH         CSV to import. Default: the File Paths row "upload".
 *   --url=URL           Download the CSV from this address first (http/https),
 *                       instead of using --file / the "upload" path.
 *   --min-rows=N        Replace only: refuse (keep the old stock) if fewer than
 *                       N rows import — protects against an empty or cut-off
 *                       file. Default 1.
 *   --force             Import even if the file is identical to the last
 *                       successful import (normally skipped, so a frequent
 *                       cron job only reloads when the file really changed).
 *   --quiet             Print only errors (cron mails you only when it fails).
 *   --help              Show this help.
 *
 * EXIT CODE  0 = imported or skipped (unchanged), 1 = error (nothing changed
 *            for replace mode, apart from the sort rebuild warning case).
 *
 * LOG        Every run is appended to storage/logs/diamond_upload_cron.log.
 *
 * EXAMPLE cPanel cron (every day at 06:00):
 *   0 6 * * * /usr/local/bin/php /home2/atest8a6/test2609.site/cron/diamond_upload_cron.php --mode=replace --min-rows=100 --quiet
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line (cron).\n");
}

// ---------------------------------------------------------------- options
$opts = getopt('', ['mode:', 'file:', 'url:', 'min-rows:', 'force', 'quiet', 'help']);
if (isset($opts['help'])) {
    preg_match('#/\*\*(.*?)\*/#s', (string)file_get_contents(__FILE__), $m);
    echo preg_replace('/^ \* ?/m', '', trim($m[1] ?? '')) . "\n";
    exit(0);
}

$root = dirname(__DIR__);
require_once $root . '/config/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/diamond_upload.php';
require_once $root . '/update_id26.php'; // included: defines update_id26_run() only

$mode = (string)($opts['mode'] ?? 'replace');
$minRows = max(1, (int)($opts['min-rows'] ?? 1));
$force = isset($opts['force']);
$quiet = isset($opts['quiet']);

// ---------------------------------------------------------------- logging
$logDir = $root . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/diamond_upload_cron.log';

function cron_log(string $msg, bool $isError = false): void
{
    global $logFile, $quiet;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . ($isError ? 'ERROR ' : '') . $msg;
    @file_put_contents($logFile, $line . "\n", FILE_APPEND | LOCK_EX);
    if ($isError) {
        fwrite(STDERR, $line . "\n");
    } elseif (!$quiet) {
        echo $line . "\n";
    }
}

function cron_fail(string $msg): void
{
    cron_log($msg, true);
    exit(1);
}

if (!in_array($mode, ['replace', 'add'], true)) {
    cron_fail('--mode must be "replace" or "add".');
}

// ------------------------------------------- one run at a time (lock file)
$stateDir = $root . '/storage/cache';
if (!is_dir($stateDir)) {
    @mkdir($stateDir, 0755, true);
}
$lockPath = (is_dir($stateDir) && is_writable($stateDir) ? $stateDir : sys_get_temp_dir()) . '/diamond_upload_cron.lock';
$lock = fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    cron_fail('Another diamond upload is still running — this run was skipped.');
}

// ------------------------------------------------------- find the CSV file
$tempFile = null;
$fileMtime = null;
if (isset($opts['url'])) {
    $url = trim((string)$opts['url']);
    if (!preg_match('#^https?://#i', $url)) {
        cron_fail('--url must start with http:// or https://');
    }
    $tempFile = tempnam(sys_get_temp_dir(), 'dupl');
    $out = fopen($tempFile, 'wb');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $out,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_FAILONERROR => true,
        CURLOPT_FILETIME => true,
        CURLOPT_USERAGENT => 'DiamondUploadCron/1.0',
    ]);
    $ok = curl_exec($ch);
    $err = curl_error($ch);
    $remoteTime = (int)curl_getinfo($ch, CURLINFO_FILETIME);
    curl_close($ch);
    fclose($out);
    if ($ok === false) {
        @unlink($tempFile);
        cron_fail("Download failed from $url: $err");
    }
    $csvPath = $tempFile;
    $fileMtime = $remoteTime > 0 ? $remoteTime : time();
    $sourceShown = $url;
} else {
    if (isset($opts['file'])) {
        $raw = (string)$opts['file'];
        $csvPath = is_file($raw) ? $raw : $root . '/' . ltrim($raw, '/\\');
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            cron_fail("File not found or not readable: $raw");
        }
    } else {
        $src = resolve_diamond_upload_source();
        if (!$src['use_server_file']) {
            cron_fail('No file given: use --file=PATH or --url=URL, or set the File Paths row "upload" to the CSV\'s full path on the server.');
        }
        if ($src['error'] !== '') {
            cron_fail($src['error']);
        }
        $csvPath = $src['resolved'];
    }
    $fileMtime = @filemtime($csvPath) ?: time();
    $sourceShown = $csvPath;
}

// ----------------------------------------- skip if unchanged since last run
$statePath = $stateDir . '/diamond_upload_cron.json';
$hash = (string)@sha1_file($csvPath);
$state = is_file($statePath) ? (json_decode((string)file_get_contents($statePath), true) ?: []) : [];
if (!$force && $hash !== '' && ($state['hash'] ?? '') === $hash && ($state['mode'] ?? '') === $mode) {
    cron_log("Skipped: $sourceShown is unchanged since the import of " . ($state['at'] ?? 'the last run') . ' (use --force to import anyway).');
    if ($tempFile !== null) {
        @unlink($tempFile);
    }
    exit(0);
}

// ------------------------------------------------------------------ import
cron_log("Importing $sourceShown (mode: $mode" . ($mode === 'replace' ? ", min rows: $minRows" : '') . ')');
try {
    $stats = process_diamond_upload($csvPath, $mode, $minRows);
} catch (Throwable $e) {
    if ($tempFile !== null) {
        @unlink($tempFile);
    }
    cron_fail('Import failed, nothing was changed: ' . $e->getMessage());
}
if ($tempFile !== null) {
    @unlink($tempFile);
}

record_diamond_upload_log((string)($fileMtime * 1000), 'cron');
@file_put_contents($statePath, json_encode(['hash' => $hash, 'mode' => $mode, 'at' => date('Y-m-d H:i:s'), 'source' => $sourceShown]));

cron_log(sprintf(
    'Done: %d rows inserted, %d fancy-coloured, %d skipped with errors. Columns: %s',
    $stats['inserted'], $stats['fancy_colored'], $stats['skipped_errors'], implode(', ', $stats['mapped_columns'])
));
if (!empty($stats['unmapped_headers'])) {
    cron_log('File columns not imported (no Upload Field Mapping): ' . implode(', ', $stats['unmapped_headers']));
}
foreach ($stats['errors'] as $err) {
    cron_log('Row error: ' . $err);
}

// ----------------------------------------------------- sort-order rebuild
try {
    $count = update_id26_run(get_db());
    cron_log("Sort orders rebuilt for $count rows.");
} catch (Throwable $e) {
    cron_log('The data was imported, but rebuilding the sort orders (update_id26) failed: ' . $e->getMessage(), true);
    exit(1);
}

exit(0);
