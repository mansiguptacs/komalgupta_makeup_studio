<?php
/**
 * Secure section: list of current website users.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user_repository.php';
requireAdmin('../login.php');

kg_seed_users_from_file_if_empty();
$users = kg_get_site_users();

$page_title = 'Current Users';
$current_page = 'users';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-section">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h1>Current Website Users</h1>
            <div>
                <a href="network_users.php" class="btn btn-secondary" style="margin-right: 8px;">Network Users</a>
                <a href="analytics.php" class="btn btn-secondary" style="margin-right: 8px;">Analytics</a>
                <a href="appointments.php" class="btn btn-secondary" style="margin-right: 8px;">Appointments</a>
                <a href="../api/logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </div>
        <p class="lead">This document lists registered users of the site (loaded from MySQL, with file fallback).</p>

        <div class="user-list-doc" style="margin-top: 1.5rem;">
            <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border);">
                <thead>
                    <tr style="background: var(--color-bg);">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Name</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Email</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.75rem 1rem;"><?php echo (int)($i + 1); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['joined'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="4" style="padding: 1.5rem; color: var(--color-text-muted);">No users on file.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.html'; ?>
