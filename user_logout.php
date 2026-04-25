<?php
require_once __DIR__ . '/includes/site_user_auth.php';
kg_site_user_logout();
header('Location: user_login.php');
exit;
