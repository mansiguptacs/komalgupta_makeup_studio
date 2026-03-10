<?php
/**
 * Shared header for Komal Gupta Makeup Studio
 * Defines page title and active nav for current page.
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if (empty($current_page)) {
    $current_page = 'index';
}
$page_title = isset($page_title) ? $page_title : 'Komal Gupta Makeup Studio';
// When included from secure/, links must go up one level
$base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/secure/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | KG Makeup Studio</title>
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
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo $base; ?>index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $base; ?>about.php" class="<?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="<?php echo $base; ?>services.php" class="<?php echo $current_page === 'services' ? 'active' : ''; ?>">Products &amp; Services</a></li>
                    <li><a href="<?php echo $base; ?>appointments.php" class="<?php echo $current_page === 'appointments' ? 'active' : ''; ?>">Book Appointment</a></li>
                    <li><a href="<?php echo $base; ?>news.php" class="<?php echo $current_page === 'news' ? 'active' : ''; ?>">News</a></li>
                    <li><a href="<?php echo $base; ?>contact.php" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>
                    <li><a href="<?php echo $base; ?>secure/users.php" class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main-content">
