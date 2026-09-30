<?php /** Shown only to admins who are previewing a template that is not live. */
$live = Site::liveTemplate(); ?>
<div class="preview-bar" role="region" aria-label="Template preview">
    <p>Previewing <strong><?= e(Site::get($template)['name']) ?></strong>. Visitors still see <?= e(Site::get($live)['name']) ?>.</p>
    <form method="post" action="<?= e(asset_url('/modules/admin/site_templates.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="activate" value="<?= e($template) ?>">
        <input type="hidden" name="return" value="<?= e($page) ?>">
        <button type="submit">Make this template live</button>
    </form>
    <a href="<?= e(Site::pageUrl($page) . '?preview=off') ?>">Exit preview</a>
</div>
