<?php
/**
 * crud_engine.php
 * Generic, metadata-driven CRUD used by every table screen in the
 * admin dashboard. Column structure is discovered from
 * information_schema so new columns show up automatically; special
 * handling (encryption, password hashing, selects) comes from
 * crud_config.php.
 *
 * SECURITY: $table is always validated against CRUD_TABLES (a fixed
 * allowlist) before being used in any SQL — it is never taken
 * directly from user input into a query.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php'; // get_maindata_columns(), used by the maindata_column_select field type
require_once __DIR__ . '/crud_config.php';

/**
 * Wire-safe HTML form field name for a column. PHP silently mangles
 * spaces (and dots) in submitted field names into underscores when
 * building $_POST — many of this schema's real column names contain
 * spaces (e.g. "Page title", "font type-1"), so using them directly
 * as a field's name="" attribute means the submitted value is never
 * found under the real column name and silently gets dropped. Every
 * field is rendered with this sanitized name instead, and
 * crud_extract_form_input() translates back to real column names.
 */
function crud_wire_name(string $colName): string
{
    return 'f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $colName);
}

/** Rebuild a real-column-name-keyed input array from a raw $_POST array. */
function crud_extract_form_input(array $meta, array $post): array
{
    $input = [];
    foreach ($meta['columns'] as $colName => $col) {
        $wireName = crud_wire_name($colName);
        if (array_key_exists($wireName, $post)) {
            $input[$colName] = $post[$wireName];
        }
    }
    return $input;
}

function crud_assert_table(string $table): void
{
    if (!array_key_exists($table, CRUD_TABLES)) {
        http_response_code(404);
        exit('Unknown table.');
    }
}

/**
 * Fetch [id => label] options for a 'lookup' column from another
 * table. $lookupTable must itself be in CRUD_TABLES (the same
 * allowlist used everywhere else) — this is never built from raw
 * user input, only from the fixed overrides in crud_config.php.
 */
function crud_lookup_options(string $lookupTable, string $valueCol, string $displayCol): array
{
    if (!array_key_exists($lookupTable, CRUD_TABLES)) {
        return [];
    }
    static $cache = [];
    $cacheKey = "$lookupTable:$valueCol:$displayCol";
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $stmt = get_db()->query("SELECT `$valueCol` AS v, `$displayCol` AS l FROM `$lookupTable` ORDER BY `$valueCol` ASC");
    $options = [];
    foreach ($stmt->fetchAll() as $row) {
        $options[(string)$row['v']] = (string)$row['l'];
    }
    $cache[$cacheKey] = $options;
    return $options;
}

/** Discover column metadata for $table (auto + overrides from crud_config.php). */
function get_table_meta(string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    crud_assert_table($table);

    $stmt = get_db()->prepare(
        'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, IS_NULLABLE, EXTRA
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([':t' => $table]);
    $dbColumns = $stmt->fetchAll();

    $overrides = CRUD_COLUMN_OVERRIDES[$table] ?? [];
    $pk = 'id';
    $columns = [];

    foreach ($dbColumns as $col) {
        $name = $col['COLUMN_NAME'];
        if ($col['COLUMN_KEY'] === 'PRI') {
            $pk = $name;
        }

        $isBinary  = in_array($col['DATA_TYPE'], ['varbinary', 'binary', 'blob'], true);
        $isLongText = in_array($col['DATA_TYPE'], ['text', 'mediumtext', 'longtext'], true);

        $meta = [
            'label'     => $overrides[$name]['label'] ?? ucwords(str_replace(['-', '_'], ' ', $name)),
            'type'      => $overrides[$name]['type'] ?? ($isBinary ? 'text' : ($isLongText ? 'textarea' : 'text')),
            'options'   => $overrides[$name]['options'] ?? null,
            'hint'      => $overrides[$name]['hint'] ?? null,
            'encrypted' => $overrides[$name]['encrypted'] ?? $isBinary,
            'nullable'  => $col['IS_NULLABLE'] === 'YES',
            'auto'      => str_contains($col['EXTRA'], 'auto_increment'),
        ];

        if (crud_is_color_column($name)) {
            $meta['type'] = 'color';
        }

        if ($meta['type'] === 'lookup') {
            $lookupTable = $overrides[$name]['lookup_table'];
            $lookupValue = $overrides[$name]['lookup_value'];
            $lookupDisplay = $overrides[$name]['lookup_display'];
            $meta['lookup_table'] = $lookupTable;
            $meta['lookup_value'] = $lookupValue;
            $meta['lookup_display'] = $lookupDisplay;
            $meta['options'] = crud_lookup_options($lookupTable, $lookupValue, $lookupDisplay);
        }

        $columns[$name] = $meta;
    }

    $result = ['pk' => $pk, 'columns' => $columns, 'label' => CRUD_TABLES[$table]];
    $cache[$table] = $result;
    return $result;
}

/** Columns safe to run a LIKE search against (plain, non-secret text). */
function crud_searchable_columns(array $meta): array
{
    $out = [];
    foreach ($meta['columns'] as $name => $col) {
        if ($col['encrypted'] || in_array($col['type'], ['password', 'secret', 'hidden'], true)) {
            continue;
        }
        if (in_array($col['type'], ['text', 'textarea', 'email', 'select'], true)) {
            $out[] = $name;
        }
    }
    return $out;
}

/** Columns shown in the list view (everything visible, i.e. not hidden/password/secret). */
function crud_list_columns(string $table, array $meta, int $limit = 7): array
{
    if (isset(CRUD_LIST_COLUMNS[$table])) {
        return array_values(array_filter(
            CRUD_LIST_COLUMNS[$table],
            fn($col) => array_key_exists($col, $meta['columns'])
        ));
    }

    $out = [];
    foreach ($meta['columns'] as $name => $col) {
        if (in_array($col['type'], ['hidden', 'password', 'secret'], true)) {
            continue;
        }
        $out[] = $name;
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/** Decrypt a raw DB row's encrypted columns for display. */
function crud_decrypt_row(array $meta, array $row): array
{
    foreach ($meta['columns'] as $name => $col) {
        if ($col['encrypted'] && array_key_exists($name, $row) && $row[$name] !== null) {
            $row[$name] = decrypt_value($row[$name]);
        }
    }
    return $row;
}

/** Paginated, optionally-searched list of rows. */
function crud_list(string $table, string $search, int $page, int $perPage = 20): array
{
    $meta = get_table_meta($table);
    $db = get_db();
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $where = '';
    $params = [];
    if ($search !== '') {
        $searchable = crud_searchable_columns($meta);
        if ($searchable !== []) {
            $clauses = [];
            foreach ($searchable as $i => $col) {
                $clauses[] = "`$col` LIKE :s$i";
                $params[":s$i"] = '%' . $search . '%';
            }
            $where = 'WHERE ' . implode(' OR ', $clauses);
        }
    }

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM `$table` $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    $stmt = $db->prepare(
        "SELECT * FROM `$table` $where ORDER BY `{$meta['pk']}` DESC LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = array_map(fn($r) => crud_decrypt_row($meta, $r), $stmt->fetchAll());

    return [
        'rows'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'perPage'  => $perPage,
        'pages'    => max(1, (int)ceil($total / $perPage)),
    ];
}

/** Fetch a single row by primary key (password/secret columns come back blank). */
function crud_get(string $table, int|string $id): ?array
{
    $meta = get_table_meta($table);
    $stmt = get_db()->prepare("SELECT * FROM `$table` WHERE `{$meta['pk']}` = :id LIMIT 1");
    $stmt->execute([':id' => (string)$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row = crud_decrypt_row($meta, $row);
    foreach ($meta['columns'] as $name => $col) {
        if (in_array($col['type'], ['password', 'secret'], true)) {
            $row[$name] = ''; // never re-display sensitive values
        }
    }
    return $row;
}

/**
 * Build the column => bind-value map for an INSERT/UPDATE from raw
 * form/import input, applying hashing/encryption/validation rules.
 * $isNew controls whether empty password/secret fields are allowed
 * (skipped on edit, may be required on insert for some tables).
 */
function crud_build_values(string $table, array $meta, array $input, bool $isNew): array
{
    $values = [];

    foreach ($meta['columns'] as $name => $col) {
        if ($col['auto'] || $col['type'] === 'hidden') {
            continue; // managed automatically (autoincrement id, emailid_hash, etc.)
        }
        if ($col['type'] === 'readonly') {
            continue;
        }

        $raw = array_key_exists($name, $input) ? trim((string)$input[$name]) : '';

        if ($col['type'] === 'password') {
            if ($raw === '') {
                continue; // keep existing hash on edit; caller enforces "required" on insert
            }
            $values[$name] = password_hash($raw, PASSWORD_BCRYPT);
            continue;
        }

        if ($col['type'] === 'secret') {
            if ($raw === '') {
                continue; // keep existing encrypted value
            }
            $values[$name] = encrypt_value($raw);
            continue;
        }

        if ($col['encrypted']) {
            $values[$name] = $raw === '' ? null : encrypt_value($raw);
            continue;
        }

        if ($col['type'] === 'select' && is_array($col['options']) && $raw !== '') {
            // Case-insensitive match (spreadsheet imports commonly have
            // inconsistent capitalization, e.g. "Yes"/"YES"/"yes") but
            // always store the canonical option casing, not whatever the
            // source happened to use, so other code that compares this
            // value exactly (pill colors, etc.) keeps working correctly.
            // Associative options (e.g. ['A' => 'Ascending']) match/store
            // the key; plain indexed lists (e.g. ['yes', 'no']) match/store
            // the value itself, same as before.
            $canonical = null;
            foreach ($col['options'] as $optKey => $opt) {
                $optValue = is_int($optKey) ? $opt : (string)$optKey;
                if (strcasecmp($optValue, $raw) === 0) {
                    $canonical = $optValue;
                    break;
                }
            }
            if ($canonical === null) {
                continue; // no matching option at all — ignore rather than trusting arbitrary input
            }
            $raw = $canonical;
        }

        if ($col['type'] === 'color' && $raw !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $raw)) {
            continue; // not a valid #rrggbb hex color — ignore rather than saving garbage
        }

        if ($col['type'] === 'lookup') {
            if ($raw === '' || !array_key_exists($raw, (array)$col['options'])) {
                continue; // must be a real id from the referenced table
            }
            $values[$name] = (int)$raw;
            continue;
        }

        $values[$name] = $raw === '' && $col['nullable'] ? null : $raw;
    }

    // Keep the searchable email-hash column in sync for the user table.
    if ($table === 'user' && array_key_exists('emailid', $values) && $values['emailid'] !== null) {
        $values['emailid_hash'] = hmac_lookup((string)$input['emailid']);
    }

    return $values;
}

/** Insert (id === null) or update (id set) a row. Returns the row's id. */
function crud_save(string $table, int|string|null $id, array $input): string
{
    $meta = get_table_meta($table);
    $values = crud_build_values($table, $meta, $input, $id === null);

    $db = get_db();

    if ($id === null) {
        if ($values === []) {
            throw new InvalidArgumentException('Nothing to save.');
        }
        $cols = array_keys($values);
        $placeholders = array_map(fn($c) => ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $c), $cols);
        $sql = "INSERT INTO `$table` (" . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ')
                VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $db->prepare($sql);
        foreach ($cols as $i => $c) {
            $stmt->bindValue($placeholders[$i], $values[$c]);
        }
        $stmt->execute();
        return $db->lastInsertId();
    }

    if ($values !== []) {
        $sets = [];
        foreach (array_keys($values) as $c) {
            $ph = ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $c);
            $sets[] = "`$c` = $ph";
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `{$meta['pk']}` = :__id";
        $stmt = $db->prepare($sql);
        foreach ($values as $c => $v) {
            $stmt->bindValue(':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $c), $v);
        }
        $stmt->bindValue(':__id', $id);
        $stmt->execute();
    }
    return $id;
}

function crud_delete(string $table, int|string $id): void
{
    $meta = get_table_meta($table);
    $stmt = get_db()->prepare("DELETE FROM `$table` WHERE `{$meta['pk']}` = :id");
    $stmt->execute([':id' => (string)$id]);
}

function crud_row_exists(string $table, int|string $id): bool
{
    $meta = get_table_meta($table);
    $stmt = get_db()->prepare("SELECT 1 FROM `$table` WHERE `{$meta['pk']}` = :id LIMIT 1");
    $stmt->execute([':id' => (string)$id]);
    return (bool)$stmt->fetchColumn();
}

/** Build [headers[], rows[][]] for export — excludes password/secret columns entirely. */
function crud_export_data(string $table): array
{
    $meta = get_table_meta($table);
    $exportCols = [];
    foreach ($meta['columns'] as $name => $col) {
        if (!in_array($col['type'], ['password', 'secret', 'hidden'], true)) {
            $exportCols[] = $name;
        }
    }

    $stmt = get_db()->query("SELECT * FROM `$table` ORDER BY `{$meta['pk']}` ASC");
    $rows = [];
    foreach ($stmt->fetchAll() as $dbRow) {
        $dbRow = crud_decrypt_row($meta, $dbRow);
        $rows[] = array_map(fn($c) => (string)($dbRow[$c] ?? ''), $exportCols);
    }

    return ['headers' => $exportCols, 'rows' => $rows];
}

/**
 * If $table is configured in CRUD_IMPORT_CLEAR_COLUMNS, blank out
 * those columns across every existing row before an import applies
 * new values — see the constant's doc comment for why.
 */
function crud_clear_import_columns(string $table, array $meta): void
{
    $columns = CRUD_IMPORT_CLEAR_COLUMNS[$table] ?? [];
    if ($columns === []) {
        return;
    }

    $sets = [];
    foreach ($columns as $colName) {
        $colMeta = $meta['columns'][$colName] ?? null;
        if ($colMeta === null) {
            continue;
        }
        $blank = $colMeta['nullable'] ? 'NULL' : "''";
        $sets[] = "`$colName` = $blank";
    }
    if ($sets === []) {
        return;
    }

    get_db()->exec("UPDATE `$table` SET " . implode(', ', $sets));
}

/**
 * Import parsed spreadsheet rows into $table.
 * $headerRow: array of column-name strings (first row of the sheet).
 * $dataRows:  array of arrays, one per data row, aligned to $headerRow.
 * Returns ['inserted'=>n, 'updated'=>n, 'skipped'=>n, 'errors'=>[str,...]].
 */
function crud_import_rows(string $table, array $headerRow, array $dataRows): array
{
    $meta = get_table_meta($table);
    $validColumns = array_keys($meta['columns']);

    // Map each header cell to a real column name (case-insensitive match
    // on column name or label); unrecognised headers are ignored.
    $colMap = []; // sheet index => db column name
    foreach ($headerRow as $i => $h) {
        $h = trim((string)$h);
        foreach ($meta['columns'] as $colName => $colMeta) {
            if (strcasecmp($h, $colName) === 0 || strcasecmp($h, $colMeta['label']) === 0) {
                $colMap[$i] = $colName;
                break;
            }
        }
    }

    $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

    if ($colMap === []) {
        $stats['errors'][] = 'No recognised column headers found in the uploaded file.';
        return $stats;
    }

    $pkSheetIndex = array_search($meta['pk'], $colMap, true);
    $willClearColumns = (CRUD_IMPORT_CLEAR_COLUMNS[$table] ?? []) !== [];
    $db = get_db();

    if ($willClearColumns) {
        $db->beginTransaction();
    }

    try {
        crud_clear_import_columns($table, $meta);

        foreach ($dataRows as $rowNum => $dataRow) {
            $input = [];
            foreach ($colMap as $i => $colName) {
                if (array_key_exists($i, $dataRow)) {
                    $input[$colName] = $dataRow[$i];
                }
            }
            if (array_filter($input, fn($v) => trim((string)$v) !== '') === []) {
                continue; // blank row
            }

            $existingId = null;
            if ($pkSheetIndex !== false && !empty($dataRow[$pkSheetIndex])) {
                $candidate = (string)$dataRow[$pkSheetIndex];
                if (crud_row_exists($table, $candidate)) {
                    $existingId = $candidate;
                }
            }

            // The user table requires a password to create a new login.
            if ($existingId === null && $table === 'user'
                && (!array_key_exists('password', $input) || trim((string)$input['password']) === '')) {
                $stats['skipped']++;
                $stats['errors'][] = 'Row ' . ($rowNum + 2) . ': skipped — a password is required to create a new user.';
                continue;
            }

            // Warn (rather than silently drop) when a select-type column's
            // value doesn't match any valid option — this is a common cause
            // of imports "succeeding" while quietly not applying a field.
            foreach ($input as $colName => $rawVal) {
                $colMeta = $meta['columns'][$colName] ?? null;
                $rawVal = trim((string)$rawVal);
                if ($colMeta && $colMeta['type'] === 'select' && $rawVal !== '' && is_array($colMeta['options'])) {
                    $matches = false;
                    foreach ($colMeta['options'] as $optKey => $opt) {
                        $optValue = is_int($optKey) ? $opt : (string)$optKey;
                        if (strcasecmp($optValue, $rawVal) === 0) {
                            $matches = true;
                            break;
                        }
                    }
                    if (!$matches) {
                        $displayOptions = [];
                        foreach ($colMeta['options'] as $optKey => $opt) {
                            $displayOptions[] = is_int($optKey) ? $opt : (string)$optKey;
                        }
                        $stats['errors'][] = 'Row ' . ($rowNum + 2) . ': "' . $colMeta['label'] . '" value "' . $rawVal
                            . '" is not one of (' . implode(', ', $displayOptions) . ') — left unchanged.';
                    }
                }
            }

            try {
                crud_save($table, $existingId, $input);
                $existingId === null ? $stats['inserted']++ : $stats['updated']++;
            } catch (Throwable $e) {
                $stats['skipped']++;
                $stats['errors'][] = 'Row ' . ($rowNum + 2) . ': ' . $e->getMessage();
            }
        }
    } catch (Throwable $e) {
        if ($willClearColumns) {
            $db->rollBack();
        }
        $stats['errors'][] = 'Import failed and was rolled back: ' . $e->getMessage();
        $stats['inserted'] = 0;
        $stats['updated'] = 0;
        return $stats;
    }

    if ($willClearColumns) {
        $db->commit();
    }

    return $stats;
}
