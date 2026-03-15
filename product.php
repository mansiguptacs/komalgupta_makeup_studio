<?php
/**
 * Single product/service page. Updates last-visited and most-visited cookies.
 */
$products = require __DIR__ . '/includes/products_data.php';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$product = null;
foreach ($products as $p) {
    if ($p['slug'] === $slug) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: services.php');
    exit;
}

// Cookie names and expiry (1 year)
$cookie_last = 'kg_last_visited';
$cookie_most = 'kg_most_visited';
$expiry = time() + (86400 * 365);

// --- Last 5 visited: comma-separated slugs, newest first ---
$last_visited = [];
if (!empty($_COOKIE[$cookie_last])) {
    $last_visited = array_filter(array_map('trim', explode(',', $_COOKIE[$cookie_last])));
}
$last_visited = array_values(array_diff($last_visited, [$slug]));
array_unshift($last_visited, $slug);
$last_visited = array_slice($last_visited, 0, 5);
setcookie($cookie_last, implode(',', $last_visited), $expiry, '/', '', false, true);

// --- Most visited: JSON object slug => count ---
$most_visited = [];
if (!empty($_COOKIE[$cookie_most])) {
    $decoded = json_decode($_COOKIE[$cookie_most], true);
    if (is_array($decoded)) {
        $most_visited = $decoded;
    }
}
if (!isset($most_visited[$slug])) {
    $most_visited[$slug] = 0;
}
$most_visited[$slug]++;
setcookie($cookie_most, json_encode($most_visited), $expiry, '/', '', false, true);

$page_title = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section product-detail-section">
    <div class="container">
        <p style="margin-bottom: 1rem;">
            <a href="services.php" style="color: var(--color-primary); text-decoration: none;">&larr; Back to Products &amp; Services</a>
        </p>

        <div class="product-detail-card">
            <div class="product-detail-image">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="800" height="500" loading="eager">
            </div>
            <div class="product-detail-body">
                <p class="product-detail-category"><?php echo htmlspecialchars($product['category']); ?></p>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-detail-meta">
                    <span>₹<?php echo number_format($product['price']); ?></span>
                    <span><?php echo htmlspecialchars($product['duration']); ?></span>
                </p>
                <div class="product-detail-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                <p style="margin-top: 1.5rem;">
                    <a href="appointments.php" class="btn btn-primary">Book this service</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
