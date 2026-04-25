<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/site_user_repository.php';

kg_require_site_user();
$user = kg_site_user();
$services = kg_get_services_catalog();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    list($ok, $msg) = kg_create_user_booking($user, $_POST);
    if ($ok) {
        $success = $msg;
    } else {
        $error = $msg;
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$bookingsData = kg_get_user_bookings($user['id'], $page, 5);
$today = date('Y-m-d');

$page_title = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <p class="lead">Book appointments and view your previous/upcoming bookings.</p>

        <?php if (isset($_GET['welcome'])): ?><div class="message success">Account setup complete. You can book now.</div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="message success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <h2>Book an appointment</h2>
        <form class="appointment-form" method="post" action="user_dashboard.php">
            <input type="hidden" name="action" value="book">
            <div class="form-row">
                <label for="name">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
            </div>
            <div class="form-row">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="form-row">
                <label for="cell_phone">Cell phone <span class="required">*</span></label>
                <input type="text" id="cell_phone" name="cell_phone" required value="<?php echo htmlspecialchars($_POST['cell_phone'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="booking_date">Booking date <span class="required">*</span></label>
                <input type="date" id="booking_date" name="booking_date" required value="<?php echo htmlspecialchars($_POST['booking_date'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label for="service_interested_in">Service interested in <span class="required">*</span></label>
                <select id="service_interested_in" name="service_interested_in" required>
                    <option value="">Select a service</option>
                    <?php foreach ($services as $svc): ?>
                        <?php $v = trim((string)($svc['name'] ?? '')); if ($v === '') { continue; } ?>
                        <option value="<?php echo htmlspecialchars($v); ?>" <?php echo (($_POST['service_interested_in'] ?? '') === $v) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="3"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Book Appointment</button>
                <a href="user_reviews.php" class="btn btn-secondary">Add Review/Rating</a>
            </div>
        </form>

        <h2>Your bookings</h2>
        <?php if (empty($bookingsData['rows'])): ?>
            <p class="lead">No bookings yet.</p>
        <?php else: ?>
            <div style="overflow:auto;">
                <table style="width:100%; border-collapse: collapse; background:#fff; border:1px solid #ebe5e1;">
                    <thead>
                        <tr style="background:#fdf8f6;">
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ebe5e1;">Date</th>
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ebe5e1;">Type</th>
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ebe5e1;">Service</th>
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ebe5e1;">Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookingsData['rows'] as $b): ?>
                        <tr>
                            <td style="padding:10px; border-bottom:1px solid #ebe5e1;"><?php echo htmlspecialchars($b['booking_date']); ?></td>
                            <td style="padding:10px; border-bottom:1px solid #ebe5e1;"><?php echo ($b['booking_date'] >= $today) ? 'Upcoming' : 'Previous'; ?></td>
                            <td style="padding:10px; border-bottom:1px solid #ebe5e1;"><?php echo htmlspecialchars($b['service_interested_in']); ?></td>
                            <td style="padding:10px; border-bottom:1px solid #ebe5e1;"><?php echo htmlspecialchars($b['cell_phone']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($bookingsData['pages'] > 1): ?>
                <div style="margin-top:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <?php for ($i = 1; $i <= $bookingsData['pages']; $i++): ?>
                        <a href="user_dashboard.php?page=<?php echo $i; ?>" class="btn <?php echo $i === $bookingsData['page'] ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
