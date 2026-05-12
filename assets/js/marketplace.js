/**
 * OurMarketplace API client for Komal Gupta Makeup Studio.
 * company_id = 1
 */
var KGMarketplace = (function () {
    var API_BASE = 'https://mansiguptacs.com/ourmarketplace/api';
    var COMPANY_ID = 1;
    var TOKEN_KEY = 'marketplace_token';
    var RECENT_KEY = 'kg_visited_products';
    var RECENT_MAX = 50; // cap the localStorage list to avoid unbounded growth

    function fetchJSON(url, opts) {
        return fetch(url, opts || {}).then(function (r) {
            return r.text().then(function (txt) {
                var data;
                try { data = JSON.parse(txt); } catch (e) { data = null; }
                if (data === null && txt && txt.length > 0) {
                    // Most likely InfinityFree's anti-bot HTML challenge page
                    var preview = txt.substring(0, 200).replace(/\s+/g, ' ');
                    console.warn('[KGMarketplace] Non-JSON response from ' + url + ' (HTTP ' + r.status + '). First 200 chars:', preview);
                    if (/<script[^>]*aes\.js/i.test(txt) || /__test/.test(txt)) {
                        console.warn('[KGMarketplace] The marketplace host is serving an InfinityFree anti-bot challenge. Cross-origin browser fetches cannot solve this. The marketplace site needs to disable the challenge (or whitelist the API path) and add CORS headers.');
                    }
                }
                return { ok: r.ok, status: r.status, data: data, raw: txt };
            });
        }).catch(function (err) {
            console.error('[KGMarketplace] Network/CORS error for ' + url + ':', err);
            throw err;
        });
    }

    function loadProducts(category) {
        var url = API_BASE + '/products.php?company_id=' + COMPANY_ID;
        if (category) url += '&category=' + encodeURIComponent(category);
        return fetchJSON(url).then(function (r) { return r.data; });
    }

    function loadProduct(productId) {
        return fetchJSON(API_BASE + '/products.php?id=' + productId)
            .then(function (r) { return r.data; });
    }

    function loadProductDetail(productId) {
        return fetchJSON(API_BASE + '/product_detail.php?id=' + productId)
            .then(function (r) { return r.data; });
    }

    function loadReviews(productId) {
        return fetchJSON(API_BASE + '/reviews.php?product_id=' + productId)
            .then(function (r) { return r.data; });
    }

    /**
     * POST a review to OurMarketplace (same DB as marketplace product pages).
     * Requires a Bearer token (SSO or api/login) in localStorage.
     * @param {number} productId OurMarketplace products.id
     * @param {number} rating Integer 1–5
     * @param {string} reviewText Optional
     */
    function submitReview(productId, rating, reviewText) {
        var token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            return Promise.reject(new Error('Sign in with Our Marketplace (Account) to post a review.'));
        }
        var rid = parseInt(productId, 10);
        var stars = Math.round(parseFloat(rating));
        if (rid <= 0 || stars < 1 || stars > 5) {
            return Promise.reject(new Error('Invalid product or rating (use 1–5).'));
        }
        return fetchJSON(API_BASE + '/reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                product_id: rid,
                rating: stars,
                review_text: (reviewText == null ? '' : String(reviewText)).trim()
            })
        }).then(function (r) {
            if (!r.ok) {
                var msg = (r.data && r.data.error) ? r.data.error : ('HTTP ' + r.status);
                throw new Error(msg);
            }
            return r.data;
        });
    }

    function verify() {
        var token = localStorage.getItem(TOKEN_KEY);
        if (!token) return Promise.resolve(null);
        return fetchJSON(API_BASE + '/verify.php', {
            headers: { 'Authorization': 'Bearer ' + token }
        }).then(function (r) {
            if (r.ok && r.data && r.data.logged_in) {
                return r.data.user;
            }
            localStorage.removeItem(TOKEN_KEY);
            return null;
        }).catch(function () {
            return null;
        });
    }

    function logout() {
        localStorage.removeItem(TOKEN_KEY);
        // Clear any in-flight/cached auth state so onAuthReady reflects the new state.
        _authPromise = null;
        _authUser = undefined;
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function isLoggedIn() {
        return !!localStorage.getItem(TOKEN_KEY);
    }

    // -----------------------------------------------------------------------
    // Cached auth readiness. Every page can call onAuthReady(cb) without each
    // one firing its own /api/verify.php request. The first caller starts the
    // verify; subsequent callers reuse the same promise and resolved value.
    // -----------------------------------------------------------------------
    var _authPromise = null;
    var _authUser = undefined; // undefined = unresolved, null = anonymous, object = user

    function onAuthReady(cb) {
        if (!_authPromise) {
            _authPromise = verify().then(function (user) {
                _authUser = user || null;
                return _authUser;
            }).catch(function () {
                _authUser = null;
                return null;
            });
        }
        if (typeof cb === 'function') _authPromise.then(cb);
        return _authPromise;
    }

    function getEffectiveUser() {
        return _authUser === undefined ? null : _authUser;
    }

    // -----------------------------------------------------------------------
    // Server-side visit tracking (writes a row in marketplace.user_visits)
    // -----------------------------------------------------------------------
    function trackVisit(productId) {
        if (productId == null) return Promise.resolve(null);
        var headers = { 'Content-Type': 'application/json' };
        var token = localStorage.getItem(TOKEN_KEY);
        if (token) headers['Authorization'] = 'Bearer ' + token;
        return fetchJSON(API_BASE + '/track_visit.php', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ product_id: productId })
        }).then(function (r) { return r.data; })
          .catch(function (err) {
              console.warn('[KGMarketplace] trackVisit failed (non-fatal):', err);
              return null;
          });
    }

    // -----------------------------------------------------------------------
    // Server-side top-N (best_rated | most_visited | most_reviewed)
    // opts = { company_id?: number, method?: string, limit?: number }
    // -----------------------------------------------------------------------
    function getTopProducts(opts) {
        opts = opts || {};
        var params = [];
        if (opts.company_id) params.push('company_id=' + encodeURIComponent(opts.company_id));
        if (opts.method)     params.push('method=' + encodeURIComponent(opts.method));
        if (opts.limit)      params.push('limit=' + encodeURIComponent(opts.limit));
        var url = API_BASE + '/top_products.php' + (params.length ? '?' + params.join('&') : '');
        return fetchJSON(url).then(function (r) { return r.data; });
    }

    // -----------------------------------------------------------------------
    // Per-browser "recently visited" store (localStorage). Mixes local site
    // products and marketplace products. Each entry shape:
    //   { key, type: 'local'|'marketplace', href, name, image, price,
    //     category, duration?, count, lastVisited }
    // -----------------------------------------------------------------------
    function readRecentStore() {
        try {
            var raw = localStorage.getItem(RECENT_KEY);
            if (!raw) return [];
            var arr = JSON.parse(raw);
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }

    function writeRecentStore(arr) {
        try { localStorage.setItem(RECENT_KEY, JSON.stringify(arr.slice(0, RECENT_MAX))); }
        catch (e) { /* localStorage may be full or unavailable — silently skip */ }
    }

    function makeEntryKey(entry) {
        if (entry.key) return entry.key;
        if (entry.type === 'marketplace' && entry.id != null) return 'mp:' + entry.id;
        if (entry.type === 'local' && entry.slug)             return 'local:' + entry.slug;
        return null;
    }

    function recordVisit(entry) {
        if (!entry || !entry.type) return null;
        var key = makeEntryKey(entry);
        if (!key) return null;

        var store = readRecentStore();
        var now = Date.now();
        var existingIdx = -1;
        for (var i = 0; i < store.length; i++) {
            if (store[i].key === key) { existingIdx = i; break; }
        }

        var record;
        if (existingIdx >= 0) {
            record = store[existingIdx];
            record.count = (parseInt(record.count, 10) || 0) + 1;
            record.lastVisited = now;
            // Update display fields in case the source changed them
            if (entry.name)     record.name = entry.name;
            if (entry.image)    record.image = entry.image;
            if (entry.price != null) record.price = entry.price;
            if (entry.category) record.category = entry.category;
            if (entry.duration) record.duration = entry.duration;
            if (entry.href)     record.href = entry.href;
            store.splice(existingIdx, 1);
        } else {
            record = {
                key: key,
                type: entry.type,
                href: entry.href || '',
                name: entry.name || '',
                image: entry.image || '',
                price: entry.price != null ? entry.price : null,
                category: entry.category || '',
                duration: entry.duration || '',
                count: 1,
                lastVisited: now
            };
        }
        store.unshift(record);
        writeRecentStore(store);
        return record;
    }

    function getRecentVisited(n) {
        var store = readRecentStore();
        // Already kept in "most-recent first" order by recordVisit's unshift,
        // but re-sort defensively in case of corruption.
        store.sort(function (a, b) { return (b.lastVisited || 0) - (a.lastVisited || 0); });
        return n ? store.slice(0, n) : store.slice();
    }

    function clearRecentVisited() {
        try { localStorage.removeItem(RECENT_KEY); } catch (e) {}
    }

    return {
        loadProducts: loadProducts,
        loadProduct: loadProduct,
        loadProductDetail: loadProductDetail,
        loadReviews: loadReviews,
        submitReview: submitReview,
        verify: verify,
        logout: logout,
        getToken: getToken,
        isLoggedIn: isLoggedIn,
        onAuthReady: onAuthReady,
        getEffectiveUser: getEffectiveUser,
        trackVisit: trackVisit,
        getTopProducts: getTopProducts,
        recordVisit: recordVisit,
        getRecentVisited: getRecentVisited,
        clearRecentVisited: clearRecentVisited,
        API_BASE: API_BASE,
        COMPANY_ID: COMPANY_ID
    };
})();
