<?php
$page_title = 'Contact';
require_once __DIR__ . '/includes/header.php';

$contacts_file = __DIR__ . '/data/contacts.txt';
$contacts = [];
if (file_exists($contacts_file) && is_readable($contacts_file)) {
    $content = file_get_contents($contacts_file);
    $blocks = explode("\n\n", str_replace("\r", "", trim($content)));
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $contact = [];
        $lines = explode("\n", $block);
        foreach ($lines as $line) {
            $parts = explode(":", $line, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $contact[$key] = trim($parts[1]);
            }
        }
        if (!empty($contact)) {
            $contacts[] = $contact;
        }
    }
}
?>
<section class="page-section">
    <div class="container">
        <h1>Contact Us</h1>
        <p>Reach out to us at any of our locations.</p>

        <?php if (!empty($contacts)): ?>
            <div class="contacts-grid">
                <?php foreach ($contacts as $row): ?>
                    <?php
                    $label = $row['label'] ?? $row['category'] ?? 'Contact';
                    $cat = $row['category'] ?? '';
                    $phone = $row['phone'] ?? '';
                    $email = $row['email'] ?? '';
                    $address = $row['address'] ?? '';
                    $notes = $row['notes'] ?? '';
                    ?>
                    <div class="contact-card">
                        <h3><?php echo htmlspecialchars($label); ?></h3>
                        <?php if ($cat && $cat !== $label): ?>
                        <p class="contact-category"><?php echo htmlspecialchars($cat); ?></p>
                        <?php endif; ?>
                        <dl class="contact-details">
                            <?php if ($phone !== ''): ?>
                                <dt>Phone</dt>
                                <dd><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phone)); ?>"><?php echo htmlspecialchars($phone); ?></a></dd>
                            <?php endif; ?>
                            <?php if ($email !== ''): ?>
                                <dt>Email</dt>
                                <dd><a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></dd>
                            <?php endif; ?>
                            <?php if ($address !== ''): ?>
                                <dt>Address</dt>
                                <dd><?php echo htmlspecialchars($address); ?></dd>
                            <?php endif; ?>
                            <?php if ($notes !== ''): ?>
                                <dt>Hours / Notes</dt>
                                <dd><?php echo htmlspecialchars($notes); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="message error">Contact information is currently unavailable. </p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
