<?php
/**
 * Displays the last 5 previously visited products (from cookie).
 */
$products = require __DIR__ . '/includes/products_data.php';
$cookie_last = 'kg_last_visited';

$slugs = [];
if (!empty($_COOKIE[$cookie_last])) {
    $slugs = array_filter(array_map('trim', explode(',', $_COOKIE[$cookie_last])));
}

$last_five = [];
foreach ($slugs as $slug) {
    foreach ($products as $p) {
        if ($p['slug'] === $slug) {
            $last_five[] = $p;
            break;
        }
    }
}

$page_title = 'Last 5 Visited Products';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section">
    <div class="container">
        <h1>Your last 5 visited products</h1>
        <p class="lead">Products you viewed most recently (tracked via cookie).</p>
        <p style="margin-bottom: 1.5rem;">
            <a href="services.php">&larr; Back to Products &amp; Services</a>
        </p>

        <?php if (empty($last_five)): ?>
            <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1.5rem;">You haven't viewed any product yet. Visit some <a href="services.php">products or services</a> to see them here.</p>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($last_five as $p): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo rawurlencode($p['slug']); ?>">
                            <div class="product-card-image">
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="400" height="300" loading="lazy">
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                <p class="product-card-meta">₹<?php echo number_format($p['price']); ?> &bull; <?php echo htmlspecialchars($p['duration']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
