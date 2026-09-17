<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crud_engine.php';

require_memo_level();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired — please go back and try again.');
}

$id = (string)($_POST['id'] ?? '');

if ($id !== '') {
    try {
        crud_delete('customer', $id);
    } catch (PDOException $e) {
        $reason = str_contains($e->getMessage(), 'a foreign key constraint fails')
            ? 'still_referenced'
            : 'delete_failed';
        header('Location: ' . asset_url('/modules/user/customer_list.php?error=' . $reason));
        exit;
    }
}

header('Location: ' . asset_url('/modules/user/customer_list.php?deleted=1'));
exit;
