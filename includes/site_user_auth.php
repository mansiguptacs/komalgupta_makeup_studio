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
        'name' => trim((string)($userRow['first_name'] ?? '') . ' ' . (string)($userRow['last_name'] ?? '')),
        'email' => (string)$userRow['email'],
    ];
}

function kg_site_user_logout() {
    unset($_SESSION['site_user']);
}

function kg_require_site_user($redirect = 'user_login.php') {
    if (!kg_site_user_is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}
