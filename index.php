<?php
$page_title = 'Home';
// Google Maps link — replace with your Business Profile URL (maps.app.goo.gl/...) if you have one.
$google_maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Komal Gupta Makeup Studio, Badaun, Uttar Pradesh, India');
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <h1>Welcome to Komal Gupta Makeup Studio</h1>
        <p class="tagline">Where beauty meets artistry</p>
        <p>We offer professional makeup and beauty services at our studio in <strong>Civil Lines, Badaun, Uttar Pradesh</strong>. Book an appointment or explore our services.</p>
        <div class="hero-actions">
            <a href="services.php" class="btn btn-primary">Our Services</a>
            <a href="appointments.php" class="btn btn-secondary">Book Appointment</a>
            <a href="<?php echo htmlspecialchars($google_maps_url); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">View on Google Maps</a>
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
        <h2>Our Studio</h2>
        <div class="location-cards">
            <div class="location-card">
                <h3>Badaun</h3>
                <p>Civil Lines, Badaun, Uttar Pradesh</p>
                <p style="margin-top: 1rem;">
                    <a href="<?php echo htmlspecialchars($google_maps_url); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        Open location in Google Maps
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
