<?php
/**
 * update_id26.php
 *
 * Rebuilds the numeric sort-order columns on `maindata` (srtcol,
 * srtcla, srtshp, srtcut, srtflu, srtpol, srtsym, srtcts) from the
 * current lookup tables, so listings/memos that ORDER BY these
 * columns (see modules/user/copy_generate.php) stay in sync after
 * diamonds are added, edited, or the lookup tables themselves change.
 *
 * -------------------------------------------------------------
 * Converted from the original MySQL/mysqli version, which:
 *   - connected directly to a MySQL server with hard-coded
 *     credentials instead of this app's shared connection,
 *   - ran 7 separate SELECT queries PER maindata ROW (id_col,
 *     id_cl, shapenw, id_cut, id_flu, id_pol, id_sym) using
 *     unescaped string concatenation,
 *   - matched against the old lookup table/column names
 *     (id_col.col/col_id, id_cl.cl/cl_id, shapenw.shapemain/id,
 *     id_cut.cut/cut_id, id_flu.flu/flu_id, id_pol.pol/pol_id,
 *     id_sym.sym/sym_id), and
 *   - kept a `processed` flag / 10,000-row LIMIT to let a MySQL
 *     cron job page through a very large production table without
 *     timing out.
 *
 * This version:
 *   - uses this project's shared PDO/MySQL connection
 *     (config/db.php's get_db()) instead of a second, separate
 *     mysqli connection with hard-coded credentials,
 *   - matches against the CURRENT lookup tables/columns:
 *       color (color -> id), clarity (clarity -> id),
 *       shape (shape -> shape_id), cut (cut -> cut_id),
 *       fluorescence (flu -> flu_id), polish (pol -> pol_id),
 *       symmetry (sym -> sym_id),
 *   - builds each lookup as a single in-memory map instead of
 *     re-querying per row, and writes every row inside one
 *     transaction with a single prepared UPDATE statement, and
 *   - matches case-/whitespace-insensitively (maindata has values
 *     like "vvs2" that only differ from the lookup's "VVS2" by
 *     case), still falling back to 0 when nothing matches — same
 *     as the original script's default for every field except
 *     Color/Shape, whose old magic-number fallbacks (12/19) were
 *     specific to the old id_col/shapenw row order.
 *   - Color keeps an equivalent fallback, looked up by NAME in the
 *     current `color` table rather than hard-coded: a Color that
 *     starts with "Fancy" (e.g. "Fancy Deep Orange") gets the "Fancy"
 *     row's id, and any other unmatched Color gets the "Others" row's
 *     id. Diamond Search's Color "Others" pill filters on exactly
 *     that (srtcol = <Others id> AND fancy = 'no' — see
 *     includes/diamond_search_query.php), so a 0 here would make
 *     those stones vanish from that filter. Shape stays at 0.
 *   - `maindata` no longer has a `processed` column, and with the
 *     per-row lookups gone a single pass over the whole table is
 *     fast, so the old chunk-of-10,000 + processed-flag paging is
 *     dropped (see the optional --limit / ?limit override below if
 *     you ever want to cap it).
 *   - logging to `prglog` is best-effort: if that table is missing
 *     (see sql/migration_prglog.sql) the rebuild still runs and the
 *     logging problem only goes to the PHP error log.
 *
 * Usage:
 *   - CLI:    php update_id26.php [--limit=N]
 *   - Browser/cron-via-wget: same URL, while logged in as an admin
 *     (?limit=N works the same way as --limit=N).
 *   - From other PHP code (e.g. modules/admin/diamond_data_upload.php,
 *     which runs it automatically after every successful upload):
 *       require_once __DIR__ . '/update_id26.php';
 *       $rows = update_id26_run(get_db());   // throws on failure
 *     When included like this the file only defines its functions;
 *     the standalone section at the bottom (auth, echo, exit) is
 *     skipped, so it never prints into or terminates the caller.
 */

declare(strict_types=1);

// True only when this file is the script PHP was asked to run (CLI
// or a direct browser/wget hit) — false when another page includes
// it just to call update_id26_run().
$update_id26_isEntry = realpath((string)(get_included_files()[0] ?? '')) === realpath(__FILE__);

if ($update_id26_isEntry) {
    date_default_timezone_set('Europe/Brussels');
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    ini_set('max_execution_time', '300'); // 5 minutes
    ini_set('memory_limit', '512M');

    if (PHP_SAPI === 'cli') {
        require_once __DIR__ . '/config/db.php';
        require_once __DIR__ . '/includes/functions.php';
    } else {
        // Web/cron-via-wget access is gated behind an admin login, same
        // as every other maintenance screen in this module.
        require_once __DIR__ . '/includes/auth.php';
        require_module_access('admin');
    }
} else {
    // The including page has already done its own auth; just make
    // sure get_db() is available.
    require_once __DIR__ . '/config/db.php';
}

/** @return int|null null means "no limit" (process every row). */
function update_id26_read_limit(bool $isCli): ?int
{
    if ($isCli) {
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
                return (int)$m[1];
            }
        }
        return null;
    }
    return isset($_GET['limit']) && ctype_digit((string)$_GET['limit']) ? (int)$_GET['limit'] : null;
}

/**
 * Best-effort run log. A logging failure (e.g. `prglog` not created
 * yet) must never stop the sort rebuild itself or mask the real error.
 */
function update_id26_log(PDO $db, string $program, string $status, int $reccnt = 0): void
{
    try {
        // Always Brussels time, whether run standalone or included by
        // the upload page (which doesn't set the timezone itself).
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Brussels'));
        $stmt = $db->prepare('INSERT INTO `prglog` (`date`, `time`, `program`, `status`, `reccnt`) VALUES (:date, :time, :program, :status, :reccnt)');
        $stmt->execute([
            ':date'    => $now->format('Y-m-d'),
            ':time'    => $now->format('H:i:s'),
            ':program' => $program,
            ':status'  => substr($status, 0, 255),
            ':reccnt'  => $reccnt,
        ]);
    } catch (Throwable $e) {
        error_log('update_id26: could not write prglog row: ' . $e->getMessage());
    }
}

/**
 * "value => sort id" map built from one query, matched later by
 * uppercased/trimmed key so minor case differences in maindata
 * (e.g. "vvs2" vs the lookup's "VVS2") still resolve.
 */
function update_id26_build_map(PDO $db, string $table, string $valueCol, string $idCol): array
{
    $map = [];
    // Backtick-quoted identifiers: this app runs on MySQL, where
    // "double quotes" are string literals, not identifiers.
    $stmt = $db->query("SELECT `$valueCol` AS v, `$idCol` AS i FROM `$table`");
    foreach ($stmt->fetchAll() as $row) {
        $key = strtoupper(trim((string)$row['v']));
        if ($key !== '') {
            $map[$key] = (int)$row['i'];
        }
    }
    return $map;
}

function update_id26_lookup(array $map, ?string $value): int
{
    return $map[strtoupper(trim((string)$value))] ?? 0;
}

/**
 * Color sort id: exact lookup first, then the "Fancy" row for any
 * "Fancy ..." color, then the "Others" row (0 if either row is missing).
 */
function update_id26_color_lookup(array $colorMap, ?string $value): int
{
    $key = strtoupper(trim((string)$value));
    if (isset($colorMap[$key])) {
        return $colorMap[$key];
    }
    if ($key !== '' && preg_match('/^FANCY\b/', $key) === 1 && isset($colorMap['FANCY'])) {
        return $colorMap['FANCY'];
    }
    return $colorMap['OTHERS'] ?? 0;
}

/**
 * Rebuilds the srt* sort columns for every maindata row (or the first
 * $limit rows) in one transaction, logging Start/End/Error to prglog.
 *
 * @return int Number of rows updated.
 * @throws Throwable on failure — the transaction is rolled back and
 *                   an "Error: ..." row is written to prglog first.
 */
function update_id26_run(PDO $db, ?int $limit = null): int
{
    $program = 'update_id26';

    // Callers like the upload page may already have used much of
    // their time budget; give this pass its own 5 minutes.
    @set_time_limit(300);

    update_id26_log($db, $program, 'Start');

    $count = 0;
    try {
        $colorMap   = update_id26_build_map($db, 'color', 'color', 'id');
        $clarityMap = update_id26_build_map($db, 'clarity', 'clarity', 'id');
        $shapeMap   = update_id26_build_map($db, 'shape', 'shape', 'shape_id');
        $cutMap     = update_id26_build_map($db, 'cut', 'cut', 'cut_id');
        $fluMap     = update_id26_build_map($db, 'fluorescence', 'flu', 'flu_id');
        $polMap     = update_id26_build_map($db, 'polish', 'pol', 'pol_id');
        $symMap     = update_id26_build_map($db, 'symmetry', 'sym', 'sym_id');

        $selectSql = 'SELECT `id`, `Color`, `Clarity`, `Shape`, `Weight`, `CutGrade`, `FluorescenceIntensity`, `Polish`, `Symmetry` FROM `maindata`';
        if ($limit !== null) {
            $selectSql .= ' LIMIT ' . $limit;
        }
        $rows = $db->query($selectSql)->fetchAll();

        $updateStmt = $db->prepare('
            UPDATE `maindata`
            SET `srtcol` = :srtcol,
                `srtcla` = :srtcla,
                `srtcts` = :srtcts,
                `srtshp` = :srtshp,
                `srtcut` = :srtcut,
                `srtflu` = :srtflu,
                `srtpol` = :srtpol,
                `srtsym` = :srtsym
            WHERE `id` = :id
        ');

        $db->beginTransaction();
        foreach ($rows as $row) {
            $updateStmt->execute([
                ':srtcol' => update_id26_color_lookup($colorMap, $row['Color']),
                ':srtcla' => update_id26_lookup($clarityMap, $row['Clarity']),
                // Sortable carat weight, scaled to an integer that keeps 3
                // decimal places of precision (1.010 ct -> 1010) rather
                // than relying on a fractional value in an INT column.
                ':srtcts' => (int)round(((float)$row['Weight']) * 1000),
                ':srtshp' => update_id26_lookup($shapeMap, $row['Shape']),
                ':srtcut' => update_id26_lookup($cutMap, $row['CutGrade']),
                ':srtflu' => update_id26_lookup($fluMap, $row['FluorescenceIntensity']),
                ':srtpol' => update_id26_lookup($polMap, $row['Polish']),
                ':srtsym' => update_id26_lookup($symMap, $row['Symmetry']),
                ':id'     => $row['id'],
            ]);
            $count++;
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        update_id26_log($db, $program, 'Error: ' . $e->getMessage(), $count);
        throw $e;
    }

    update_id26_log($db, $program, 'End', $count);
    return $count;
}

// ------------------------------------------------------------------
// Standalone run (CLI / direct URL) — skipped when included.
// ------------------------------------------------------------------
if ($update_id26_isEntry) {
    $isCli = PHP_SAPI === 'cli';
    try {
        $count = update_id26_run(get_db(), update_id26_read_limit($isCli));
    } catch (Throwable $e) {
        $message = 'update_id26 failed, no changes were saved: ' . $e->getMessage();
        if ($isCli) {
            fwrite(STDERR, $message . "\n");
        } else {
            echo htmlspecialchars($message, ENT_QUOTES);
        }
        exit(1);
    }

    $message = "Processing complete ($count rows updated)";
    echo $isCli ? ($message . "\n") : htmlspecialchars($message, ENT_QUOTES);
}
