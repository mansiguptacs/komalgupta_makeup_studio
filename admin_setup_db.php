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
        $messages[] = 'Tables OK: site_users, subscribers.';
        kg_seed_users_from_file_if_empty();
        $messages[] = 'Users: imported from data/site_users.json if table was empty.';
        $n = kg_seed_subscribers_from_file();
        $messages[] = 'Subscribers: imported ' . (int)$n . ' row(s) from data/subscribers.json (duplicates skipped).';
    } else {
        $messages[] = 'Failed to create tables.';
        $err = kg_db_last_error();
        if ($err !== '') {
            $messages[] = $err;
        }
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
