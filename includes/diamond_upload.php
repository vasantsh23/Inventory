<?php
/**
 * includes/diamond_upload.php
 * The diamond CSV import, shared by
 *   - Admin -> Diamond Data Upload (modules/admin/diamond_data_upload.php)
 *   - the batch/cron version      (cron/diamond_upload_cron.php)
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Runs the actual CSV import: matches header names against the
 * uploadref table (active rows only), inserts into maindata using
 * only the matched columns. When the mapped Color value starts with
 * "Fancy", that row's `fancy`, `NatFancyColor` and
 * `NatFancyColorIntensity` columns are derived from it automatically
 * — e.g. "Fancy Blue" -> NatFancyColor "Blue", NatFancyColorIntensity
 * blank; "Fancy Deep Orange" -> NatFancyColor "Orange",
 * NatFancyColorIntensity "Deep" (the word immediately after "Fancy"
 * is the intensity, the last word is the color). These three columns
 * feed Diamond Search's Nat Fancy Color / Nat Fancy Color Intensity
 * sections and the Color "Fancy" pill — see includes/diamond_search_query.php.
 */
/**
 * Header-name key used to match CSV headers with Upload Field Mapping's
 * "CSV Header Name": case-insensitive and ignoring all spaces, so
 * "Depth %", "Depth%", "depth  %" and " DEPTH % " all match each other.
 */
function upload_header_key(string $header): string
{
    return strtolower((string)preg_replace('/\s+/u', '', $header));
}

/**
 * @param int $minRows Replace mode only: if fewer rows than this were
 *                     imported (default 1, i.e. an empty file), nothing
 *                     is changed and an exception is thrown — protects
 *                     the live stock from an empty or cut-off feed.
 */
function process_diamond_upload(string $csvPath, string $mode, int $minRows = 1): array
{
    set_time_limit(0); // large CSVs can take a while; don't let PHP's default timeout cut the import short

    $validCols = get_maindata_columns();

    // header name (lowercased) => maindata column, active mappings only
    $mapStmt = get_db()->query("SELECT colname, excolname FROM uploadref WHERE active = 'yes' AND excolname != ''");
    $headerToCol = [];
    foreach ($mapStmt->fetchAll() as $row) {
        if (in_array($row['colname'], $validCols, true)) {
            $headerToCol[upload_header_key((string)$row['excolname'])] = $row['colname'];
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
    $unmappedHeaders = [];
    foreach ($headerRow as $idx => $header) {
        $key = upload_header_key((string)$header);
        if (isset($headerToCol[$key])) {
            $indexToCol[$idx] = $headerToCol[$key];
        } elseif (trim((string)$header) !== '') {
            $unmappedHeaders[] = trim((string)$header);
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

    // totamt and Measurements can be computed from other mapped
    // fields when blank/zero (see the row loop below) — make sure
    // both are in the insert's column list even if the CSV doesn't
    // map either of them directly, so a computed value has somewhere
    // to go. Same for fancy/NatFancyColor/NatFancyColorIntensity,
    // always derived from Color (see docblock above) whenever Color
    // itself is mapped.
    $derivedFancyCols = $colorIdx !== null ? ['fancy', 'NatFancyColor', 'NatFancyColorIntensity'] : [];
    foreach (array_merge(['totamt', 'Measurements'], $derivedFancyCols) as $computedCol) {
        if (in_array($computedCol, $validCols, true) && !in_array($computedCol, $mappedCols, true)) {
            $mappedCols[] = $computedCol;
        }
    }

    $db = get_db();

    $colList = implode(', ', array_map(fn($c) => "`$c`", $mappedCols));
    $placeholders = implode(', ', array_fill(0, count($mappedCols), '?'));
    $insertStmt = $db->prepare("INSERT INTO maindata ($colList) VALUES ($placeholders)");

    $inserted = 0;
    $fancyCount = 0;
    $skippedErrors = 0;
    $errors = [];
    $rowNum = 1; // the header row itself was line 1

    $db->beginTransaction();
    try {
        // Replace: the old rows are deleted INSIDE the transaction, so if
        // the import fails (or the file turns out to be empty / too short)
        // everything is rolled back and the existing stock stays.
        if ($mode === 'replace') {
            $db->exec('DELETE FROM maindata');
        }
        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if ($row === [null]) {
                continue; // a blank line in the file
            }

            // Build [colname => value] first (rather than going
            // straight to the positional $values array) so the
            // computed-fallback fields below can look up other
            // mapped values by name, regardless of column order.
            $rowValues = [];
            foreach ($colToIndex as $col => $srcIdx) {
                $val = isset($row[$srcIdx]) ? trim((string)$row[$srcIdx]) : '';
                $rowValues[$col] = $val === '' ? null : $val;
            }

            // totamt: if blank or zero, compute as Weight * Price —
            // only when both of those are themselves mapped and numeric.
            if (in_array('totamt', $mappedCols, true)) {
                $totamt = $rowValues['totamt'] ?? null;
                if (($totamt === null || (float)$totamt === 0.0)
                    && isset($rowValues['Weight'], $rowValues['Price'])
                    && is_numeric($rowValues['Weight']) && is_numeric($rowValues['Price'])
                ) {
                    $rowValues['totamt'] = (string)((float)$rowValues['Weight'] * (float)$rowValues['Price']);
                }
            }

            // Measurements: if blank, derive "length x width x height"
            // from those three fields — only when all three are mapped
            // and numeric.
            if (in_array('Measurements', $mappedCols, true)) {
                $meas = $rowValues['Measurements'] ?? null;
                if (($meas === null || trim((string)$meas) === '')
                    && isset($rowValues['length'], $rowValues['width'], $rowValues['height'])
                    && is_numeric($rowValues['length']) && is_numeric($rowValues['width']) && is_numeric($rowValues['height'])
                ) {
                    $rowValues['Measurements'] = sprintf(
                        '%.2f x %.2f x %.2f',
                        (float)$rowValues['length'], (float)$rowValues['width'], (float)$rowValues['height']
                    );
                }
            }

            // fancy / NatFancyColor / NatFancyColorIntensity: derived
            // from Color when it starts with "Fancy" — the word right
            // after "Fancy" is the intensity, the last word is the
            // color (e.g. "Fancy Deep Orange" -> intensity "Deep",
            // color "Orange"; "Fancy Blue" -> intensity blank, color
            // "Blue"). Anything else -> fancy = 'no', both left blank.
            if ($colorIdx !== null) {
                $colorRaw = isset($row[$colorIdx]) ? trim((string)$row[$colorIdx]) : '';
                $isFancy = $colorRaw !== '' && preg_match('/^fancy\b\s*(.*)$/i', $colorRaw, $fancyMatch) === 1;
                $rowValues['fancy'] = $isFancy ? 'yes' : 'no';
                $rowValues['NatFancyColor'] = null;
                $rowValues['NatFancyColorIntensity'] = null;
                if ($isFancy) {
                    $fancyCount++;
                    $rest = trim((string)$fancyMatch[1]);
                    if ($rest !== '') {
                        $tokens = preg_split('/\s+/', $rest);
                        $rowValues['NatFancyColor'] = array_pop($tokens);
                        $rowValues['NatFancyColorIntensity'] = $tokens !== [] ? implode(' ', $tokens) : null;
                    }
                }
            }

            $values = [];
            foreach ($mappedCols as $col) {
                $values[] = $rowValues[$col] ?? null;
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
        if ($mode === 'replace' && $inserted < max(1, $minRows)) {
            throw new RuntimeException(
                "Only $inserted row(s) could be imported (minimum " . max(1, $minRows) . ') — the existing stock was kept unchanged.'
                . ($errors !== [] ? ' First error: ' . $errors[0] : '')
            );
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
        'unmapped_headers' => $unmappedHeaders,
        'inserted' => $inserted,
        'fancy_colored' => $fancyCount,
        'skipped_errors' => $skippedErrors,
        'errors' => $errors,
    ];
}

/**
 * Decides where the CSV comes from, based on the `path` table row
 * whose description = 'upload':
 *   - row found and `path` is non-blank -> that server-side file is
 *     imported directly; the user isn't asked to choose a file.
 *   - row found but `path` is blank/spaces (or no row at all) -> the
 *     user is prompted to choose the file to upload, as before.
 *
 * The stored value must be the full path including the file name
 * (e.g. "/home/acct/feeds/stock.csv" or "C:\feeds\stock.csv"). A
 * relative or "/"-rooted value that doesn't exist as-is is also tried
 * relative to the application root, the same way the logo path is.
 *
 * Returns:
 *   use_server_file  bool    true when the stored path should be used
 *   raw              string  the stored path, trimmed
 *   dir / filename   string  the stored path split for display
 *   resolved         ?string the readable file actually found, if any
 *   error            string  why the stored path can't be used ('' if fine)
 */
function resolve_diamond_upload_source(): array
{
    $source = [
        'use_server_file' => false,
        'raw' => '',
        'dir' => '',
        'filename' => '',
        'resolved' => null,
        'error' => '',
    ];

    $stored = get_path_by_description('upload');
    $raw = trim((string)$stored);
    if ($raw === '') {
        return $source; // no row, or path is spaces -> prompt for the file
    }

    $source['use_server_file'] = true;
    $source['raw'] = $raw;

    // Split on the last "/" or "\" so Windows-style paths display correctly too.
    $p1 = strrpos($raw, '/');
    $p2 = strrpos($raw, '\\');
    $pos = max($p1 === false ? -1 : $p1, $p2 === false ? -1 : $p2);
    $source['dir'] = $pos > 0 ? substr($raw, 0, $pos) : ($pos === 0 ? substr($raw, 0, 1) : '');
    $source['filename'] = substr($raw, $pos + 1);

    if ($source['filename'] === '') {
        $source['error'] = 'The upload path "' . $raw . '" points to a folder — include the CSV file name at the end of the path in File Paths (description "upload").';
        return $source;
    }
    if (!str_ends_with(strtolower($source['filename']), '.csv')) {
        $source['error'] = 'The upload file "' . $source['filename'] . '" is not a .csv file.';
        return $source;
    }

    $candidates = [$raw, dirname(__DIR__) . '/' . ltrim($raw, '/\\')];
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            $source['resolved'] = $candidate;
            break;
        }
    }
    if ($source['resolved'] === null) {
        $source['error'] = 'The upload file "' . $raw . '" was not found on the server, or cannot be read.';
    }

    return $source;
}

