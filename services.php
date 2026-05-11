<?php
/**
 * Products & Services — three sections: Most Popular, All Services, Your Visited.
 */
$products = require __DIR__ . '/includes/products_data.php';
$cookie_last = 'kg_last_visited';
$cookie_most = 'kg_most_visited';

// Last 5 visited (newest first)
$last_slugs = [];
if (!empty($_COOKIE[$cookie_last])) {
    $last_slugs = array_filter(array_map('trim', explode(',', $_COOKIE[$cookie_last])));
}
$last_five = [];
foreach ($last_slugs as $slug) {
    foreach ($products as $p) {
        if ($p['slug'] === $slug) {
            $last_five[] = $p;
            break;
        }
    }
}

// Most visited (top 5 by count)
$counts = [];
if (!empty($_COOKIE[$cookie_most])) {
    $decoded = json_decode($_COOKIE[$cookie_most], true);
    if (is_array($decoded)) {
        $counts = $decoded;
    }
}
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

function render_product_card($p, $extra = null) {
    $body = '<h3>' . htmlspecialchars($p['name']) . '</h3>';
    $body .= '<p class="product-card-meta">₹' . number_format($p['price']) . ' &bull; ' . htmlspecialchars($p['duration']) . '</p>';
    if ($extra) {
        $body .= $extra;
    }
    return '<div class="product-card">'
        . '<a href="product.php?slug=' . rawurlencode($p['slug']) . '">'
        . '<div class="product-card-image">'
        . '<img src="' . htmlspecialchars($p['image']) . '" alt="' . htmlspecialchars($p['name']) . '" width="400" height="300" loading="lazy">'
        . '</div>'
        . '<div class="product-card-body">' . $body . '</div>'
        . '</a>'
        . '</div>';
}

// --- Fetch marketplace products via cURL ---
$marketplaceProducts = [];
$marketplaceCompany = '';
$marketplaceError = '';
if (function_exists('curl_init')) {
    $mpCh = curl_init('https://mansiguptacs.com/ourmarketplace/api/products.php?company_id=1');
    curl_setopt($mpCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($mpCh, CURLOPT_TIMEOUT, 8);
    curl_setopt($mpCh, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($mpCh, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($mpCh, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($mpCh, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $mpBody = curl_exec($mpCh);
    $mpCode = (int)curl_getinfo($mpCh, CURLINFO_HTTP_CODE);
    $mpCurlErr = curl_error($mpCh);
    curl_close($mpCh);

    if ($mpCurlErr) {
        $marketplaceError = 'cURL error: ' . $mpCurlErr;
    } elseif ($mpCode < 200 || $mpCode >= 300) {
        $marketplaceError = 'Marketplace API returned HTTP ' . $mpCode;
    } else {
        $mpData = json_decode($mpBody, true);
        if (is_array($mpData) && isset($mpData['products']) && is_array($mpData['products'])) {
            $marketplaceProducts = $mpData['products'];
            $marketplaceCompany = (string)($mpData['company'] ?? 'Marketplace');
        } else {
            $marketplaceError = 'Marketplace API returned unexpected JSON format.';
        }
    }
} else {
    $marketplaceError = 'cURL extension is not enabled on this host.';
}

$page_title = 'Products & Services';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section services-sections">
    <div class="container">
        <h1>Products &amp; Services</h1>
        <p class="lead">Explore our makeup, beauty, and styling services. Click any product for full details and to book.</p>

        <div class="tracking-links">
            <a href="popular.php">Show 5 most visited products</a>
            <a href="recent.php">Show your last 5 visited products</a>
        </div>

        <!-- 1. Most Popular services -->
        <div class="services-section">
            <h2 class="services-section-title">Most popular services</h2>
            <p class="services-section-desc">Services you've viewed the most.</p>
            <?php if (empty($most_five)): ?>
                <p class="services-section-empty">Visit a few services below to see your most popular here.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($most_five as $item): ?>
                        <?php
                        $extra = '<p class="product-card-visits">Viewed ' . (int)$item['visits'] . ' time' . ($item['visits'] !== 1 ? 's' : '') . '</p>';
                        echo render_product_card($item['product'], $extra);
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. All services -->
        <div class="services-section">
            <h2 class="services-section-title">All services</h2>
            <p class="services-section-desc">Full list of what we offer.</p>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                    <?php echo render_product_card($p); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. Your visited services -->
        <div class="services-section">
            <h2 class="services-section-title">Your visited services</h2>
            <p class="services-section-desc">The last 5 services you viewed.</p>
            <?php if (empty($last_five)): ?>
                <p class="services-section-empty">Click any service above to see your recently visited list here.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($last_five as $p): ?>
                        <?php echo render_product_card($p); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Marketplace Products (fetched via cURL) -->
        <div class="services-section">
            <h2 class="services-section-title">Marketplace Products<?php if ($marketplaceCompany !== ''): ?> — <?php echo htmlspecialchars($marketplaceCompany); ?><?php endif; ?></h2>
            <p class="services-section-desc">Products from our marketplace partner, fetched live via cURL. (<?php echo count($marketplaceProducts); ?> products)</p>
            <?php if ($marketplaceError !== ''): ?>
                <p class="message error"><?php echo htmlspecialchars($marketplaceError); ?></p>
            <?php elseif (empty($marketplaceProducts)): ?>
                <p class="services-section-empty">No marketplace products available right now.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($marketplaceProducts as $mp): ?>
                        <div class="product-card">
                            <div class="product-card-image">
                                <?php if (!empty($mp['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($mp['image_url']); ?>" alt="<?php echo htmlspecialchars($mp['name'] ?? ''); ?>" width="400" height="300" loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($mp['name'] ?? 'Unnamed'); ?></h3>
                                <p class="product-card-meta">
                                    <?php if (!empty($mp['price'])): ?>₹<?php echo number_format((float)$mp['price']); ?><?php endif; ?>
                                    <?php if (!empty($mp['category'])): ?> &bull; <?php echo htmlspecialchars($mp['category']); ?><?php endif; ?>
                                </p>
                                <?php if (!empty($mp['description'])): ?>
                                    <p style="font-size:.9rem;color:var(--color-text-muted);margin-top:.25rem;"><?php echo htmlspecialchars($mp['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
