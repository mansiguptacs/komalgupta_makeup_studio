<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin('login.php');
require_once __DIR__ . '/includes/user_repository.php';

$page_title = 'DB Setup';
require_once __DIR__ . '/includes/header.php';

$db = kg_db();
$messages = [];
if (!$db) {
    $messages[] = 'Database not connected.';
    $err = kg_db_last_error();
    if ($err !== '') {
        $messages[] = $err;
    }
    $messages[] = 'Check: (1) File is named exactly config/db_credentials.php (not a copy). (2) password is set. (3) Host/user/database match InfinityFree panel.';
} else {
    if (kg_ensure_tables($db)) {
        $messages[] = 'Schema OK. Required tables already exist.';
        $n = kg_seed_subscribers_from_file();
        $messages[] = 'Subscribers: imported ' . (int)$n . ' row(s) from data/subscribers.json (duplicates skipped).';
    } else {
        $messages[] = 'Schema validation failed.';
        $err = kg_db_last_error();
        if ($err !== '') {
            $messages[] = $err;
        }
        $messages[] = 'Import sql/schema.sql manually in phpMyAdmin, then reload this page.';
    }
}
?>
<section class="page-section">
  <div class="container">
    <h1>Database Setup</h1>
    <?php foreach ($messages as $m): ?>
      <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border);"><?php echo htmlspecialchars($m); ?></p>
    <?php endforeach; ?>
    <p><a href="secure/users.php">Go to Users</a> &middot; <a href="secure/analytics.php">Analytics (subscribers)</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
