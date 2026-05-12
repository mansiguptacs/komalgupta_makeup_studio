<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/sso_client.php';
$page_title = 'Book Appointment';

// Single studio location
$studio_location = 'Civil Lines, Badaun, Uttar Pradesh';

$bookingSuccess = '';
$bookingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $date    = isset($_POST['date']) ? trim($_POST['date']) : '';
    $service = isset($_POST['service']) ? trim($_POST['service']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Optional marketplace metadata (populated via hidden inputs from JS)
    $mpUserId      = isset($_POST['marketplace_user_id'])     ? trim($_POST['marketplace_user_id'])     : '';
    $mpUsername    = isset($_POST['marketplace_username'])    ? trim($_POST['marketplace_username'])    : '';
    $mpProductId   = isset($_POST['marketplace_product_id'])  ? trim($_POST['marketplace_product_id'])  : '';
    $mpProductName = isset($_POST['marketplace_product_name']) ? trim($_POST['marketplace_product_name']) : '';

    if ($name !== '' && $email !== '' && $phone !== '') {
        $file = __DIR__ . '/data/appointments.json';
        $apps = [];
        if (file_exists($file)) {
            $apps = json_decode(file_get_contents($file), true) ?: [];
        }
        $booking = [
            'id' => uniqid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'location' => $studio_location,
            'date' => $date,
            'service' => $service,
            'message' => $message,
            'status' => 'Pending',
            'source' => $mpUserId !== '' ? 'marketplace' : 'website',
            'created_at' => date('Y-m-d H:i:s')
        ];
        if ($mpUserId !== '')      $booking['marketplace_user_id'] = (int)$mpUserId;
        if ($mpUsername !== '')    $booking['marketplace_username'] = $mpUsername;
        if ($mpProductId !== '')   $booking['marketplace_product_id'] = (int)$mpProductId;
        if ($mpProductName !== '') $booking['marketplace_product_name'] = $mpProductName;

        $apps[] = $booking;
        file_put_contents($file, json_encode($apps, JSON_PRETTY_PRINT));

        $bookingSuccess = 'Your appointment request has been received. We will contact you at '
            . htmlspecialchars($email) . ' or ' . htmlspecialchars($phone)
            . ' to confirm your slot at ' . htmlspecialchars($studio_location) . '.';
    } else {
        $bookingError = 'Please fill in all required fields (Name, Email, Phone).';
    }
}

// Pre-fill from URL params (e.g. from a marketplace product detail page)
$prefillService   = isset($_GET['service']) ? (string)$_GET['service'] : '';
$prefillProductId = isset($_GET['mp_product_id']) ? (int)$_GET['mp_product_id'] : 0;

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <h1>Book an Appointment</h1>

        <?php if ($bookingSuccess !== ''): ?>
            <div class="message success"><p><strong>Thank you!</strong> <?php echo $bookingSuccess; ?></p></div>
        <?php endif; ?>
        <?php if ($bookingError !== ''): ?>
            <div class="message error"><p><?php echo htmlspecialchars($bookingError); ?></p></div>
        <?php endif; ?>

        <!--
          Customer identity is PHP session only (Our Marketplace SSO). Optional
          marketplace_user_id on bookings comes from the SSO session.
        -->
        <?php
        $mpUid = (int)($_SESSION['marketplace_user_id'] ?? 0);
        $mpUn = (string)($_SESSION['marketplace_username'] ?? '');
        $su = kg_site_user();
        ?>
        <?php if (kg_site_user_is_logged_in()): ?>
            <div id="auth-status" class="message success" data-state="site">
                You are signed in as <strong><?php echo htmlspecialchars((string)($su['name'] ?? '')); ?></strong>.
                Use your <a href="user_dashboard.php">dashboard</a> for booking history.
            </div>
        <?php else: ?>
            <div id="auth-status" class="message" data-state="anonymous" style="background:var(--color-surface);border:1px solid var(--color-border);">
                <p style="margin:0 0 .5rem;">To link this request to your Our Marketplace account, sign in first.</p>
                <a class="btn btn-primary" href="<?php echo htmlspecialchars(kg_sso_authorize_url(), ENT_QUOTES, 'UTF-8'); ?>">Sign in with Our Marketplace</a>
                <span style="color:var(--color-text-muted);font-size:.85rem;margin-left:.75rem;">New user? <a href="<?php echo htmlspecialchars(kg_sso_marketplace_register_url(), ENT_QUOTES, 'UTF-8'); ?>">Create an account on Our Marketplace</a></span>
            </div>
        <?php endif; ?>

        <p>Fill in your details. We will get back to you to confirm your slot at <strong><?php echo htmlspecialchars($studio_location); ?></strong>.</p>

        <form class="appointment-form" action="appointments.php" method="post">
            <div class="form-row">
                <label for="name">Your Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required value="<?php echo kg_site_user_is_logged_in() ? htmlspecialchars((string)($su['name'] ?? '')) : ''; ?>">
            </div>
            <div class="form-row">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo kg_site_user_is_logged_in() ? htmlspecialchars((string)($su['email'] ?? '')) : ''; ?>">
            </div>
            <div class="form-row">
                <label for="phone">Phone <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-row">
                <label for="date">Preferred Date</label>
                <input type="date" id="date" name="date">
            </div>
            <div class="form-row">
                <label for="service">Service interested in</label>
                <input type="text" id="service" name="service" placeholder="e.g. Bridal, Party makeup" value="<?php echo htmlspecialchars($prefillService); ?>">
            </div>
            <div class="form-row">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" placeholder="Any special requests or notes..."></textarea>
            </div>

            <input type="hidden" name="marketplace_user_id" id="mp-user-id" value="<?php echo $mpUid > 0 ? $mpUid : ''; ?>">
            <input type="hidden" name="marketplace_username" id="mp-username" value="<?php echo htmlspecialchars($mpUn, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="marketplace_product_id" id="mp-product-id" value="<?php echo $prefillProductId > 0 ? (int)$prefillProductId : ''; ?>">
            <input type="hidden" name="marketplace_product_name" id="mp-product-name" value="<?php echo htmlspecialchars($prefillService); ?>">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
