<?php
$page_title = 'Book Appointment';
require_once __DIR__ . '/includes/header.php';

$locations = [
    'faridabad' => 'Faridabad (Sector 15)',
    'badaun'    => 'Badaun, UP (Civil Lines)',
];
?>
<section class="page-section">
    <div class="container">
        <h1>Book an Appointment</h1>
        <p>Fill in your details and choose a location. We will get back to you to confirm your slot.</p>

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
                <label for="location">Studio Location <span class="required">*</span></label>
                <select id="location" name="location" required>
                    <option value="">— Select location —</option>
                    <?php foreach ($locations as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
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
            $location = isset($_POST['location']) ? trim($_POST['location']) : '';
            $date    = isset($_POST['date']) ? trim($_POST['date']) : '';
            $service = isset($_POST['service']) ? trim($_POST['service']) : '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';

            $valid = $name !== '' && $email !== '' && $phone !== '' && $location !== '';

            if ($valid) {
                $location_label = isset($locations[$location]) ? $locations[$location] : $location;
                echo '<div class="message success"><p><strong>Thank you!</strong> Your appointment request has been received. We will contact you at ' . htmlspecialchars($email) . ' or ' . htmlspecialchars($phone) . ' to confirm your slot at ' . htmlspecialchars($location_label) . '.</p></div>';
            } else {
                echo '<div class="message error"><p>Please fill in all required fields (Name, Email, Phone, Location).</p></div>';
            }
        }
        ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
