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

/**
 * Resolve a submitted/imported value for a 'lookup' column to the
 * referenced row's id. Accepts the id itself or — handy for Excel
 * imports — its display label (case-insensitive, e.g. "Admin").
 * Returns null if it matches neither.
 */
function crud_resolve_lookup(array $col, string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $options = (array)$col['options'];
    if (array_key_exists($raw, $options)) {
        return (string)$raw;
    }
    foreach ($options as $optId => $optLabel) {
        if (strcasecmp(trim((string)$optLabel), $raw) === 0) {
            return (string)$optId;
        }
    }
    return null;
}

/**
 * The list-view filters for $table that are actually in effect, taken
 * from $query (normally $_GET['filter']) — only columns allowed by
 * CRUD_LIST_FILTERS, and only values that are real options of that
 * column. Returns [column => stored value].
 */
function crud_active_list_filters(string $table, array $meta, mixed $query): array
{
    $out = [];
    if (!is_array($query)) {
        return $out;
    }
    foreach (CRUD_LIST_FILTERS[$table] ?? [] as $colName) {
        $col = $meta['columns'][$colName] ?? null;
        if ($col === null || !isset($query[$colName]) || !is_scalar($query[$colName])) {
            continue;
        }
        $raw = trim((string)$query[$colName]);
        if ($raw === '') {
            continue;
        }
        if ($col['type'] === 'lookup') {
            if (array_key_exists($raw, (array)$col['options'])) {
                $out[$colName] = $raw;
            }
        } elseif ($col['type'] === 'select' && is_array($col['options'])) {
            foreach ($col['options'] as $optKey => $opt) {
                if ((is_int($optKey) ? $opt : (string)$optKey) === $raw) {
                    $out[$colName] = $raw;
                    break;
                }
            }
        }
    }
    return $out;
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
            'required'  => (bool)($overrides[$name]['required'] ?? false),
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

/**
 * Paginated, optionally-searched list of rows. $filters is
 * [column => exact value], already validated by
 * crud_active_list_filters().
 */
function crud_list(string $table, string $search, int $page, int $perPage = 20, array $filters = []): array
{
    $meta = get_table_meta($table);
    $db = get_db();
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $conditions = [];
    $params = [];

    $f = 0;
    foreach ($filters as $colName => $value) {
        if (!array_key_exists($colName, $meta['columns'])) {
            continue;
        }
        $conditions[] = "`$colName` = :f$f";
        $params[":f$f"] = (string)$value;
        $f++;
    }

    if ($search !== '') {
        $searchable = crud_searchable_columns($meta);
        $clauses = [];
        foreach ($searchable as $i => $col) {
            $clauses[] = "`$col` LIKE :s$i";
            $params[":s$i"] = '%' . $search . '%';
        }
        // Lookup columns store an id, so match the search text against
        // their display labels instead (e.g. searching "Admin" finds rows
        // whose User Type is Admin).
        $l = 0;
        foreach ($meta['columns'] as $colName => $col) {
            if ($col['type'] !== 'lookup') {
                continue;
            }
            $matchIds = [];
            foreach ((array)$col['options'] as $optId => $optLabel) {
                if (stripos((string)$optLabel, $search) !== false) {
                    $matchIds[] = (string)$optId;
                }
            }
            if ($matchIds === []) {
                continue;
            }
            $phs = [];
            foreach ($matchIds as $id) {
                $ph = ":l{$l}";
                $phs[] = $ph;
                $params[$ph] = $id;
                $l++;
            }
            $clauses[] = "`$colName` IN (" . implode(', ', $phs) . ')';
        }
        if ($clauses !== []) {
            $conditions[] = '(' . implode(' OR ', $clauses) . ')';
        }
    }

    $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $orderBy = CRUD_LIST_ORDER[$table] ?? "`{$meta['pk']}` DESC";

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM `$table` $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    $stmt = $db->prepare(
        "SELECT * FROM `$table` $where ORDER BY $orderBy LIMIT :lim OFFSET :off"
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

        if ($col['type'] === 'email' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("\"{$col['label']}\" is not a valid email address.");
        }

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
            $resolved = crud_resolve_lookup($col, $raw);
            if ($resolved === null) {
                // Must be a real id (or label) from the referenced table.
                // A required lookup may not be left out of a new row, nor
                // be blanked/invalid on an edit that includes it.
                if ($col['required'] && ($isNew || array_key_exists($name, $input))) {
                    throw new InvalidArgumentException($raw === ''
                        ? "\"{$col['label']}\" is required."
                        : "\"{$col['label']}\" value \"$raw\" doesn't match any existing option.");
                }
                continue;
            }
            $values[$name] = (int)$resolved;
            continue;
        }

        if ($col['required'] && $raw === '' && ($isNew || array_key_exists($name, $input))) {
            throw new InvalidArgumentException("\"{$col['label']}\" is required.");
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
/**
 * Messages produced while saving (e.g. "approval email sent to …"),
 * collected so the page that called crud_save() can show them.
 * Each entry: ['type' => 'success'|'warning', 'message' => string].
 */
function crud_add_notice(string $type, string $message): void
{
    $GLOBALS['__crud_notices'][] = ['type' => $type, 'message' => $message];
}

/** Return the collected notices and clear the list. */
function crud_take_notices(): array
{
    $notices = $GLOBALS['__crud_notices'] ?? [];
    $GLOBALS['__crud_notices'] = [];
    return $notices;
}

function crud_save(string $table, int|string|null $id, array $input): string
{
    $meta = get_table_meta($table);
    $values = crud_build_values($table, $meta, $input, $id === null);

    $db = get_db();

    // setup table: Registration = yes needs an Admin emailid, because
    // that's where new-registration e-mails go (register.php refuses to
    // run without it). Checks the row as it will be after saving.
    if ($table === 'setup') {
        $current = $id !== null ? (crud_get($table, $id) ?? []) : [];
        $registration = array_key_exists('registration', $values) ? $values['registration'] : ($current['registration'] ?? 'yes');
        $adminEmail = array_key_exists('Admin emailid', $values) ? $values['Admin emailid'] : ($current['Admin emailid'] ?? null);
        if (array_key_exists('registration', $meta['columns'])
            && strcasecmp(trim((string)$registration), 'yes') === 0
            && trim((string)$adminEmail) === '') {
            throw new InvalidArgumentException('"Admin Email (registrations)" is required while Registration is Yes — new registrations are emailed to it. Fill it in, or set Registration to No.');
        }
    }

    // user table: friendly message instead of a raw SQL error when the
    // username or email already belongs to another account (the same
    // check self-registration does).
    if ($table === 'user') {
        $checks = [
            'username'     => 'Another user already has this username.',
            'emailid_hash' => 'Another user already has this email address.',
        ];
        foreach ($checks as $col => $message) {
            if (!isset($values[$col]) || $values[$col] === '') {
                continue;
            }
            $sql = "SELECT 1 FROM `user` WHERE `$col` = :v" . ($id !== null ? " AND `{$meta['pk']}` <> :id" : '') . ' LIMIT 1';
            $dup = $db->prepare($sql);
            $dup->bindValue(':v', $values[$col]);
            if ($id !== null) {
                $dup->bindValue(':id', $id);
            }
            $dup->execute();
            if ($dup->fetchColumn()) {
                throw new InvalidArgumentException($message);
            }
        }
    }

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

    // user table: remember the Approval before this update, so an
    // account moving to "approved" gets an approval email (below).
    $previousApproval = null;
    if ($table === 'user' && array_key_exists('approval', $values)) {
        $prev = $db->prepare("SELECT approval FROM `user` WHERE `{$meta['pk']}` = :id");
        $prev->execute([':id' => $id]);
        $previousApproval = $prev->fetchColumn();
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

    // Approval changed to "approved": tell the user by email. The save
    // has already succeeded; a mail failure is reported, not thrown.
    if ($table === 'user' && $previousApproval !== false && $previousApproval !== null
        && $previousApproval !== 'approved' && ($values['approval'] ?? null) === 'approved') {
        require_once __DIR__ . '/registration.php';
        $mail = user_send_approval_mail($id);
        $who = (string)($values['username'] ?? ('user #' . $id));
        if ($mail['ok']) {
            crud_add_notice('success', 'Approval email sent to ' . $mail['email'] . ' (' . $who . ').');
        } else {
            crud_add_notice('warning', 'Account ' . $who . ' was approved, but the approval email could not be sent'
                . ($mail['email'] !== '' ? ' to ' . $mail['email'] : '') . ': ' . rtrim($mail['error'], '. ')
                . '. Please tell the user yourself.');
        }
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
/**
 * Loosely normalizes a header/label for matching: lowercase, and
 * every run of non-alphanumeric characters (spaces, underscores,
 * hyphens, punctuation) collapsed to nothing. "CSV Header Name",
 * "csv_header_name", "CSV-Header-Name " and "csvheadername" all
 * normalize to the same string, so minor formatting differences in
 * a hand-built import file don't cause a column to be silently
 * skipped.
 */
function crud_normalize_header(string $s): string
{
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $s) ?? '');
}

function crud_import_rows(string $table, array $headerRow, array $dataRows): array
{
    $meta = get_table_meta($table);
    $validColumns = array_keys($meta['columns']);

    // Map each header cell to a real column name — matched, in order
    // of preference, against the exact column name, the exact label,
    // then a loosely-normalized version of either (see
    // crud_normalize_header()). Anything left unmatched is recorded
    // in $unrecognizedHeaders and reported back rather than silently
    // dropped, since a column that fails to map imports as blank for
    // every row with no other visible symptom.
    $colMap = []; // sheet index => db column name
    $unrecognizedHeaders = [];
    foreach ($headerRow as $i => $h) {
        $h = trim((string)$h);
        if ($h === '') {
            continue;
        }
        $matchedCol = null;
        $normalizedH = crud_normalize_header($h);
        foreach ($meta['columns'] as $colName => $colMeta) {
            if (strcasecmp($h, $colName) === 0 || strcasecmp($h, $colMeta['label']) === 0) {
                $matchedCol = $colName;
                break;
            }
        }
        if ($matchedCol === null && $normalizedH !== '') {
            foreach ($meta['columns'] as $colName => $colMeta) {
                if ($normalizedH === crud_normalize_header($colName) || $normalizedH === crud_normalize_header($colMeta['label'])) {
                    $matchedCol = $colName;
                    break;
                }
            }
        }
        if ($matchedCol !== null) {
            $colMap[$i] = $matchedCol;
        } else {
            $unrecognizedHeaders[] = $h;
        }
    }

    $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

    if ($unrecognizedHeaders !== []) {
        $stats['errors'][] = 'Column' . (count($unrecognizedHeaders) === 1 ? '' : 's') . ' not recognized and left unchanged for every row: "'
            . implode('", "', $unrecognizedHeaders) . '". Check the header text exactly matches a column name shown on this table\'s Edit form.';
    }

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
                if ($colMeta && $colMeta['type'] === 'lookup' && !$colMeta['required'] && $rawVal !== ''
                    && crud_resolve_lookup($colMeta, $rawVal) === null) {
                    $stats['errors'][] = 'Row ' . ($rowNum + 2) . ': "' . $colMeta['label'] . '" value "' . $rawVal
                        . '" doesn\'t match any existing option — left unchanged.';
                }
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
                foreach (crud_take_notices() as $notice) {
                    $stats['errors'][] = 'Row ' . ($rowNum + 2) . ': ' . $notice['message'];
                }
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
