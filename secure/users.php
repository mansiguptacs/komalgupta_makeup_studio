<?php
/**
 * Secure section: list of current website users.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user_repository.php';
requireAdmin('../login.php');

kg_seed_users_from_file_if_empty();
$searchFirstName = trim((string)($_GET['first_name'] ?? ''));
$searchLastName = trim((string)($_GET['last_name'] ?? ''));
$searchEmail = trim((string)($_GET['email'] ?? ''));
$searchPhone = trim((string)($_GET['phone'] ?? ''));

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
        <form method="get" action="users.php" class="appointment-form" style="max-width: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem; align-items: end; margin-top: 1rem;">
            <div class="form-row" style="margin: 0;">
                <label for="first_name">Search by first name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($searchFirstName); ?>" placeholder="e.g. Komal">
            </div>
            <div class="form-row" style="margin: 0;">
                <label for="last_name">Search by last name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($searchLastName); ?>" placeholder="e.g. Gupta">
            </div>
            <div class="form-row" style="margin: 0;">
                <label for="email">Search by email</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($searchEmail); ?>" placeholder="e.g. gmail.com">
            </div>
            <div class="form-row" style="margin: 0;">
                <label for="phone">Search by phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($searchPhone); ?>" placeholder="e.g. 98xxxxxx">
            </div>
            <div class="form-actions" style="margin: 0; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="users.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <div class="user-list-doc" style="margin-top: 1.5rem;">
            <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border);">
                <thead>
                    <tr style="background: var(--color-bg);">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Name</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Email</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Phone</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.75rem 1rem;"><?php echo (int)($i + 1); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['first_name'] ?? '') . ' ' . htmlspecialchars($u['last_name'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['cell_phone'] ?? $u['home_phone'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($u['joined'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="padding: 1.5rem; color: var(--color-text-muted);">No users found for the current search.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.html'; ?>
