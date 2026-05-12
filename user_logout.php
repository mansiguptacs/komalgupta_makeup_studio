<?php
require_once __DIR__ . '/includes/site_user_auth.php';
$_SESSION['clear_marketplace_token_js'] = 1;
kg_site_user_logout();
header('Location: account.php');
exit;
