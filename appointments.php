<?php
require_once __DIR__ . '/includes/site_user_auth.php';
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

        <?php if (!kg_site_user_is_logged_in()): ?>
            <div class="message error">
                Please <a href="user_login.php">login</a> to book and track appointments.
            </div>
        <?php else: ?>
            <div class="message success">
                You are logged in. Use your <a href="user_dashboard.php">dashboard</a> for booking and history.
            </div>
        <?php endif; ?>

        <!-- Marketplace banner - populated client-side -->
        <div id="mp-banner" style="display:none;margin:1rem 0;padding:.85rem 1rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:10px;">
            <p id="mp-banner-text" style="margin:0;"></p>
            <button type="button" id="mp-banner-action" class="btn btn-secondary" style="margin-top:.5rem;padding:.35rem .9rem;font-size:.85rem;display:none;">Login to Marketplace</button>
        </div>

        <p>Fill in your details. We will get back to you to confirm your slot at <strong><?php echo htmlspecialchars($studio_location); ?></strong>.</p>

        <form class="appointment-form" action="appointments.php" method="post">
            <div class="form-row">
                <label for="name">Your Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-row">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required>
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

            <!-- Marketplace metadata: populated by JS when the user is logged in to marketplace. -->
            <input type="hidden" name="marketplace_user_id" id="mp-user-id" value="">
            <input type="hidden" name="marketplace_username" id="mp-username" value="">
            <input type="hidden" name="marketplace_product_id" id="mp-product-id" value="<?php echo $prefillProductId > 0 ? (int)$prefillProductId : ''; ?>">
            <input type="hidden" name="marketplace_product_name" id="mp-product-name" value="<?php echo htmlspecialchars($prefillService); ?>">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</section>

<!-- Marketplace Login Modal -->
<div id="mp-auth-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.5);overflow-y:auto;">
    <div style="max-width:420px;margin:4rem auto;background:var(--color-surface);border-radius:12px;padding:2rem;position:relative;box-shadow:0 16px 48px rgba(0,0,0,0.15);">
        <button type="button" id="mp-auth-close" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--color-text-muted);">&times;</button>
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
    if (typeof KGMarketplace === 'undefined') return;

    // Only show the marketplace banner when the visitor is logged into *this* site.
    // Otherwise it clashes with the "Please log in to book and track appointments" notice.
    var siteUserLoggedIn = <?php echo json_encode(kg_site_user_is_logged_in()); ?>;

    var banner       = document.getElementById('mp-banner');
    var bannerText   = document.getElementById('mp-banner-text');
    var bannerAction = document.getElementById('mp-banner-action');
    var nameEl       = document.getElementById('name');
    var emailEl      = document.getElementById('email');
    var mpUserIdEl   = document.getElementById('mp-user-id');
    var mpUsernameEl = document.getElementById('mp-username');
    var modal        = document.getElementById('mp-auth-modal');
    var closeBtn     = document.getElementById('mp-auth-close');
    var loginForm    = document.getElementById('mp-login-form');
    var authMsg      = document.getElementById('mp-auth-msg');

    function applyMpUser(user) {
        // Always sync hidden fields + optional pre-fill when we have a marketplace session
        if (user) {
            if (!nameEl.value && (user.full_name || user.username)) nameEl.value = user.full_name || user.username;
            if (!emailEl.value && user.email) emailEl.value = user.email;
            mpUserIdEl.value   = user.id != null ? user.id : '';
            mpUsernameEl.value = user.username || '';
        } else {
            mpUserIdEl.value   = '';
            mpUsernameEl.value = '';
        }

        if (!siteUserLoggedIn) {
            if (banner) banner.style.display = 'none';
            return;
        }

        if (user) {
            bannerText.innerHTML = 'Logged in to marketplace as <strong>' + (user.full_name || user.username) + '</strong>. Name and email pre-filled.';
            bannerAction.style.display = 'none';
            banner.style.display = '';
        } else {
            bannerText.innerHTML = 'Have a marketplace account? Login to auto-fill your name and email.';
            bannerAction.textContent = 'Login to Marketplace';
            bannerAction.style.display = 'inline-block';
            banner.style.display = '';
        }
    }

    KGMarketplace.verify().then(applyMpUser).catch(function(){ applyMpUser(null); });

    if (bannerAction) bannerAction.addEventListener('click', function(){ modal.style.display = 'block'; });
    if (closeBtn)     closeBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
    if (modal)        modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });

    if (loginForm) loginForm.addEventListener('submit', function(e){
        e.preventDefault();
        authMsg.style.display = 'none';
        var user = document.getElementById('mp-login-user').value.trim();
        var pass = document.getElementById('mp-login-pass').value;
        KGMarketplace.login(user, pass).then(function(r){
            if (r.success) {
                authMsg.className = 'message success';
                authMsg.textContent = 'Logged in!';
                authMsg.style.display = 'block';
                applyMpUser(r.user);
                setTimeout(function(){ modal.style.display = 'none'; }, 600);
            } else {
                authMsg.className = 'message error';
                authMsg.textContent = r.error || 'Login failed.';
                authMsg.style.display = 'block';
            }
        }).catch(function(){
            authMsg.className = 'message error';
            authMsg.textContent = 'Network error.';
            authMsg.style.display = 'block';
        });
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
