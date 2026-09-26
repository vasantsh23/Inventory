<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/header.php';
$businessHours = get_business_hours();
?>
    <section class="page-content">
        <h1>Contact Us</h1>
        <?php if (!empty($setup['company'])): ?>
            <p class="contact-company-name"><?= e($setup['company']) ?></p>
        <?php endif; ?>

        <ul class="contact-details">
            <?php if (($addr = format_address($setup)) !== ''): ?>
                <li><strong>Address:</strong> <?= e($addr) ?></li>
            <?php endif; ?>
            <?php if (($phone = primary_phone($setup)) !== ''): ?>
                <li><strong>Telephone:</strong> <?= e($phone) ?></li>
            <?php endif; ?>
            <?php if (($email = primary_email($setup)) !== ''): ?>
                <li><strong>Email:</strong> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
            <?php endif; ?>
        </ul>

        <?php if ($businessHours !== []): ?>
            <h2 class="section-subheading">Business Hours</h2>
            <ul class="contact-details business-hours-list">
                <?php foreach ($businessHours as $row): ?>
                    <li>
                        <strong><?= e($row['Day'] ?? '') ?>:</strong>
                        <?= e(format_business_hours_row($row)) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php
require_once __DIR__ . '/includes/footer.php';
