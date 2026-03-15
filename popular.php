<?php
/**
 * Displays the 5 most visited products (from cookie).
 */
$products = require __DIR__ . '/includes/products_data.php';
$cookie_most = 'kg_most_visited';

$counts = [];
if (!empty($_COOKIE[$cookie_most])) {
    $decoded = json_decode($_COOKIE[$cookie_most], true);
    if (is_array($decoded)) {
        $counts = $decoded;
    }
}

// Sort by visit count descending, take top 5 slugs
arsort($counts, SORT_NUMERIC);
$top_slugs = array_slice(array_keys($counts), 0, 5);

$most_five = [];
foreach ($top_slugs as $slug) {
    foreach ($products as $p) {
        if ($p['slug'] === $slug) {
            $most_five[] = ['product' => $p, 'visits' => $counts[$slug]];
            break;
        }
    }
}

$page_title = '5 Most Visited Products';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section">
    <div class="container">
        <h1>5 most visited products</h1>
        <p class="lead">Products you've viewed the most (tracked via cookie).</p>
        <p style="margin-bottom: 1.5rem;">
            <a href="services.php">&larr; Back to Products &amp; Services</a>
        </p>

        <?php if (empty($most_five)): ?>
            <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1.5rem;">No visit data yet. View some <a href="services.php">products or services</a> to see the most visited here.</p>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($most_five as $item): $p = $item['product']; ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo rawurlencode($p['slug']); ?>">
                            <div class="product-card-image">
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="400" height="300" loading="lazy">
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                <p class="product-card-meta">₹<?php echo number_format($p['price']); ?> &bull; <?php echo htmlspecialchars($p['duration']); ?></p>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0.5rem 0 0;">Viewed <?php echo (int)$item['visits']; ?> time<?php echo $item['visits'] !== 1 ? 's' : ''; ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
