<?php
require_once __DIR__ . '/includes/site_user_auth.php';
require_once __DIR__ . '/includes/site_user_repository.php';

kg_require_site_user();
$user = kg_site_user();
$products = kg_get_products_catalog();
$services = kg_get_services_catalog();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($ok, $msg) = kg_save_review(
        $user['id'],
        $_POST['review_type'] ?? 'product',
        $_POST['item_id'] ?? 0,
        $_POST['rating'] ?? 0,
        $_POST['review_text'] ?? ''
    );
    if ($ok) {
        $success = $msg;
    } else {
        $error = $msg;
    }
}

$myReviews = kg_get_user_reviews($user['id'], 20);

$page_title = 'My Reviews';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <h1>Add review &amp; rating</h1>
        <p class="lead">You can review any product or service. Submitting again updates your previous review.</p>
        <?php if ($success !== ''): ?><div class="message success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form class="appointment-form" method="post" action="user_reviews.php">
            <div class="form-row">
                <label for="review_type">Review type <span class="required">*</span></label>
                <select id="review_type" name="review_type" required>
                    <option value="product" <?php echo (($_POST['review_type'] ?? 'product') === 'product') ? 'selected' : ''; ?>>Product</option>
                    <option value="service" <?php echo (($_POST['review_type'] ?? '') === 'service') ? 'selected' : ''; ?>>Service</option>
                </select>
            </div>
            <div class="form-row">
                <label for="item_id">Item <span class="required">*</span></label>
                <select id="item_id" name="item_id" required>
                    <optgroup label="Products">
                        <?php foreach ($products as $p): ?>
                            <?php $id = (int)($p['product_id'] ?? 0); if ($id <= 0) { continue; } ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($p['name'] . ' (Product)'); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Services">
                        <?php foreach ($services as $s): ?>
                            <?php $id = (int)($s['service_id'] ?? 0); if ($id <= 0) { continue; } ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($s['name'] . ' (Service)'); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="form-row">
                <label for="rating">Rating (1 to 5) <span class="required">*</span></label>
                <input type="number" id="rating" name="rating" required min="1" max="5" step="0.1" value="<?php echo htmlspecialchars($_POST['rating'] ?? '5'); ?>">
            </div>
            <div class="form-row">
                <label for="review_text">Review</label>
                <textarea id="review_text" name="review_text" rows="4"><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Review</button>
                <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>

        <h2>Your recent reviews</h2>
        <?php if (empty($myReviews)): ?>
            <p class="lead">You have not posted any reviews yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($myReviews as $r): ?>
                    <li>
                        <strong><?php echo htmlspecialchars(ucfirst($r['review_type'])); ?> #<?php echo (int)$r['item_id']; ?></strong>
                        - Rating: <?php echo htmlspecialchars($r['rating']); ?>/5
                        <?php if (!empty($r['review_text'])): ?> - <?php echo htmlspecialchars($r['review_text']); ?><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
