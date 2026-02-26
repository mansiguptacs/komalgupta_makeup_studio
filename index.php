<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <h1>Welcome to Komal Gupta Makeup Studio</h1>
        <p class="tagline">Where beauty meets artistry</p>
        <p>We offer professional makeup and beauty services at our studios in <strong>Faridabad</strong> and <strong>Badaun, UP</strong>. Book an appointment or explore our services.</p>
        <div class="hero-actions">
            <a href="services.php" class="btn btn-primary">Our Services</a>
            <a href="appointments.php" class="btn btn-secondary">Book Appointment</a>
        </div>
    </div>
</section>

<section class="stats-banner" style="background: var(--color-surface); padding: 2rem 0; border-top: 1px solid var(--color-border);">
    <div class="container">
        <div class="no-of-customer-cards" style="padding: 0; border: none; background: transparent;">
            <div class="no-of-customer-card" style="display: flex; align-items: baseline; justify-content: center; gap: 8px;">
                <h3 style="margin: 0;">3000+</h3>
                <p>Happy Customers</p>
            </div>
            <div class="no-of-customer-card" style="display: flex; align-items: baseline; justify-content: center; gap: 8px;">
                <h3 style="margin: 0;">5+ Years</h3>
                <p>of Service</p>
            </div>
            <div class="no-of-customer-card" style="display: flex; align-items: baseline; justify-content: center; gap: 8px;">
                <h3 style="margin: 0;">50+</h3>
                <p>Premium Services</p>
            </div>
        </div>
    </div>
</section>

<section class="locations-preview">
    <div class="container">
        <h2>Our Locations</h2>
        <div class="location-cards">
            <div class="location-card">
                <h3>Faridabad</h3>
                <p>Sector 15, Faridabad, Haryana</p>
            </div>
            <div class="location-card">
                <h3>Badaun, UP</h3>
                <p>Civil Lines, Badaun, Uttar Pradesh</p>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
