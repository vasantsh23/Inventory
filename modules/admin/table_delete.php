<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/crud_engine.php';
require_once __DIR__ . '/../../includes/auth.php';

require_module_access('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$table = (string)($_POST['table'] ?? '');
crud_assert_table($table);
$id = (string)($_POST['id'] ?? '');

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please go back and try again.');
}

// Safety: an admin cannot delete their own logged-in account by mistake.
if ($table === 'user' && $id === (string)current_user()['id']) {
    header('Location: ' . asset_url('/modules/admin/table_view.php?table=user&error=self_delete'));
    exit;
}

if ($id !== '') {
    try {
        crud_delete($table, $id);
    } catch (PDOException $e) {
        // Most commonly a foreign key constraint (e.g. deleting a user_types
        // row that's still assigned to one or more user accounts).
        $reason = str_contains($e->getMessage(), 'a foreign key constraint fails')
            ? 'still_referenced'
            : 'delete_failed';
        header('Location: ' . asset_url('/modules/admin/table_view.php?table=' . urlencode($table) . '&error=' . $reason));
        exit;
    }
}

header('Location: ' . asset_url('/modules/admin/table_view.php?table=' . urlencode($table) . '&deleted=1'));
exit;
