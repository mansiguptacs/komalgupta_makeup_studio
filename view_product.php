<?php
/**
 * Marketplace product detail page.
 * Fetches /api/product_detail.php?id=N client-side via assets/js/marketplace.js
 * (server-side cURL is blocked by the marketplace host's anti-bot challenge).
 */
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: services.php');
    exit;
}

$page_title = 'Product';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section product-detail-section">
    <div class="container">
        <p style="margin-bottom: 1rem;">
            <a href="services.php" style="color: var(--color-primary); text-decoration: none;">&larr; Back to Products &amp; Services</a>
        </p>

        <div id="mp-detail-status">
            <p style="color:var(--color-text-muted);">Loading product details...</p>
        </div>

        <div id="mp-detail-card" class="product-detail-card" style="display:none;">
            <div class="product-detail-image">
                <img id="mp-detail-image" alt="" width="800" height="500" loading="eager">
            </div>
            <div class="product-detail-body">
                <p class="product-detail-category" id="mp-detail-category"></p>
                <h1 id="mp-detail-name"></h1>
                <p class="product-detail-meta">
                    <span id="mp-detail-price"></span>
                    <span id="mp-detail-rating"></span>
                    <span id="mp-detail-visits" style="color:var(--color-text-muted);font-weight:400;font-size:.9rem;"></span>
                </p>
                <div class="product-detail-description" id="mp-detail-description"></div>

                <div id="mp-detail-company" style="margin-top:1.25rem;padding:.85rem 1rem;background:var(--color-bg);border-radius:8px;display:none;">
                    <p style="margin:0;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-muted);">Sold by</p>
                    <p style="margin:.2rem 0 0;font-weight:600;" id="mp-detail-company-name"></p>
                    <p style="margin:0;font-size:.85rem;color:var(--color-text-muted);" id="mp-detail-company-category"></p>
                    <a id="mp-detail-company-website" target="_blank" rel="noopener" style="font-size:.85rem;color:var(--color-primary);text-decoration:none;display:none;">Visit website &rarr;</a>
                </div>

                <p style="margin-top:1.5rem;display:none;" id="mp-detail-book-wrap">
                    <a id="mp-detail-book-btn" href="#" class="btn btn-primary">Book this service</a>
                </p>
            </div>
        </div>

        <div id="mp-detail-reviews-section" style="margin-top:2rem; display:none;">
            <h2 style="font-family:var(--font-heading);">Reviews <span id="mp-detail-reviews-count" style="font-weight:400;color:var(--color-text-muted);font-size:1rem;"></span></h2>
            <div id="mp-detail-rating-breakdown" style="margin-bottom:1.25rem;"></div>
            <div id="mp-detail-reviews"></div>
        </div>
    </div>
</section>

<script>
(function(){
    try {
    console.log('[MP Detail] script start');
    var productId = <?php echo json_encode($productId); ?>;
    console.log('[MP Detail] productId =', productId, 'KGMarketplace =', typeof KGMarketplace);

    if (typeof KGMarketplace === 'undefined') {
        var s = document.getElementById('mp-detail-status');
        if (s) s.innerHTML = '<p class="message error">KGMarketplace JS library failed to load. Check that assets/js/marketplace.js exists and the path is correct.</p>';
        return;
    }

    var statusEl = document.getElementById('mp-detail-status');
    var card = document.getElementById('mp-detail-card');
    var imgEl = document.getElementById('mp-detail-image');
    var nameEl = document.getElementById('mp-detail-name');
    var categoryEl = document.getElementById('mp-detail-category');
    var priceEl = document.getElementById('mp-detail-price');
    var ratingEl = document.getElementById('mp-detail-rating');
    var visitsEl = document.getElementById('mp-detail-visits');
    var descEl = document.getElementById('mp-detail-description');
    var companyBox = document.getElementById('mp-detail-company');
    var companyNameEl = document.getElementById('mp-detail-company-name');
    var companyCategoryEl = document.getElementById('mp-detail-company-category');
    var companyWebsiteEl = document.getElementById('mp-detail-company-website');
    var reviewsSection = document.getElementById('mp-detail-reviews-section');
    var reviewsCountEl = document.getElementById('mp-detail-reviews-count');
    var breakdownEl = document.getElementById('mp-detail-rating-breakdown');
    var reviewsEl = document.getElementById('mp-detail-reviews');

    function escapeHtml(s){
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function stars(n){
        var rounded = Math.round(Number(n) || 0);
        var s = '';
        for (var i = 1; i <= 5; i++) s += (i <= rounded) ? '★' : '☆';
        return s;
    }

    function pickProduct(data){
        if (!data) return null;
        if (data.product && typeof data.product === 'object') return data.product;
        // Some APIs return the product fields at the top level
        if (data.id != null && data.name != null) return data;
        return null;
    }

    function renderRatingBreakdown(breakdown, totalCount){
        if (!breakdown || typeof breakdown !== 'object') { breakdownEl.innerHTML = ''; return; }
        var total = totalCount > 0 ? totalCount : 0;
        if (total === 0) {
            for (var k in breakdown) total += parseInt(breakdown[k] || 0, 10);
        }
        if (total === 0) { breakdownEl.innerHTML = ''; return; }
        var html = '<div style="display:flex;flex-direction:column;gap:.3rem;max-width:340px;">';
        ['5','4','3','2','1'].forEach(function(k){
            var count = parseInt(breakdown[k] || 0, 10);
            var pct = total > 0 ? Math.round((count / total) * 100) : 0;
            html += '<div style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;">'
                + '<span style="width:1.5rem;color:var(--color-text-muted);">' + k + '★</span>'
                + '<div style="flex:1;height:.55rem;background:var(--color-bg);border-radius:4px;overflow:hidden;"><div style="width:' + pct + '%;height:100%;background:var(--color-primary);"></div></div>'
                + '<span style="width:2.5rem;text-align:right;color:var(--color-text-muted);">' + count + '</span>'
                + '</div>';
        });
        html += '</div>';
        breakdownEl.innerHTML = html;
    }

    function renderReviews(reviews){
        reviewsSection.style.display = 'block';
        if (!reviews || reviews.length === 0) {
            reviewsCountEl.textContent = '(0)';
            reviewsEl.innerHTML = '<p style="color:var(--color-text-muted);">No reviews yet for this product.</p>';
            return;
        }
        reviewsCountEl.textContent = '(' + reviews.length + ')';
        var html = '';
        reviews.forEach(function(rev){
            html += '<div style="border-bottom:1px solid var(--color-border);padding:.75rem 0;">'
                + '<div style="display:flex;justify-content:space-between;align-items:center;">'
                + '<strong>' + escapeHtml(rev.full_name || rev.username || 'Anonymous') + '</strong>'
                + '<span style="color:var(--color-primary);font-size:.95rem;">' + stars(rev.rating) + '</span>'
                + '</div>';
            if (rev.review_text) html += '<p style="margin:.35rem 0 0;color:var(--color-text-muted);">' + escapeHtml(rev.review_text) + '</p>';
            if (rev.created_at) html += '<p style="margin:.2rem 0 0;font-size:.8rem;color:var(--color-text-muted);">' + escapeHtml(rev.created_at) + '</p>';
            html += '</div>';
        });
        reviewsEl.innerHTML = html;
    }

    function showError(msg){
        statusEl.innerHTML = '<p class="message error">' + escapeHtml(msg) + '</p>';
    }

    var bookWrap = document.getElementById('mp-detail-book-wrap');
    var bookBtn = document.getElementById('mp-detail-book-btn');

    // -------- Product detail load ----------
    KGMarketplace.loadProductDetail(productId).then(function(data){
        var product = pickProduct(data);
        if (!product) {
            showError('Could not load this product. The marketplace returned an unexpected response. Check the browser console for details.');
            return;
        }

        document.title = (product.name || 'Marketplace Product') + ' | Komal Gupta Makeup Studio';

        if (product.image_url) {
            imgEl.src = product.image_url;
            imgEl.alt = product.name || '';
        } else {
            imgEl.style.display = 'none';
        }
        nameEl.textContent = product.name || 'Unnamed product';
        categoryEl.textContent = product.category || '';
        priceEl.textContent = (product.price != null && product.price !== '') ? ('₹' + Number(product.price).toLocaleString('en-IN')) : '';

        var avg = parseFloat(product.avg_rating || 0);
        var reviewCount = parseInt(product.review_count || 0, 10);
        if (avg > 0) {
            ratingEl.innerHTML = stars(avg) + ' ' + avg.toFixed(1) + '/5 <span style="color:var(--color-text-muted);font-weight:400;">(' + reviewCount + ' review' + (reviewCount !== 1 ? 's' : '') + ')</span>';
        }

        var visitCount = parseInt(product.visit_count || 0, 10);
        if (visitCount > 0) {
            visitsEl.innerHTML = ' &bull; ' + visitCount + ' view' + (visitCount !== 1 ? 's' : '');
        }

        descEl.innerHTML = product.description
            ? escapeHtml(product.description).replace(/\n/g, '<br>')
            : '<em style="color:var(--color-text-muted);">No description provided.</em>';

        // Company info block (requires #mp-detail-company markup in this page)
        if (companyBox && companyNameEl && product.company_name) {
            companyNameEl.textContent = product.company_name;
            if (companyCategoryEl) {
                if (product.company_category) {
                    companyCategoryEl.textContent = product.company_category;
                    companyCategoryEl.style.display = '';
                } else {
                    companyCategoryEl.style.display = 'none';
                }
            }
            if (companyWebsiteEl && product.company_website) {
                companyWebsiteEl.href = product.company_website;
                companyWebsiteEl.style.display = 'inline-block';
                companyWebsiteEl.style.marginTop = '.35rem';
            } else if (companyWebsiteEl) {
                companyWebsiteEl.style.display = 'none';
            }
            companyBox.style.display = '';
        }

        // "Book this service" button — only for the Services category
        if (product.category && String(product.category).toLowerCase() === 'services') {
            var params = new URLSearchParams();
            params.set('service', product.name || '');
            if (product.id != null) params.set('mp_product_id', product.id);
            bookBtn.href = 'appointments.php?' + params.toString();
            bookWrap.style.display = '';
        }

        // Track this visit:
        //   1) on the marketplace DB (so global "most visited" counts include
        //      users arriving via this site, not just direct marketplace traffic)
        //   2) in localStorage (so "recently viewed by you" can list both
        //      local and marketplace products in the same list)
        if (product.id != null) {
            KGMarketplace.trackVisit(product.id);
            KGMarketplace.recordVisit({
                type: 'marketplace',
                id: product.id,
                name: product.name || '',
                image: product.image_url || '',
                price: product.price,
                category: product.category || '',
                href: 'view_product.php?id=' + encodeURIComponent(product.id)
            });
        }

        statusEl.style.display = 'none';
        card.style.display = '';

        renderRatingBreakdown(data.rating_breakdown, reviewCount);

        // Reviews: prefer inline reviews from detail response, fall back to /reviews.php
        if (data.reviews && Array.isArray(data.reviews)) {
            renderReviews(data.reviews);
        } else {
            KGMarketplace.loadReviews(productId).then(function(rd){
                renderReviews((rd && rd.reviews) ? rd.reviews : []);
            }).catch(function(){
                reviewsSection.style.display = 'block';
                reviewsCountEl.textContent = '';
                reviewsEl.innerHTML = '<p class="message error">Could not load reviews.</p>';
            });
        }
    }).catch(function(err){
        console.error('[MP Detail] loadProductDetail rejected:', err);
        var detail = (err && err.message) ? err.message : String(err);
        showError('Could not load product details. If the marketplace API is reachable, check the browser console. '
            + (detail && detail !== 'undefined' ? '(' + detail + ')' : ''));
    });

    } catch (e) {
        console.error('[MP Detail] Uncaught error in IIFE:', e);
        var s = document.getElementById('mp-detail-status');
        if (s) s.innerHTML = '<p class="message error">JavaScript error: ' + (e && e.message ? e.message : String(e)) + '. Check the browser console for the full stack trace.</p>';
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
