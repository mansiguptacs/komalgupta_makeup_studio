<?php
/**
 * Dedicated "Top 5 popular products" view (our studio only).
 *
 * Data comes from the marketplace API (/api/top_products.php?company_id=1),
 * so the same three ranking methods used elsewhere are available here too.
 * Marketplace-wide rankings are intentionally not exposed on this site.
 */
$page_title = '5 Most Popular Products';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-section">
    <div class="container">
        <h1>Top 5 popular products</h1>
        <p class="lead">Our studio's top 5 products ranked from the marketplace database.</p>
        <p style="margin-bottom: 1.5rem;">
            <a href="services.php">&larr; Back to Products &amp; Services</a>
        </p>

        <!-- Method tabs -->
        <div role="tablist" aria-label="Ranking method" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
            <button type="button" class="btn btn-secondary method-tab active" data-method="best_rated">Best Rated</button>
            <button type="button" class="btn btn-secondary method-tab" data-method="most_visited">Most Visited</button>
            <button type="button" class="btn btn-secondary method-tab" data-method="most_reviewed">Most Reviewed</button>
        </div>

        <p id="popular-desc" style="color:var(--color-text-muted);">Loading...</p>
        <div id="popular-container"></div>
    </div>
</section>

<script>
(function(){
    if (typeof KGMarketplace === 'undefined') {
        document.getElementById('popular-desc').textContent = 'KGMarketplace JS library failed to load.';
        return;
    }

    function escapeHtml(s){
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function stars(n){
        var r = Math.round(Number(n) || 0); var s = '';
        for (var i = 1; i <= 5; i++) s += (i <= r) ? '★' : '☆';
        return s;
    }
    function methodLabel(m){
        if (m === 'most_visited')  return 'Most Visited';
        if (m === 'most_reviewed') return 'Most Reviewed';
        return 'Best Rated';
    }

    function renderCard(mp, rank){
        var img = mp.image_url
            ? '<img src="' + escapeHtml(mp.image_url) + '" alt="' + escapeHtml(mp.name || '') + '" width="400" height="300" loading="lazy">'
            : '<div style="width:100%;height:200px;background:var(--color-bg);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.9rem;">No image</div>';
        var avg = parseFloat(mp.avg_rating || 0);
        var reviewCount = parseInt(mp.review_count || 0, 10);
        var visitCount = parseInt(mp.visit_count || 0, 10);
        var price = (mp.price != null && mp.price !== '') ? '$' + Number(mp.price).toLocaleString('en-IN') : '';
        var rankBadge = '<span style="display:inline-block;background:var(--color-primary);color:#fff;width:1.6rem;height:1.6rem;border-radius:50%;text-align:center;line-height:1.6rem;font-weight:700;font-size:.85rem;margin-right:.4rem;">' + rank + '</span>';
        var rating = '<p style="font-size:.85rem;color:var(--color-primary);margin:.25rem 0 0;font-weight:600;">'
            + stars(avg) + ' ' + (avg > 0 ? avg.toFixed(1) + '/5' : 'Not rated')
            + ' <span style="font-weight:400;color:var(--color-text-muted);">'
            + '(' + reviewCount + ' review' + (reviewCount !== 1 ? 's' : '') + ', '
            + visitCount + ' visit' + (visitCount !== 1 ? 's' : '') + ')</span></p>';
        var href = mp.id != null ? 'view_product.php?id=' + encodeURIComponent(mp.id) : '#';
        return '<div class="product-card"><a href="' + href + '">'
            + '<div class="product-card-image">' + img + '</div>'
            + '<div class="product-card-body">'
            + '<h3>' + rankBadge + escapeHtml(mp.name || 'Unnamed') + '</h3>'
            + '<p class="product-card-meta">' + price + '</p>'
            + rating
            + '</div>'
            + '</a></div>';
    }

    var descEl = document.getElementById('popular-desc');
    var container = document.getElementById('popular-container');

    var currentMethod = 'best_rated';

    function reload(){
        descEl.textContent = 'Loading our studio top 5 \u2014 ' + methodLabel(currentMethod) + '...';
        container.innerHTML = '';
        KGMarketplace.getTopProducts({
            company_id: KGMarketplace.COMPANY_ID,
            method: currentMethod,
            limit: 5
        }).then(function(data){
            var list = (data && data.products) || [];
            if (!list.length) {
                descEl.textContent = 'No ranked products yet for our studio.';
                return;
            }
            descEl.textContent = 'Our studio\u2019s top ' + list.length + ' \u2014 ' + methodLabel(currentMethod) + '.';
            var html = '<div class="products-grid">';
            for (var i = 0; i < list.length; i++) html += renderCard(list[i], i + 1);
            html += '</div>';
            container.innerHTML = html;
        }).catch(function(){
            descEl.innerHTML = '<span class="message error">Could not load rankings.</span>';
        });
    }

    function setActive(buttons, btn){
        for (var i = 0; i < buttons.length; i++) buttons[i].classList.remove('active');
        btn.classList.add('active');
    }

    var methodTabs = document.querySelectorAll('.method-tab');
    methodTabs.forEach(function(btn){
        btn.addEventListener('click', function(){
            currentMethod = btn.getAttribute('data-method');
            setActive(methodTabs, btn);
            reload();
        });
    });

    reload();
})();
</script>

<style>
.method-tab { padding: .35rem .9rem; font-size: .85rem; opacity: .65; }
.method-tab.active { opacity: 1; background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
</style>

<?php require_once __DIR__ . '/includes/footer.html'; ?>
