<?php
/**
 * Session-based admin authentication helper.
 * Use requireAdmin() at the top of any secure page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current user is logged in as admin.
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_user']) && $_SESSION['admin_user'] === 'admin';
}

/**
 * Require admin login. Redirects to login page with return URL if not logged in.
 * Call at the top of secure pages.
 * @param string $loginPage Path to login page (e.g. 'login.php')
 */
function requireAdmin($loginPage = 'login.php') {
    if (!isAdminLoggedIn()) {
        $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'secure/users.php');
        $sep = strpos($loginPage, '?') === false ? '?' : '&';
        header('Location: ' . $loginPage . $sep . 'return=' . $returnUrl);
        exit;
    }
}
