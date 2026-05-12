<?php
/**
 * Legacy URL: accounts are created on OurMarketplace, then customers use SSO here.
 */
require_once __DIR__ . '/includes/sso_client.php';

$url = kg_sso_marketplace_register_url();
if ($url === '#' || $url === '') {
    header('Location: account.php?sso_error=' . rawurlencode('Registration URL is not configured.'));
    exit;
}

header('Location: ' . $url, true, 302);
exit;
