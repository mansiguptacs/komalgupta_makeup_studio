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
            <p class="lead" id="account-lead">Signed in as <strong><?php echo htmlspecialchars($siteUser['name']); ?></strong> (<?php echo htmlspecialchars($siteUser['email']); ?>).</p>
        <?php elseif ($isAdmin): ?>
            <p class="lead" id="account-lead">You are signed in as an administrator.</p>
        <?php else: ?>
            <p class="lead" id="account-lead">Choose how you want to continue. A marketplace login alone is enough &mdash; you don't need a separate site account.</p>
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
            <?php elseif (!$isAdmin): ?>
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
            <?php elseif (!$isSiteUser): ?>
                <article class="account-card">
                    <h2>Admin login</h2>
                    <p>Staff/admin users can sign in here.</p>
                    <a class="btn btn-secondary" href="login.php">Admin login</a>
                </article>
            <?php endif; ?>

            <!-- Marketplace account -->
            <article class="account-card" id="mp-account-card">
                <h2>Marketplace</h2>
                <div id="mp-status-area">
                    <p style="color:var(--color-text-muted);">Checking marketplace status...</p>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- Marketplace Login Modal -->
<div id="mp-auth-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.5);overflow-y:auto;">
    <div style="max-width:420px;margin:4rem auto;background:var(--color-surface);border-radius:12px;padding:2rem;position:relative;box-shadow:0 16px 48px rgba(0,0,0,0.15);">
        <button id="mp-auth-close" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--color-text-muted);">&times;</button>
        <h2 style="font-family:var(--font-heading);margin:0 0 1rem;">Marketplace Login</h2>
        <div id="mp-auth-msg" class="message" style="display:none;margin-bottom:1rem;"></div>
        <form id="mp-login-form" class="appointment-form" style="margin:0;">
            <div class="form-row"><label for="mp-login-user">Username or Email</label><input type="text" id="mp-login-user" required></div>
            <div class="form-row"><label for="mp-login-pass">Password</label><input type="password" id="mp-login-pass" required></div>
            <div class="form-actions" style="gap:.5rem;">
                <button type="submit" class="btn btn-primary">Login to Marketplace</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var statusArea = document.getElementById('mp-status-area');
    var leadEl     = document.getElementById('account-lead');
    var modal      = document.getElementById('mp-auth-modal');
    var closeBtn   = document.getElementById('mp-auth-close');
    var loginForm  = document.getElementById('mp-login-form');
    var msgEl      = document.getElementById('mp-auth-msg');

    var siteUserLoggedIn = <?php echo json_encode($isSiteUser); ?>;
    var isAdmin          = <?php echo json_encode($isAdmin); ?>;

    function escapeHtml(s){
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderStatus(user) {
        if (user && (user.full_name || user.username)) {
            statusArea.innerHTML = '<p>Signed in to marketplace as <strong>'
                + escapeHtml(user.full_name || user.username) + '</strong>'
                + (user.email ? ' (' + escapeHtml(user.email) + ')' : '') + '.</p>'
                + '<button class="btn btn-secondary" id="mp-logout-btn">Marketplace Logout</button>';
            document.getElementById('mp-logout-btn').addEventListener('click', function(){
                KGMarketplace.logout();
                renderStatus(null);
                // If the site session was already absent, refresh the lead too.
                if (!siteUserLoggedIn && !isAdmin && leadEl) {
                    leadEl.innerHTML = 'Choose how you want to continue. A marketplace login alone is enough &mdash; you don\u2019t need a separate site account.';
                }
            });
            // If only marketplace is in play, surface that as the primary "logged in" state.
            if (!siteUserLoggedIn && !isAdmin && leadEl) {
                leadEl.innerHTML = 'Signed in via marketplace as <strong>'
                    + escapeHtml(user.full_name || user.username) + '</strong>'
                    + (user.email ? ' (' + escapeHtml(user.email) + ')' : '') + '. '
                    + 'No separate site account is required.';
            }
        } else {
            statusArea.innerHTML = '<p>Connect your account to the marketplace to access shared products and reviews.</p>'
                + '<button class="btn btn-primary" id="mp-open-login">Login to Marketplace</button>';
            document.getElementById('mp-open-login').addEventListener('click', function(){
                modal.style.display = 'block';
            });
        }
    }

    KGMarketplace.onAuthReady(renderStatus);

    if (loginForm) loginForm.addEventListener('submit', function(e){
        e.preventDefault();
        msgEl.style.display = 'none';
        var user = document.getElementById('mp-login-user').value.trim();
        var pass = document.getElementById('mp-login-pass').value;
        KGMarketplace.login(user, pass).then(function(r){
            if (r.success) {
                msgEl.className = 'message success'; msgEl.textContent = 'Logged in!'; msgEl.style.display = 'block';
                renderStatus(r.user);
                setTimeout(function(){ modal.style.display = 'none'; }, 800);
            } else {
                msgEl.className = 'message error'; msgEl.textContent = r.error || 'Login failed.'; msgEl.style.display = 'block';
            }
        }).catch(function(){
            msgEl.className = 'message error'; msgEl.textContent = 'Network error.'; msgEl.style.display = 'block';
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
