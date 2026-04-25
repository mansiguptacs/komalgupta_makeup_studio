<?php
require_once __DIR__ . '/includes/site_user_auth.php';
$page_title = 'Book Appointment';
require_once __DIR__ . '/includes/header.php';

// Single studio location
$studio_location = 'Civil Lines, Badaun, Uttar Pradesh';
?>
<section class="page-section">
    <div class="container">
        <h1>Book an Appointment</h1>
        <?php if (!kg_site_user_is_logged_in()): ?>
            <div class="message error">
                Please <a href="user_login.php">login</a> to book and track appointments with pagination history.
            </div>
        <?php else: ?>
            <div class="message success">
                You are logged in. Use your <a href="user_dashboard.php">dashboard</a> for booking and history.
            </div>
        <?php endif; ?>
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
                <input type="text" id="service" name="service" placeholder="e.g. Bridal, Party makeup">
            </div>
            <div class="form-row">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" placeholder="Any special requests or notes..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
            $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $date    = isset($_POST['date']) ? trim($_POST['date']) : '';
            $service = isset($_POST['service']) ? trim($_POST['service']) : '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';

            $valid = $name !== '' && $email !== '' && $phone !== '';

            if ($valid) {
                $file = __DIR__ . '/data/appointments.json';
                $apps = [];
                if (file_exists($file)) {
                    $apps = json_decode(file_get_contents($file), true) ?: [];
                }
                $apps[] = [
                    'id' => uniqid(),
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $studio_location,
                    'date' => $date,
                    'service' => $service,
                    'message' => $message,
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                file_put_contents($file, json_encode($apps, JSON_PRETTY_PRINT));

                echo '<div class="message success"><p><strong>Thank you!</strong> Your appointment request has been received. We will contact you at ' . htmlspecialchars($email) . ' or ' . htmlspecialchars($phone) . ' to confirm your slot at ' . htmlspecialchars($studio_location) . '.</p></div>';
            } else {
                echo '<div class="message error"><p>Please fill in all required fields (Name, Email, Phone).</p></div>';
            }
        }
        ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
