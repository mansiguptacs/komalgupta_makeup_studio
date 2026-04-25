<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/site_user_repository.php';

if (kg_site_user_is_logged_in()) {
    header('Location: user_dashboard.php');
    exit;
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($ok, $result) = kg_register_site_user($_POST);
    if ($ok) {
        kg_site_user_login($result);
        header('Location: user_dashboard.php?welcome=1');
        exit;
    }
    $error = (string)$result;
}

$page_title = 'User Register';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <h1>Create your account</h1>
        <p class="lead">Register to book appointments and manage your bookings.</p>

        <?php if ($message !== ''): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form class="appointment-form" method="post" action="user_register.php">
            <div class="form-row">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="cell_phone">Cell Phone <span class="required">*</span></label>
                <input type="text" id="cell_phone" name="cell_phone" required value="<?php echo htmlspecialchars($_POST['cell_phone'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="home_phone">Home Phone</label>
                <input type="text" id="home_phone" name="home_phone" value="<?php echo htmlspecialchars($_POST['home_phone'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="home_address">Home Address</label>
                <textarea id="home_address" name="home_address" rows="3"><?php echo htmlspecialchars($_POST['home_address'] ?? ''); ?></textarea>
            </div>
            <div class="form-row">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="user_login.php" class="btn btn-secondary">Already have an account?</a>
            </div>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
