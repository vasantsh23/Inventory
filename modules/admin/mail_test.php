<?php
/**
 * mail_test.php
 * Admin > Email Test: shows how the site sends e-mail (registration
 * confirmations, admin notifications) and sends a test message, so
 * delivery problems can be checked without registering a new account.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/registration.php';

require_module_access('admin');

$setup = get_setup() ?? [];
$adminEmail = trim((string)($setup['Admin emailid'] ?? ''));
$company = trim((string)($setup['company'] ?? '')) ?: 'Website';
$result = null;
$to = $adminEmail;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim((string)($_POST['to'] ?? ''));
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $result = ['ok' => false, 'message' => 'Your session expired — please try again.'];
    } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $result = ['ok' => false, 'message' => 'Please enter a valid email address to send the test to.'];
    } else {
        $sent = send_plain_mail(
            $to,
            'Test email from ' . $company,
            "This is a test email from the {$company} website (Admin > Email Test).\n\n"
            . 'Sent: ' . date('Y-m-d H:i:s') . "\n"
            . 'From: ' . mail_from_address() . "\n\n"
            . "If you received this, registration emails can reach this address too.\n",
            $company
        );
        $result = $sent
            ? ['ok' => true, 'message' => (mail_transport() === 'postmark' ? 'Postmark accepted' : 'The server accepted')
                . ' the message for ' . $to . '. Check that inbox — and its spam folder — in the next few minutes.']
            : ['ok' => false, 'message' => 'Sending failed: ' . mail_last_error()];
    }
}

$pageTitle = 'Email Test';
$pageSubtitle = 'Check that registration emails can be delivered';
$activeNav = 'mail_test';
require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <?php if ($result !== null): ?>
        <div class="alert <?= $result['ok'] ? 'alert-success' : 'alert-error' ?>" role="status" style="margin-bottom:18px;"><?= e($result['message']) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>Current settings</h2>
        <p class="panel-desc">
            <?php if (mail_transport() === 'postmark'): ?>
                Registration emails are sent through <strong>Postmark</strong> (POSTMARK_SERVER_TOKEN is set in config/config.php).
            <?php else: ?>
                Registration emails are sent with PHP <code>mail()</code> through this server’s own mail program (Exim on cPanel). To use Postmark instead, set POSTMARK_SERVER_TOKEN and MAIL_FROM_ADDRESS in config/config.php.
            <?php endif; ?>
        </p>
        <table class="data-table">
            <tbody>
                <tr><th style="text-align:left; width:260px;">Sent with</th><td><?= mail_transport() === 'postmark' ? 'Postmark (message stream “' . e((string)POSTMARK_MESSAGE_STREAM) . '”)' : 'PHP mail()' ?></td></tr>
                <tr><th style="text-align:left;">Sent from</th><td><?= e($company) ?> &lt;<?= e(mail_from_address()) ?>&gt;
                    <?php if (mail_transport() === 'postmark' && trim((string)MAIL_FROM_ADDRESS) === ''): ?>
                        <br><span class="text-danger">MAIL_FROM_ADDRESS is not set — Postmark will refuse this address unless it’s verified. Set it in config/config.php.</span>
                    <?php endif; ?></td></tr>
                <tr><th style="text-align:left;">Registration</th><td><?= registration_enabled() ? 'Yes (Register button shown)' : 'No' ?></td></tr>
                <tr><th style="text-align:left;">Admin emailid (Site Setup)</th>
                    <td><?php if ($adminEmail === ''): ?><span class="text-danger">Not set — registration shows an error until this is filled in.</span>
                        <?php elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)): ?><span class="text-danger"><?= e($adminEmail) ?> (not a valid address)</span>
                        <?php else: ?><?= e($adminEmail) ?><?php endif; ?></td></tr>
                <?php if (mail_transport() === 'php_mail'): ?>
                    <tr><th style="text-align:left;">PHP mail() available</th><td><?= function_exists('mail') ? 'Yes' : '<span class="text-danger">No — disabled on this server</span>' ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="panel" style="margin-top:18px;">
        <h2>Send a test email</h2>
        <p class="panel-desc">Try a Gmail address as well as your own domain — Gmail is the strictest about what it accepts.</p>
        <form method="post" action="<?= e(asset_url('/modules/admin/mail_test.php')) ?>" class="crud-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group">
                <label for="to">Send to</label>
                <input type="email" id="to" name="to" value="<?= e($to) ?>" required>
            </div>
            <div class="form-group full-width">
                <div><button type="submit" class="btn btn-accent">Send test email</button></div>
            </div>
        </form>
    </div>

    <div class="panel" style="margin-top:18px;">
        <h2>If the email doesn’t arrive</h2>
        <?php if (mail_transport() === 'postmark'): ?>
        <p class="panel-desc">An error above shows Postmark’s own reason. The usual ones:</p>
        <ol style="line-height:1.7; margin:0; padding-left:20px;">
            <li><strong>“not a Sender Signature”</strong> — the From address isn’t verified. In Postmark → Sender Signatures, add and confirm MAIL_FROM_ADDRESS, or verify your whole domain (DKIM + Return-Path DNS records).</li>
            <li><strong>“pending approval … same domain”</strong> — a new Postmark account can only send to addresses on the From address’s domain until Postmark approves it. Request approval in your Postmark account.</li>
            <li><strong>“stream … not found”</strong> — POSTMARK_MESSAGE_STREAM must be a stream ID that exists on that server (normally <code>outbound</code>).</li>
            <li><strong>HTTP 401</strong> — the token is wrong or was deleted; copy it again from Postmark → your Server → API Tokens.</li>
            <li>Accepted but not in the inbox — Postmark → your Server → <strong>Activity</strong> shows delivered, bounced or blocked for each message.</li>
        </ol>
        <?php else: ?>
        <p class="panel-desc">“The server accepted the message” only means it was handed to the server’s mail program, not that it was delivered.</p>
        <ol style="line-height:1.7; margin:0; padding-left:20px;">
            <li>Check the recipient’s spam / junk folder.</li>
            <li>cPanel → <strong>Email → Track Delivery</strong>: search for the recipient address. It shows whether the message was delivered, deferred or rejected, and the reason.</li>
            <li>cPanel → <strong>Email → Email Deliverability</strong>: SPF and DKIM for your domain should both show as valid. If not, use its “Repair” buttons.</li>
            <li>If Track Delivery shows nothing at all, the host may block PHP mail — ask the hosting provider to check outgoing mail for your account.</li>
        </ol>
        <?php endif; ?>
    </div>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
