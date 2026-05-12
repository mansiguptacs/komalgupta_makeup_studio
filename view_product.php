<?php
/**
 * Marketplace-backed product detail (this studio's catalog).
 * Fetches /api/product_detail.php?id=N client-side via assets/js/marketplace.js.
 * Company "Sold by" block is omitted — this page is served on the owner's site.
 */
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: services.php');
    exit;
}

require_once __DIR__ . '/includes/site_user_auth.php';
$kg_site_logged_in = kg_site_user_is_logged_in();

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

        <div id="mp-review-write-card" style="display:none;margin-top:2rem;padding:1.25rem;border:1px solid var(--color-border);border-radius:8px;background:var(--color-bg-alt, #fafafa);">
            <h3 style="font-family:var(--font-heading);margin-top:0;">Write a review</h3>
            <p id="mp-review-write-hint" style="color:var(--color-text-muted);font-size:.95rem;margin-bottom:1rem;"></p>
            <form id="mp-review-form" style="display:none;">
                <div class="form-row" style="margin-bottom:1rem;">
                    <label for="mp-review-rating" style="display:block;margin-bottom:.35rem;font-weight:600;">Rating <span class="required">*</span> (1–5)</label>
                    <select id="mp-review-rating" name="rating" required style="max-width:12rem;padding:.4rem;">
                        <option value="5">5 — Excellent</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </div>
                <div class="form-row" style="margin-bottom:1rem;">
                    <label for="mp-review-text" style="display:block;margin-bottom:.35rem;font-weight:600;">Your review</label>
                    <textarea id="mp-review-text" name="review_text" rows="4" style="width:100%;max-width:36rem;padding:.5rem;" placeholder="Share your experience…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="mp-review-submit">Submit review</button>
                <p id="mp-review-msg" style="margin-top:.75rem;font-size:.9rem;" aria-live="polite"></p>
            </form>
        </div>
    </div>
</section>

<script>
(function(){
    try {
    console.log('[MP Detail] script start');
    var productId = <?php echo json_encode($productId); ?>;
    var siteUserLoggedIn = <?php echo $kg_site_logged_in ? 'true' : 'false'; ?>;
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

    function setupMpReviewForm() {
        var writeCard = document.getElementById('mp-review-write-card');
        var form = document.getElementById('mp-review-form');
        var hint = document.getElementById('mp-review-write-hint');
        var msg = document.getElementById('mp-review-msg');
        if (!writeCard || !form || !hint || !KGMarketplace.onAuthReady) {
            return;
        }
        var submitBound = false;
        KGMarketplace.onAuthReady(function (mpUser) {
            writeCard.style.display = 'block';
            if (!siteUserLoggedIn) {
                hint.textContent = 'Sign in with your customer account (Our Marketplace) to post a review. It will appear on this page and on the main marketplace.';
                form.style.display = 'none';
                return;
            }
            if (!mpUser) {
                hint.innerHTML = 'Open <a href="account.php">Account</a> and use &ldquo;Sign in with Our Marketplace&rdquo; so we can save your review to the marketplace.';
                form.style.display = 'none';
                return;
            }
            hint.textContent = 'Submit a rating and optional text. This is stored on OurMarketplace (same reviews as on the hub site).';
            form.style.display = 'block';
            if (submitBound) {
                return;
            }
            submitBound = true;
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                msg.textContent = '';
                msg.style.color = '';
                var btn = document.getElementById('mp-review-submit');
                var rating = parseInt(document.getElementById('mp-review-rating').value, 10);
                var text = document.getElementById('mp-review-text').value;
                if (btn) {
                    btn.disabled = true;
                }
                KGMarketplace.submitReview(productId, rating, text).then(function () {
                    msg.textContent = 'Thank you! Your review was saved. Refreshing…';
                    msg.style.color = 'var(--color-primary)';
                    setTimeout(function () { location.reload(); }, 900);
                }).catch(function (err) {
                    msg.textContent = (err && err.message) ? err.message : 'Could not submit review.';
                    msg.style.color = '#b00020';
                }).finally(function () {
                    if (btn) {
                        btn.disabled = false;
                    }
                });
            });
        });
    }

    setupMpReviewForm();

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
        priceEl.textContent = (product.price != null && product.price !== '') ? ('$' + Number(product.price).toLocaleString('en-IN')) : '';

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

        // "Sold by" company block omitted — visitors are already on this studio's site.

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
