<?php
/**
 * Shared header for Komal Gupta Makeup Studio
 * Defines page title and active nav for current page.
 */
require_once __DIR__ . '/site_user_auth.php';
require_once __DIR__ . '/auth.php';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if (empty($current_page)) {
    $current_page = 'index';
}
if (empty($page_title) || !is_string($page_title)) {
    $page_title = 'Komal Gupta Makeup Studio';
}
$isUserLoggedIn = kg_site_user_is_logged_in();
$isAdmin = isAdminLoggedIn();
$profileActivePages = ['account', 'user_login', 'user_register', 'user_dashboard', 'user_reviews', 'login'];
$isProfileActive = in_array($current_page, $profileActivePages, true);
// When included from secure/, links must go up one level
$base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/secure/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(trim($page_title)); ?> | Komal Gupta Makeup Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?php echo $base; ?>index.php" class="logo">
                <span class="logo-letters">KG</span>
                <span class="logo-text">Komal Gupta Makeup Studio</span>
            </a>
            <nav class="main-nav" aria-label="Main navigation">
                <ul>
                    <li><a href="<?php echo $base; ?>index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $base; ?>about.php" class="<?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="<?php echo $base; ?>team.php" class="<?php echo $current_page === 'team' ? 'active' : ''; ?>">Team</a></li>
                    <li><a href="<?php echo $base; ?>services.php" class="<?php echo $current_page === 'services' ? 'active' : ''; ?>">Products &amp; Services</a></li>
                    <li><a href="<?php echo $base; ?>appointments.php" class="<?php echo $current_page === 'appointments' ? 'active' : ''; ?>">Book Appointment</a></li>
                    <li><a href="<?php echo $base; ?>news.php" class="<?php echo $current_page === 'news' ? 'active' : ''; ?>">News</a></li>
                    <li><a href="<?php echo $base; ?>contact.php" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>
                </ul>
            </nav>
            <a href="<?php echo $base; ?>account.php" class="profile-nav-link <?php echo $isProfileActive ? 'active' : ''; ?>" aria-label="Open account menu">
                <span class="profile-nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.87 0-7 3.13-7 7h2c0-2.76 2.24-5 5-5s5 2.24 5 5h2c0-3.87-3.13-7-7-7z"/>
                    </svg>
                </span>
                <span class="profile-nav-text">
                    <?php if ($isUserLoggedIn): ?>
                        <?php echo htmlspecialchars((string)(kg_site_user()['name'] ?? 'My Account')); ?>
                    <?php elseif ($isAdmin): ?>
                        Admin
                    <?php else: ?>
                        Account
                    <?php endif; ?>
                </span>
            </a>
        </div>
    </header>
    <main class="main-content">
