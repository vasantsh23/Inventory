<?php
/**
 * Contact Us page. Layout comes from the website template chosen in
 * Admin → Appearance → Website Template; text and images come from
 * Admin → Manage Tables → Website Content (page = "contact").
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    site_contact_submit(); // validates, saves to contact_messages, redirects back
}
site_render('contact');
