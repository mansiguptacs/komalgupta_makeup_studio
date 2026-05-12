<?php
/**
 * Products & Services
 *
 * Sections:
 *   1. Top 5 at our studio       — from marketplace API (company_id = 1)
 *   2. Recently viewed by you    — per-browser localStorage (mixes local + marketplace items)
 *   3. Marketplace products      — full company catalog
 *
 * All sections are rendered client-side because the marketplace API is fetched
 * cross-origin. Local "recently viewed" entries are also written into
 * localStorage by product.php, so they show alongside marketplace items.
 *
 * Marketplace-wide rankings are intentionally NOT shown on this site — this
 * page is dedicated to the studio's own catalog and its per-browser history.
 */
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

        <!-- Ranking method tabs (drive sections 1 and 2) -->
        <div class="ranking-tabs" role="tablist" aria-label="Ranking method" style="display:flex;gap:.5rem;flex-wrap:wrap;margin:1rem 0 0;">
            <button type="button" class="btn btn-secondary ranking-tab active" data-method="best_rated">Best Rated</button>
            <button type="button" class="btn btn-secondary ranking-tab" data-method="most_visited">Most Visited</button>
            <button type="button" class="btn btn-secondary ranking-tab" data-method="most_reviewed">Most Reviewed</button>
        </div>

        <!-- 1. Top 5 at our studio -->
        <div class="services-section">
            <h2 class="services-section-title" id="top-studio-heading">Top 5 at our studio</h2>
            <p class="services-section-desc" id="top-studio-desc">Loading top products from our studio...</p>
            <div id="top-studio-container"></div>
        </div>

        <!-- 2. Recently viewed by you -->
        <div class="services-section">
            <h2 class="services-section-title">Recently viewed by you</h2>
            <p class="services-section-desc" id="recent-desc">The products and services you've viewed most recently in this browser.</p>
            <div id="recent-container"></div>
        </div>

        <!-- 4. Full marketplace catalog -->
        <div class="services-section">
            <h2 class="services-section-title" id="mp-products-heading">Products</h2>
            <p class="services-section-desc" id="mp-products-desc">Loading products...</p>
            <div id="mp-products-container"></div>
        </div>
    </div>
</section>

<script>
(function(){
    if (typeof KGMarketplace === 'undefined') {
        document.getElementById('top-studio-desc').textContent = 'KGMarketplace JS library failed to load.';
        return;
    }

    function escapeHtml(s){
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderStars(avg){
        var rounded = Math.round(Number(avg) || 0);
        var s = '';
        for (var i = 1; i <= 5; i++) s += (i <= rounded) ? '★' : '☆';
        return s;
    }

    // ----- Top-N card (from marketplace API response) ---------------------
    function renderTopCard(mp, rank){
        var img = mp.image_url
            ? '<img src="' + escapeHtml(mp.image_url) + '" alt="' + escapeHtml(mp.name || '') + '" width="400" height="300" loading="lazy">'
            : '<div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>';

        var avg = parseFloat(mp.avg_rating || 0);
        var reviewCount = parseInt(mp.review_count || 0, 10);
        var visitCount = parseInt(mp.visit_count || 0, 10);
        var price = (mp.price != null && mp.price !== '') ? '$' + Number(mp.price).toLocaleString('en-IN') : '';
        // var company = mp.company_name ? ' &bull; ' + escapeHtml(mp.company_name) : '';

        var rankBadge = '<span style="display:inline-block;background:var(--color-primary);color:#fff;width:1.6rem;height:1.6rem;border-radius:50%;text-align:center;line-height:1.6rem;font-weight:700;font-size:.85rem;margin-right:.4rem;">' + rank + '</span>';
        var ratingLine = '<p style="font-size:.85rem;color:var(--color-primary);margin:.25rem 0 0;font-weight:600;">'
            + renderStars(avg) + ' ' + (avg > 0 ? avg.toFixed(1) + '/5' : 'Not rated')
            + ' <span style="font-weight:400;color:var(--color-text-muted);">'
            + '(' + reviewCount + ' review' + (reviewCount !== 1 ? 's' : '') + ', '
            + visitCount + ' visit' + (visitCount !== 1 ? 's' : '') + ')</span></p>';

        var href = mp.id != null ? 'view_product.php?id=' + encodeURIComponent(mp.id) : null;
        var inner = '<div class="product-card-image">' + img + '</div>'
            + '<div class="product-card-body">'
            + '<h3>' + rankBadge + escapeHtml(mp.name || 'Unnamed') + '</h3>'
            + '<p class="product-card-meta">' + price + '</p>'
            + ratingLine
            + '</div>';

        if (href) return '<div class="product-card"><a href="' + href + '">' + inner + '</a></div>';
        return '<div class="product-card">' + inner + '</div>';
    }

    function renderTopList(container, descEl, products, emptyMsg){
        if (!products || products.length === 0) {
            descEl.textContent = emptyMsg;
            container.innerHTML = '';
            return;
        }
        var html = '<div class="products-grid">';
        for (var i = 0; i < products.length; i++) {
            html += renderTopCard(products[i], i + 1);
        }
        html += '</div>';
        container.innerHTML = html;
    }

    function methodLabel(method){
        if (method === 'most_visited')  return 'Most Visited';
        if (method === 'most_reviewed') return 'Most Reviewed';
        return 'Best Rated';
    }

    // ----- Top section (load per method) ----------------------------------
    var studioDesc      = document.getElementById('top-studio-desc');
    var studioContainer = document.getElementById('top-studio-container');

    function loadTopSections(method){
        studioDesc.textContent = 'Loading top products from our studio (' + methodLabel(method) + ')...';
        studioContainer.innerHTML = '';

        KGMarketplace.getTopProducts({ company_id: KGMarketplace.COMPANY_ID, method: method, limit: 5 })
            .then(function(data){
                var list = (data && data.products) || [];
                studioDesc.textContent = 'Our studio\u2019s top 5 \u2014 ' + methodLabel(method) + '.';
                renderTopList(studioContainer, studioDesc, list, 'No ranked products yet for our studio.');
            })
            .catch(function(){
                studioDesc.innerHTML = '<span class="message error" style="display:inline-block;padding:.4rem .75rem;">Could not load studio rankings.</span>';
            });
    }

    // Wire up tab buttons
    var tabs = document.querySelectorAll('.ranking-tab');
    tabs.forEach(function(btn){
        btn.addEventListener('click', function(){
            tabs.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            loadTopSections(btn.getAttribute('data-method'));
        });
    });
    loadTopSections('best_rated');

    // ----- Recently viewed (localStorage, mixes local + marketplace) ------
    function renderRecentCard(entry){
        var img = entry.image
            ? '<img src="' + escapeHtml(entry.image) + '" alt="' + escapeHtml(entry.name || '') + '" width="400" height="300" loading="lazy">'
            : '<div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>';

        var price = (entry.price != null && entry.price !== '') ? '₹' + Number(entry.price).toLocaleString('en-IN') : '';
        var category = entry.category ? ' &bull; ' + escapeHtml(entry.category) : '';
        var duration = entry.duration ? ' &bull; ' + escapeHtml(entry.duration) : '';
        var sourceBadge = entry.type === 'marketplace'
            ? '<span style="font-size:.7rem;background:var(--color-bg);color:var(--color-text-muted);padding:.1rem .45rem;border-radius:4px;margin-left:.4rem;text-transform:uppercase;letter-spacing:.05em;">Marketplace</span>'
            : '';
        var visited = entry.count > 1
            ? '<p style="font-size:.8rem;color:var(--color-text-muted);margin:.25rem 0 0;">Viewed ' + entry.count + ' times</p>'
            : '';

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

    function renderRecent(){
        var recent = KGMarketplace.getRecentVisited(5);
        var container = document.getElementById('recent-container');
        var desc = document.getElementById('recent-desc');
        if (!recent || recent.length === 0) {
            desc.textContent = 'Click any product or service below to start building your recently viewed list.';
            container.innerHTML = '';
            return;
        }
        desc.textContent = 'The last ' + recent.length + ' product' + (recent.length !== 1 ? 's' : '') + ' you viewed (this browser).';
        var html = '<div class="products-grid">';
        recent.forEach(function(r){ html += renderRecentCard(r); });
        html += '</div>';
        container.innerHTML = html;
    }
    renderRecent();

    // ----- Full marketplace catalog (existing section) --------------------
    var heading   = document.getElementById('mp-products-heading');
    var catalogDesc      = document.getElementById('mp-products-desc');
    var catalogContainer = document.getElementById('mp-products-container');

    function renderCatalogCard(mp){
        var img = mp.image_url
            ? '<img src="' + escapeHtml(mp.image_url) + '" alt="' + escapeHtml(mp.name || '') + '" width="400" height="300" loading="lazy">'
            : '<div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>';

        var avg = parseFloat(mp.avg_rating || 0);
        var reviewCount = parseInt(mp.review_count || 0, 10);
        var rating = '';
        if (avg > 0) {
            rating = '<p style="font-size:.85rem;color:var(--color-primary);margin:.25rem 0 0;font-weight:600;">'
                + renderStars(avg) + ' ' + avg.toFixed(1) + '/5 '
                + '<span style="font-weight:400;color:var(--color-text-muted);">(' + reviewCount + ' review' + (reviewCount !== 1 ? 's' : '') + ')</span>'
                + '</p>';
        }

        var price = (mp.price != null && mp.price !== '') ? '₹' + Number(mp.price).toLocaleString('en-IN') : '';
        var category = mp.category ? ' &bull; ' + escapeHtml(mp.category) : '';

        var descText = (mp.description || '').toString();
        if (descText.length > 100) descText = descText.substring(0, 97) + '...';
        var descHtml = descText ? '<p style="font-size:.85rem;color:var(--color-text-muted);margin-top:.25rem;">' + escapeHtml(descText) + '</p>' : '';

        var detailHref = mp.id ? 'view_product.php?id=' + encodeURIComponent(mp.id) : null;
        var inner = '<div class="product-card-image">' + img + '</div>'
            + '<div class="product-card-body">'
            + '<h3>' + escapeHtml(mp.name || 'Unnamed') + '</h3>'
            + '<p class="product-card-meta">' + price + category + '</p>'
            + rating + descHtml
            + '</div>';

        if (detailHref) return '<div class="product-card"><a href="' + detailHref + '">' + inner + '</a></div>';
        return '<div class="product-card">' + inner + '</div>';
    }

    function showCatalogError(reason){
        catalogDesc.textContent = 'Marketplace is unavailable right now.';
        catalogContainer.innerHTML = '<p class="message error" style="margin-top:.75rem;">'
            + 'Could not load marketplace products. ' + (reason || '')
            + ' See the browser console for details.</p>';
    }

    KGMarketplace.loadProducts().then(function(data){
        if (!data || !data.products) {
            showCatalogError('The marketplace returned an unexpected response.');
            return;
        }
        var products = data.products || [];
        var companyName = (data.company && data.company.name) ? data.company.name : '';
        if (companyName) heading.textContent = 'All Products \u2014 ';
        catalogDesc.textContent = 'Full catalog. (' + products.length + ' product' + (products.length !== 1 ? 's' : '') + ')';

        if (products.length === 0) {
            catalogContainer.innerHTML = '<p class="services-section-empty">No marketplace products available right now.</p>';
            return;
        }
        var html = '<div class="products-grid">';
        products.forEach(function(p){ html += renderCatalogCard(p); });
        html += '</div>';
        catalogContainer.innerHTML = html;
    }).catch(function(){
        showCatalogError('The browser blocked the request (likely an InfinityFree anti-bot challenge).');
    });
})();
</script>

<style>
.ranking-tab { padding: .35rem .9rem; font-size: .85rem; opacity: .65; }
.ranking-tab.active { opacity: 1; background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
</style>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
