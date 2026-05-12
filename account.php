<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sso_client.php';

$siteUser = kg_site_user();
$isSiteUser = kg_site_user_is_logged_in();
$isAdmin = isAdminLoggedIn();
$ssoError = isset($_GET['sso_error']) ? (string)$_GET['sso_error'] : '';

$page_title = 'Account';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container account-page">
        <h1>Your account</h1>
        <?php if ($ssoError !== ''): ?>
            <div class="message error"><?php echo htmlspecialchars($ssoError); ?></div>
        <?php endif; ?>
        <?php if ($isSiteUser): ?>
            <p class="lead" id="account-lead">Sign in with OurMarketplace (SSO)</p>
        <?php elseif ($isAdmin): ?>
            <p class="lead" id="account-lead">You are signed in as an administrator.</p>
        <?php else: ?>
            <p class="lead" id="account-lead">Customer accounts use <strong>OurMarketplace</strong> single sign-on. Create an account there if you need one, then sign in here.</p>
        <?php endif; ?>

        <div class="account-grid">
            <?php if ($isSiteUser): ?>
                <article class="account-card">
                    <h2>User dashboard</h2>
                    <p>Book appointments and see previous/upcoming bookings with pagination.</p>
                    <a class="btn btn-primary" href="user_dashboard.php">Go to dashboard</a>
                </article>
                <article class="account-card">
                    <h2>My reviews</h2>
                    <p>Add or update product/service ratings and reviews.</p>
                    <a class="btn btn-secondary" href="user_reviews.php">Open reviews</a>
                </article>
                <article class="account-card">
                    <h2>Logout</h2>
                    <p>Sign out from this site (your marketplace session may still be active in the browser until you log out there).</p>
                    <a class="btn btn-secondary" href="user_logout.php">Logout</a>
                </article>
            <?php elseif (!$isAdmin): ?>
                <article class="account-card">
                    <h2>Sign in</h2>
                    <p>Use the same OurMarketplace account for this studio site.</p>
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars(kg_sso_authorize_url(), ENT_QUOTES, 'UTF-8'); ?>">Sign in with Our Marketplace</a>
                </article>
                <article class="account-card">
                    <h2>Create account</h2>
                    <p>Register on OurMarketplace, then return here and use Sign in.</p>
                    <a class="btn btn-secondary" href="<?php echo htmlspecialchars(kg_sso_marketplace_register_url(), ENT_QUOTES, 'UTF-8'); ?>">Create account on Our Marketplace</a>
                </article>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <article class="account-card">
                    <h2>Admin dashboard</h2>
                    <p>Manage secure users and admin content.</p>
                    <a class="btn btn-primary" href="secure/users.php">Open admin dashboard</a>
                </article>
                <article class="account-card">
                    <h2>Admin logout</h2>
                    <p>Sign out from the admin session.</p>
                    <a class="btn btn-secondary" href="api/logout.php">Admin logout</a>
                </article>
            <?php elseif (!$isSiteUser): ?>
                <article class="account-card">
                    <h2>Admin login</h2>
                    <p>Staff and administrators sign in here (separate from customer SSO).</p>
                    <a class="btn btn-secondary" href="login.php">Admin login</a>
                </article>
            <?php endif; ?>

            <article class="account-card">
                <h2>OurMarketplace</h2>
                <p>Browse products, reviews, and your marketplace profile in a new tab.</p>
                <a class="btn btn-secondary" href="<?php echo htmlspecialchars(rtrim((string)(kg_sso_config()['provider_base'] ?? ''), '/') . '/', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Open Our Marketplace</a>
            </article>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
