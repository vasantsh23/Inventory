<?php
/** Website -> Enquiries: messages sent from the website's contact form. */
declare(strict_types=1);
require_once __DIR__ . '/../../site/admin_helpers.php';

$ready = site_admin_ready();
$status = (string) ($_GET['status'] ?? 'new');
$statuses = ['new' => 'New', 'read' => 'Read', 'archived' => 'Archived', 'all' => 'All'];
if (!isset($statuses[$status])) {
    $status = 'new';
}

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    theme_csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id > 0 && in_array($action, ['new', 'read', 'archived'], true)) {
        get_db()->prepare('UPDATE website_enquiries SET status = ? WHERE id = ?')->execute([$action, $id]);
        theme_flash('success', 'Enquiry marked as ' . ($action === 'new' ? 'new' : $action) . '.');
    } elseif ($id > 0 && $action === 'delete') {
        get_db()->prepare('DELETE FROM website_enquiries WHERE id = ?')->execute([$id]);
        theme_flash('success', 'Enquiry deleted.');
    }
    $back = (string) ($_POST['status'] ?? 'new');
    theme_redirect('site_enquiries.php?status=' . urlencode(isset($statuses[$back]) ? $back : 'new'));
}

$counts = ['new' => 0, 'read' => 0, 'archived' => 0, 'all' => 0];
$rows = [];
if ($ready) {
    foreach (get_db()->query('SELECT status, COUNT(*) c FROM website_enquiries GROUP BY status')->fetchAll() as $r) {
        $counts[$r['status']] = (int) $r['c'];
        $counts['all'] += (int) $r['c'];
    }
    $sql = 'SELECT * FROM website_enquiries' . ($status !== 'all' ? ' WHERE status = ?' : '') . ' ORDER BY created_at DESC, id DESC LIMIT 500';
    $st = get_db()->prepare($sql);
    $st->execute($status !== 'all' ? [$status] : []);
    $rows = $st->fetchAll();
}

$pageTitle = 'Website Enquiries';
$pageSubtitle = 'Messages sent from the contact form on the website. New messages are also emailed when the server can send mail.';
$activeNav = 'site_enquiries';
require_once __DIR__ . '/../../includes/admin_header.php';
echo site_admin_css();
$pages = Site::defaults();
?>
    <?= theme_render_flashes() ?>
    <?php if (!$ready): ?>
        <?= site_admin_missing_notice() ?>
    <?php else: ?>
    <nav class="ts-chips" aria-label="Filter enquiries">
        <?php foreach ($statuses as $k => $l): ?>
            <a class="ts-chip<?= $k === $status ? ' is-active' : '' ?>" href="<?= e(site_admin_url('site_enquiries.php', ['status' => $k])) ?>"><?= e($l) ?> <span><?= $counts[$k] ?></span></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$rows): ?>
        <div class="panel ts-empty"><p><strong>No <?= $status === 'all' ? '' : e(strtolower($statuses[$status])) . ' ' ?>enquiries.</strong></p>
            <p class="panel-desc">When someone sends the contact form on the website, their message appears here.</p></div>
    <?php else: ?>
        <div class="se-list">
            <?php foreach ($rows as $r): ?>
                <details class="panel se-item se-item--<?= e($r['status']) ?>">
                    <summary>
                        <span class="se-when"><?= e(date('d M Y, H:i', strtotime((string) $r['created_at']))) ?></span>
                        <span class="se-who"><strong><?= e($r['name']) ?></strong><?= $r['company'] ? ' · ' . e($r['company']) : '' ?></span>
                        <span class="se-excerpt"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', (string) $r['message']), 0, 90, '…')) ?></span>
                        <?php if ($r['status'] === 'new'): ?><span class="pill pill-warning">New</span><?php endif; ?>
                    </summary>
                    <div class="se-body">
                        <dl class="se-meta">
                            <div><dt>Email</dt><dd><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd></div>
                            <?php if ($r['phone']): ?><div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $r['phone'])) ?>"><?= e($r['phone']) ?></a></dd></div><?php endif; ?>
                            <?php if ($r['location']): ?><div><dt>Country / city</dt><dd><?= e($r['location']) ?></dd></div><?php endif; ?>
                            <div><dt>Sent from</dt><dd><?= e($pages[$r['page_key']]['label'] ?? (string) $r['page_key']) ?> page</dd></div>
                        </dl>
                        <div class="se-message"><?= nl2br(e((string) $r['message'])) ?></div>
                        <form method="post" class="se-actions">
                            <?= theme_csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
                            <a class="btn btn-sm btn-accent" href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode('Re: your enquiry to ' . Site::company()) ?>">Reply by email</a>
                            <?php if ($r['status'] !== 'read'): ?><button class="btn btn-sm" name="action" value="read">Mark as read</button><?php endif; ?>
                            <?php if ($r['status'] !== 'new'): ?><button class="btn btn-sm" name="action" value="new">Mark as new</button><?php endif; ?>
                            <?php if ($r['status'] !== 'archived'): ?><button class="btn btn-sm" name="action" value="archived">Archive</button><?php endif; ?>
                            <button class="btn btn-sm btn-danger" name="action" value="delete" data-confirm="Delete this enquiry from <?= e($r['name']) ?>? This cannot be undone.">Delete</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
