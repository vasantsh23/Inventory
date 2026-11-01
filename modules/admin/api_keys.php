<?php
/**
 * api_keys.php
 * Admin > API: generate a customer's API key (and e-mail it to them),
 * see existing keys, regenerate (replace) a key, and revoke a key.
 * Customers download the stock CSV from api/stock_csv.php by typing their key into its form.
 * Logic: includes/apikey.php.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/apikey.php';

require_module_access('admin');

$flash = null;      // ['type' => 'success'|'warning'|'error', 'message' => ...]
$newKey = '';
$selectedCustid = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $flash = ['type' => 'error', 'message' => 'Your session expired — please try again.'];
    } elseif ($action === 'generate') {
        $selectedCustid = (int)($_POST['custid'] ?? 0);
        try {
            $result = apikey_create_for_customer($selectedCustid);
            if ($result['ok']) {
                // Post/Redirect/Get, so a refresh can't submit twice.
                $_SESSION['apikey_flash'] = [
                    'type'    => $result['mailed'] ? 'success' : 'warning',
                    'message' => $result['message'],
                    'apikey'  => $result['apikey'],
                ];
                header('Location: ' . asset_url('/modules/admin/api_keys.php'), true, 303);
                exit;
            }
            $flash = ['type' => 'error', 'message' => $result['message']];
        } catch (Throwable $e) {
            error_log('api_keys generate failed: ' . $e->getMessage());
            $flash = ['type' => 'error', 'message' => 'The API key could not be generated. Has sql/migration_apikey.sql been run?'];
        }
    } elseif ($action === 'regenerate') {
        try {
            $result = apikey_regenerate((int)($_POST['id'] ?? 0));
            $_SESSION['apikey_flash'] = $result['ok']
                ? ['type' => $result['mailed'] ? 'success' : 'warning', 'message' => $result['message'], 'apikey' => $result['apikey']]
                : ['type' => 'error', 'message' => $result['message']];
        } catch (Throwable $e) {
            error_log('api_keys regenerate failed: ' . $e->getMessage());
            $_SESSION['apikey_flash'] = ['type' => 'error', 'message' => 'The API key could not be regenerated. Please try again.'];
        }
        header('Location: ' . asset_url('/modules/admin/api_keys.php'), true, 303);
        exit;
    } elseif ($action === 'revoke') {
        $row = apikey_revoke((int)($_POST['id'] ?? 0));
        $_SESSION['apikey_flash'] = $row
            ? ['type' => 'success', 'message' => 'API key for "' . $row['name'] . '" (' . $row['emailid'] . ') was revoked. It no longer works, and a new key can now be generated.']
            : ['type' => 'error', 'message' => 'That API key no longer exists.'];
        header('Location: ' . asset_url('/modules/admin/api_keys.php'), true, 303);
        exit;
    }
}

if ($flash === null && isset($_SESSION['apikey_flash'])) {
    $flash = $_SESSION['apikey_flash'];
    $newKey = (string)($flash['apikey'] ?? '');
    unset($_SESSION['apikey_flash']);
}

$tableError = '';
try {
    $customers = apikey_customers();
    $keys = apikey_list();
} catch (Throwable $e) {
    error_log('api_keys list failed: ' . $e->getMessage());
    $customers = $customers ?? [];
    $keys = [];
    $tableError = 'The apikey table is missing or out of date. Please run sql/migration_apikey.sql in phpMyAdmin.';
}

$pageTitle = 'API';
$pageSubtitle = 'Generate API keys for customers';
$activeNav = 'api_keys';
require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <div class="api-tabs">
        <a href="<?= e(asset_url('/modules/admin/api_keys.php')) ?>" class="active" aria-current="page">API keys</a>
        <a href="<?= e(asset_url('/modules/admin/api_columns.php')) ?>">CSV columns</a>
    </div>

    <?php if ($tableError !== ''): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($tableError) ?></div>
    <?php endif; ?>

    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status" style="margin-bottom:18px;">
            <?= e($flash['message']) ?>
            <?php if ($newKey !== ''): ?>
                <div style="margin-top:8px;">API key: <code style="font-size:1rem; user-select:all;"><?= e($newKey) ?></code></div>
                <div style="margin-top:4px; word-break:break-all;">Download page: <code style="user-select:all;"><?= e(apikey_download_url()) ?></code> (the customer enters the key there)</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Generate API key</h2>
        <p class="panel-desc">
            The key is emailed to the customer’s email address from the Customers table, with a link that downloads the
            current stock list as a CSV file (the columns set to Extract = Yes under <a href="<?= e(asset_url('/modules/admin/api_columns.php')) ?>">CSV columns</a>; held stones follow the Hold Restriction setting).
            Each customer can have one key; customers without an email address can’t be given one.
        </p>
        <form method="post" action="<?= e(asset_url('/modules/admin/api_keys.php')) ?>" class="crud-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="generate">
            <div class="form-group full-width" style="max-width:640px;">
                <label for="custid-search">Search customer</label>
                <input type="search" id="custid-search" data-select-search="custid" data-select-search-size="10" autocomplete="off"
                       placeholder="Type part of the name or email…" aria-controls="custid">
                <small data-select-search-count="custid" aria-live="polite" style="color:var(--text-muted); margin:4px 0 10px; display:block;"></small>
                <label for="custid">Customer <span style="color:var(--text-muted); font-weight:normal;">(click one)</span></label>
                <select id="custid" name="custid" required style="height:auto;">
                    <option value="">— Select customer —</option>
                    <?php foreach ($customers as $c):
                        $cEmail = trim((string)$c['email']);
                    ?>
                        <option value="<?= (int)$c['custid'] ?>" <?= (int)$c['custid'] === $selectedCustid ? 'selected' : '' ?>>
                            <?= e((string)$c['custnm']) ?> — <?= $cEmail !== '' ? e($cEmail) : '(no email)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full-width">
                <div><button type="submit" class="btn btn-accent">Generate API key</button></div>
            </div>
        </form>
    </div>

    <div class="panel" style="margin-top:18px;">
        <h2>Existing API keys</h2>
        <p class="panel-desc"><strong>Regenerate</strong> replaces a key and emails the new one to the customer; the old key stops working immediately.
            <strong>Revoke</strong> deletes the key without a replacement.</p>
        <?php if ($keys === []): ?>
            <p>No API keys yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Customer</th><th>Email</th><th>API key</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keys as $k): ?>
                            <tr>
                                <td data-label="Customer">
                                    <?= e((string)($k['custnm'] ?? '') !== '' ? (string)$k['custnm'] : (string)$k['name']) ?>
                                    <?php if ($k['custid'] === null): ?><br><small style="color:var(--text-muted);">older key, not linked to a customer</small><?php endif; ?>
                                </td>
                                <td data-label="Email"><?= e((string)$k['emailid']) ?></td>
                                <td data-label="API key"><code><?= e(apikey_mask((string)$k['apikey'])) ?></code></td>
                                <td data-label="Created"><?= e((string)($k['created_at'] ?? '')) ?></td>
                                <td class="row-actions" data-label="Actions">
                                    <form method="post" action="<?= e(asset_url('/modules/admin/api_keys.php')) ?>" style="display:inline;"
                                          data-confirm="Replace the API key for <?= e((string)$k['name']) ?> and email the new one? The current key will stop working immediately.">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="regenerate">
                                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                        <button type="submit" class="btn btn-sm">Regenerate</button>
                                    </form>
                                    <form method="post" action="<?= e(asset_url('/modules/admin/api_keys.php')) ?>" style="display:inline;"
                                          data-confirm="Revoke the API key for <?= e((string)$k['name']) ?>? It will stop working immediately.">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="<?= e(asset_url_versioned('/assets/js/select_search.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
