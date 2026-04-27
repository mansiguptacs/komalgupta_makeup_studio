<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/site_user_repository.php';
require_once __DIR__ . '/includes/auth.php';

if (kg_site_user_is_logged_in()) {
    header('Location: user_dashboard.php');
    exit;
}

$error = '';
if (isAdminLoggedIn()) {
    $error = 'Admin session is already active. Please logout from admin first, then login as user.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isAdminLoggedIn()) {
        $error = 'Admin session is already active. Please logout from admin first, then login as user.';
    } else {
        list($ok, $result) = kg_authenticate_site_user($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($ok) {
            kg_site_user_login($result);
            header('Location: user_dashboard.php');
            exit;
        }
        $error = (string)$result;
    }
}

$page_title = 'User Login';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <h1>User login</h1>
        <p class="lead">Sign in to book appointments and view your booking history.</p>
        <?php if ($error !== ''): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form class="appointment-form" method="post" action="user_login.php">
            <div class="form-row">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="user_register.php" class="btn btn-secondary">Create account</a>
            </div>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
