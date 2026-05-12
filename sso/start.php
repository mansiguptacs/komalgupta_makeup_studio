<?php
/**
 * Customer sign-in entry: redirects to OurMarketplace SSO authorize.
 */
require_once __DIR__ . '/../includes/site_user_auth.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sso_client.php';

if (kg_site_user_is_logged_in()) {
    header('Location: ../user_dashboard.php');
    exit;
}

if (isAdminLoggedIn()) {
    header('Location: ../account.php?sso_error=' . rawurlencode('Log out from admin before signing in as a customer.'));
    exit;
}

$url = kg_sso_authorize_url();
if ($url === '#' || $url === '') {
    header('Location: ../account.php?sso_error=' . rawurlencode('SSO is not configured (check config/sso.php).'));
    exit;
}

header('Location: ' . $url);
exit;
