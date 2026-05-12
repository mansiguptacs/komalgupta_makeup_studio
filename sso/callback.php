<?php
/**
 * OurMarketplace SSO callback: exchanges ?code= for a session on this site.
 */
require_once __DIR__ . '/../includes/site_user_auth.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_user_repository.php';
require_once __DIR__ . '/../includes/sso_client.php';

if (isAdminLoggedIn()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SSO</title></head><body>';
    echo '<p>An administrator session is active. Log out from admin first, then use Our Marketplace sign-in.</p>';
    echo '<p><a href="../login.php">Admin login page</a> · <a href="../account.php">Account</a></p>';
    echo '</body></html>';
    exit;
}

$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
if ($code === '') {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SSO error</title></head><body>';
    echo '<p>No authorization code was returned. <a href="../sso/start.php">Try sign-in again</a> or <a href="../account.php">Account</a></p>';
    echo '</body></html>';
    exit;
}

list($ok, $data, $err) = kg_sso_exchange_code($code);
if (!$ok || !is_array($data)) {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SSO error</title></head><body>';
    echo '<p>Could not complete sign-in: ' . htmlspecialchars($err ?: 'Unknown error') . '</p>';
    echo '<p><a href="../sso/start.php">Try sign-in again</a> · <a href="../account.php">Account</a></p>';
    echo '</body></html>';
    exit;
}

$token = (string)$data['token'];
$user = $data['user'];
$mpId = (int)($user['id'] ?? 0);
$mpUsername = (string)($user['username'] ?? '');
$mpFull = (string)($user['full_name'] ?? '');
if ($mpId <= 0) {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SSO error</title></head><body>';
    echo '<p>Invalid user payload from marketplace.</p><p><a href="../sso/start.php">Try sign-in again</a></p>';
    echo '</body></html>';
    exit;
}

list($uok, $row, $uerr) = kg_sso_upsert_site_user_from_marketplace($mpId, $mpUsername, $mpFull);
if (!$uok || !is_array($row)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SSO error</title></head><body>';
    echo '<p>' . htmlspecialchars((string)$uerr) . '</p><p><a href="../sso/start.php">Try sign-in again</a></p>';
    echo '</body></html>';
    exit;
}

kg_site_user_login($row);
$_SESSION['marketplace_token_pending_sync'] = $token;
$_SESSION['marketplace_access_token'] = $token;
$_SESSION['marketplace_user_id'] = $mpId;
$_SESSION['marketplace_username'] = $mpUsername;

header('Location: ../user_dashboard.php?welcome=sso');
exit;
