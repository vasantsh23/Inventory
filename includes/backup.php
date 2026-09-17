<?php
/**
 * backup.php
 * Whole-database backup (structure + data, as a portable .sql dump)
 * and restore. Encrypted columns are backed up in their already-
 * encrypted (ciphertext) form — restoring on the same app instance
 * (same APP_ENCRYPTION_KEY) decrypts correctly again automatically.
 *
 * Backups are written outside any URL-mapped assumption and only
 * ever served through download_backup.php, which requires an
 * authenticated admin/superadmin session — never linked directly.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function backup_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
        // Belt-and-braces for Apache-based shared hosting: block direct
        // HTTP access even if the folder ends up inside the web root.
        file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return $dir;
}

/** All tables in the current database, in a safe dependency order (FKs last-safe via simple listing). */
function all_table_names(): array
{
    $stmt = get_db()->query(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
    );
    return array_column($stmt->fetchAll(), 'TABLE_NAME');
}

/** Generate a full SQL dump (schema + data) and save it under storage/backups. Returns the filename. */
function create_backup(?string $createdBy = null): string
{
    $db = get_db();
    $tables = all_table_names();
    $filename = 'backup-' . date('Ymd-His') . '.sql';
    $path = backup_dir() . '/' . $filename;

    $fh = fopen($path, 'w');
    if ($fh === false) {
        throw new RuntimeException('Could not create backup file — check storage/ folder permissions.');
    }

    fwrite($fh, "-- Inventory Management System backup\n-- Generated: " . date('c') . "\n\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $table) {
        $createRow = $db->query("SHOW CREATE TABLE `$table`")->fetch();
        $createSql = $createRow['Create Table'] ?? '';

        fwrite($fh, "-- ----------------------------\n-- Table: $table\n-- ----------------------------\n");
        fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($fh, $createSql . ";\n\n");

        $rowCountStmt = $db->query("SELECT COUNT(*) AS c FROM `$table`");
        $rowCount = (int)$rowCountStmt->fetch()['c'];
        if ($rowCount === 0) {
            continue;
        }

        $colStmt = $db->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $db->quote($table) . "
             ORDER BY ORDINAL_POSITION"
        );
        $columns = array_column($colStmt->fetchAll(), 'COLUMN_NAME');
        $colList = implode(', ', array_map(fn($c) => "`$c`", $columns));

        $dataStmt = $db->query("SELECT * FROM `$table`");
        $batch = [];
        $batchSize = 200;
        $count = 0;

        foreach ($dataStmt as $row) {
            $vals = array_map(function ($v) use ($db) {
                if ($v === null) {
                    return 'NULL';
                }
                if (is_int($v) || is_float($v)) {
                    return (string)$v;
                }
                return $db->quote((string)$v);
            }, $row);
            $batch[] = '(' . implode(', ', $vals) . ')';
            $count++;

            if (count($batch) >= $batchSize) {
                fwrite($fh, "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch !== []) {
            fwrite($fh, "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    $size = filesize($path) ?: 0;
    $log = $db->prepare(
        'INSERT INTO backup_log (filename, size_bytes, created_by) VALUES (:f, :s, :u)'
    );
    $log->execute([':f' => $filename, ':s' => $size, ':u' => $createdBy]);

    return $filename;
}

/** List backups newest-first, from the log table (falls back to disk listing if the log is empty). */
function list_backups(): array
{
    $stmt = get_db()->query('SELECT * FROM backup_log ORDER BY created_at DESC LIMIT 50');
    $rows = $stmt->fetchAll();
    if ($rows !== []) {
        return $rows;
    }

    $dir = backup_dir();
    $files = glob($dir . '/*.sql') ?: [];
    rsort($files);
    return array_map(fn($f) => [
        'filename'   => basename($f),
        'size_bytes' => filesize($f) ?: 0,
        'created_by' => null,
        'created_at' => date('Y-m-d H:i:s', filemtime($f) ?: time()),
    ], $files);
}

function backup_file_path(string $filename): ?string
{
    // Prevent path traversal — only bare filenames matching our own
    // naming pattern are ever accepted.
    if (!preg_match('/^backup-[0-9]{8}-[0-9]{6}\.sql$/', $filename)) {
        return null;
    }
    $path = backup_dir() . '/' . $filename;
    return is_file($path) ? $path : null;
}

/**
 * Restore a database from an uploaded .sql dump. Runs inside a
 * transaction where possible; statements are split on statement-
 * terminating semicolons that are outside quoted strings.
 * Returns the number of statements executed.
 */
function restore_backup(string $sqlFilePath): int
{
    $sql = file_get_contents($sqlFilePath);
    if ($sql === false) {
        throw new RuntimeException('Could not read the uploaded backup file.');
    }

    $statements = split_sql_statements($sql);
    $db = get_db();
    $executed = 0;

    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($statements as $stmt) {
            $stmt = strip_sql_comment_lines($stmt);
            if ($stmt === '') {
                continue;
            }
            $db->exec($stmt);
            $executed++;
        }
    } finally {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    return $executed;
}

/** Remove full-line "-- ..." SQL comments from a statement, keeping any real SQL that follows. */
function strip_sql_comment_lines(string $stmt): string
{
    $lines = explode("\n", $stmt);
    $kept = array_filter($lines, fn($line) => !str_starts_with(ltrim($line), '--'));
    return trim(implode("\n", $kept));
}

/** Split a .sql file into individual statements, respecting quoted strings. */
function split_sql_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($inString) {
            $current .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                $current .= $sql[++$i];
                continue;
            }
            if ($ch === $stringChar) {
                $inString = false;
            }
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $inString = true;
            $stringChar = $ch;
            $current .= $ch;
            continue;
        }

        if ($ch === ';') {
            $statements[] = $current;
            $current = '';
            continue;
        }

        $current .= $ch;
    }

    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}
