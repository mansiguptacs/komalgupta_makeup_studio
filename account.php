<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/auth.php';

$siteUser = kg_site_user();
$isSiteUser = kg_site_user_is_logged_in();
$isAdmin = isAdminLoggedIn();

$page_title = 'Account';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container account-page">
        <h1>Your account</h1>
        <?php if ($isSiteUser): ?>
            <p class="lead">Signed in as <strong><?php echo htmlspecialchars($siteUser['name']); ?></strong> (<?php echo htmlspecialchars($siteUser['email']); ?>).</p>
        <?php elseif ($isAdmin): ?>
            <p class="lead">You are signed in as an administrator.</p>
        <?php else: ?>
            <p class="lead">Choose how you want to continue.</p>
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
                    <p>Sign out from your user account safely.</p>
                    <a class="btn btn-secondary" href="user_logout.php">Logout</a>
                </article>
            <?php else: ?>
                <article class="account-card">
                    <h2>User login</h2>
                    <p>Already have an account? Sign in to manage bookings and reviews.</p>
                    <a class="btn btn-primary" href="user_login.php">User login</a>
                </article>
                <article class="account-card">
                    <h2>Create user account</h2>
                    <p>Register to book appointments and track your history.</p>
                    <a class="btn btn-secondary" href="user_register.php">Create account</a>
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
            <?php else: ?>
                <article class="account-card">
                    <h2>Admin login</h2>
                    <p>Staff/admin users can sign in here.</p>
                    <a class="btn btn-secondary" href="login.php">Admin login</a>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
