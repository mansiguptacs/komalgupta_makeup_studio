<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function kg_site_user_is_logged_in() {
    return isset($_SESSION['site_user']) && is_array($_SESSION['site_user']) && !empty($_SESSION['site_user']['id']);
}

function kg_site_user() {
    return kg_site_user_is_logged_in() ? $_SESSION['site_user'] : null;
}

function kg_site_user_login($userRow) {
    $_SESSION['site_user'] = [
        'id' => (int)$userRow['id'],
        'first_name' => (string)($userRow['first_name'] ?? ''),
        'last_name' => (string)($userRow['last_name'] ?? ''),
        'name' => trim(trim((string)($userRow['first_name'] ?? '') . ' ' . (string)($userRow['last_name'] ?? ''))),
        'email' => (string)$userRow['email'],
    ];
}

function kg_site_user_logout() {
    unset($_SESSION['site_user']);
    unset($_SESSION['marketplace_token_pending_sync']);
    unset($_SESSION['marketplace_access_token']);
    unset($_SESSION['marketplace_user_id']);
    unset($_SESSION['marketplace_username']);
}

function kg_require_site_user($redirect = 'sso/start.php') {
    if (!kg_site_user_is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}
