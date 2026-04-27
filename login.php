<?php
/**
 * Administrator login page.
 * User ID and password are validated against file-based credentials (hashed + salt).
 */
require_once __DIR__ . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: secure/users.php');
    exit;
}

$page_title = 'Admin Login';
$current_page = 'login';
require_once __DIR__ . '/includes/header.php';

$returnUrl = isset($_GET['return']) ? htmlspecialchars($_GET['return']) : 'secure/users.php';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
if (isSiteUserSessionActive()) {
    $error = 'A user account session is already active. Please logout from user account first, then login as admin.';
}
?>

<section class="page-section">
    <div class="container">
        <h1>Administrator Login</h1>
        <p class="lead">Sign in to access the secure section.</p>

        <div id="login-message" class="message error" style="<?php echo $error ? '' : 'display:none;'; ?>">
            <?php echo $error ?: 'Invalid user ID or password.'; ?>
        </div>

        <form id="login-form" class="appointment-form" method="post" action="api/login.php">
            <input type="hidden" name="return" value="<?php echo $returnUrl; ?>">
            <div class="form-row">
                <label for="userid">User ID <span class="required">*</span></label>
                <input type="text" id="userid" name="userid" required autocomplete="username" value="">
            </div>
            <div class="form-row">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>
    </div>
</section>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var msg = document.getElementById('login-message');
    var returnUrl = form.querySelector('input[name="return"]').value;
    var payload = {
        userid: form.userid.value.trim(),
        password: form.password.value,
        return: returnUrl
    };

    fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            msg.textContent = data.error || 'Invalid user ID or password.';
            msg.className = 'message error';
            msg.style.display = 'block';
        }
    })
    .catch(function() {
        msg.textContent = 'Connection error. Please try again.';
        msg.className = 'message error';
        msg.style.display = 'block';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
