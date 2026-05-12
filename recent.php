<?php
/**
 * Dedicated "Last visited products" view.
 *
 * Reads the unified localStorage store maintained by assets/js/marketplace.js
 * (KGMarketplace.recordVisit), which is populated from both product.php (local
 * products) and view_product.php (marketplace products), so the list
 * mixes both sources.
 */
$page_title = 'Last Visited Products';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section">
    <div class="container">
        <h1>Your last visited products</h1>
        <p class="lead">Products and services you viewed most recently in this browser (mixes our studio's local products and the marketplace).</p>
        <p style="margin-bottom: 1.5rem;">
            <a href="services.php">&larr; Back to Products &amp; Services</a>
            <a href="#" id="recent-clear" style="margin-left:1rem;color:var(--color-text-muted);text-decoration:underline;font-size:.9rem;">Clear history</a>
        </p>

        <p id="recent-desc" style="color:var(--color-text-muted);">Loading...</p>
        <div id="recent-container"></div>
    </div>
</section>

<script>
(function(){
    if (typeof KGMarketplace === 'undefined') {
        document.getElementById('recent-desc').textContent = 'KGMarketplace JS library failed to load.';
        return;
    }

    function escapeHtml(s){
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatTimeAgo(ms){
        if (!ms) return '';
        var diff = Date.now() - ms;
        if (diff < 60 * 1000)      return 'just now';
        if (diff < 3600 * 1000)    return Math.floor(diff / 60000) + ' min ago';
        if (diff < 86400 * 1000)   return Math.floor(diff / 3600000) + ' hr ago';
        return Math.floor(diff / 86400000) + ' day' + (Math.floor(diff/86400000) === 1 ? '' : 's') + ' ago';
    }

    function renderCard(entry){
        var img = entry.image
            ? '<img src="' + escapeHtml(entry.image) + '" alt="' + escapeHtml(entry.name || '') + '" width="400" height="300" loading="lazy">'
            : '<div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>';
        var price = (entry.price != null && entry.price !== '') ? '$' + Number(entry.price).toLocaleString('en-IN') : '';
        var category = entry.category ? ' &bull; ' + escapeHtml(entry.category) : '';
        var duration = entry.duration ? ' &bull; ' + escapeHtml(entry.duration) : '';
        var sourceBadge = entry.type === 'marketplace'
            ? '<span style="font-size:.7rem;background:var(--color-bg);color:var(--color-text-muted);padding:.1rem .45rem;border-radius:4px;margin-left:.4rem;text-transform:uppercase;letter-spacing:.05em;">Marketplace</span>'
            : '';
        var visited = '<p style="font-size:.8rem;color:var(--color-text-muted);margin:.25rem 0 0;">Viewed ' + entry.count + ' time' + (entry.count !== 1 ? 's' : '') + ' &middot; last ' + formatTimeAgo(entry.lastVisited) + '</p>';
        var href = entry.href || '#';
        return '<div class="product-card"><a href="' + escapeHtml(href) + '">'
            + '<div class="product-card-image">' + img + '</div>'
            + '<div class="product-card-body">'
            + '<h3>' + escapeHtml(entry.name || 'Unnamed') + sourceBadge + '</h3>'
            + '<p class="product-card-meta">' + price + category + duration + '</p>'
            + visited
            + '</div>'
            + '</a></div>';
    }

    var descEl = document.getElementById('recent-desc');
    var container = document.getElementById('recent-container');

    function render(){
        var recent = KGMarketplace.getRecentVisited(20); // show up to 20 here
        if (!recent || recent.length === 0) {
            descEl.textContent = "You haven't viewed any product yet. Visit some products or services to see them here.";
            container.innerHTML = '';
            return;
        }
        descEl.textContent = 'Showing your last ' + recent.length + ' visited product' + (recent.length !== 1 ? 's' : '') + '.';
        var html = '<div class="products-grid">';
        recent.forEach(function(r){ html += renderCard(r); });
        html += '</div>';
        container.innerHTML = html;
    }

    document.getElementById('recent-clear').addEventListener('click', function(e){
        e.preventDefault();
        if (confirm('Clear your recently viewed list on this browser?')) {
            KGMarketplace.clearRecentVisited();
            render();
        }
    });

    render();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
