<?php
/**
 * Admin analytics: subscribers + visit logs (visited users).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user_repository.php';
requireAdmin('../login.php');

$page_title = 'Analytics';
$current_page = 'analytics';
require_once __DIR__ . '/../includes/header.php';

$subs = kg_get_subscribers();

$visitsFile = __DIR__ . '/../data/visits.json';
$visits = [];
if (is_readable($visitsFile)) {
    $visits = json_decode(file_get_contents($visitsFile), true);
    if (!is_array($visits)) {
        $visits = [];
    }
}
// Count by page
$byPage = [];
foreach ($visits as $v) {
    $p = $v['page'] ?? '/';
    if (!isset($byPage[$p])) {
        $byPage[$p] = 0;
    }
    $byPage[$p]++;
}
arsort($byPage);
$totalVisits = count($visits);
$uniquePages = count($byPage);
// Last 50 visits for detail table
$recentVisits = array_reverse(array_slice($visits, -50));
?>

<section class="page-section">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h1>Analytics &amp; Subscribers</h1>
            <div>
                <a href="users.php" class="btn btn-secondary" style="margin-right: 8px;">Users List</a>
                <a href="network_users.php" class="btn btn-secondary" style="margin-right: 8px;">Network Users</a>
                <a href="appointments.php" class="btn btn-secondary" style="margin-right: 8px;">Appointments</a>
                <a href="../api/logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </div>
        <p class="lead">Subscribed emails and page visit analytics. Subscribers are loaded from MySQL (with fallback).</p>

        <div class="analytics-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
            <div class="location-card" style="text-align: center;">
                <h3 style="margin: 0; font-size: 1.75rem;"><?php echo count($subs); ?></h3>
                <p style="margin: 0;">Subscribers</p>
            </div>
            <div class="location-card" style="text-align: center;">
                <h3 style="margin: 0; font-size: 1.75rem;"><?php echo (int)$totalVisits; ?></h3>
                <p style="margin: 0;">Total page views</p>
            </div>
            <div class="location-card" style="text-align: center;">
                <h3 style="margin: 0; font-size: 1.75rem;"><?php echo (int)$uniquePages; ?></h3>
                <p style="margin: 0;">Pages tracked</p>
            </div>
        </div>

        <h2 style="font-family: var(--font-heading); font-size: 1.2rem; margin-top: 2rem;">Subscribers</h2>
        <?php if (empty($subs)): ?>
            <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border);">No subscribers yet. Users can subscribe via the footer on any page.</p>
        <?php else: ?>
            <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border); margin-top: 0.5rem;">
                <thead>
                    <tr style="background: var(--color-bg);">
                        <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--color-border);">Email</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--color-border);">Subscribed at</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--color-border);">Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subs as $s): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.75rem 1rem;"><a href="mailto:<?php echo htmlspecialchars($s['email'] ?? ''); ?>"><?php echo htmlspecialchars($s['email'] ?? ''); ?></a></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($s['subscribed_at'] ?? ''); ?></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($s['source'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 style="font-family: var(--font-heading); font-size: 1.2rem; margin-top: 2rem;">Views by page</h2>
        <?php if (empty($byPage)): ?>
            <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border);">No visits recorded yet. Visits are logged when users load any page (footer script).</p>
        <?php else: ?>
            <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border); margin-top: 0.5rem;">
                <thead>
                    <tr style="background: var(--color-bg);">
                        <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--color-border);">Page</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--color-border);">Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byPage as $page => $count): ?>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 0.75rem 1rem;"><code style="word-break: break-all;"><?php echo htmlspecialchars($page); ?></code></td>
                        <td style="padding: 0.75rem 1rem;"><?php echo (int)$count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($recentVisits)): ?>
        <h2 style="font-family: var(--font-heading); font-size: 1.2rem; margin-top: 2rem;">Recent visits (last 50)</h2>
        <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border); margin-top: 0.5rem;">
            <thead>
                <tr style="background: var(--color-bg);">
                    <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border);">Time</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border);">Page</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border);">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentVisits as $v): ?>
                <tr style="border-bottom: 1px solid var(--color-border); font-size: 0.9rem;">
                    <td style="padding: 0.5rem 0.75rem;"><?php echo htmlspecialchars($v['ts'] ?? ''); ?></td>
                    <td style="padding: 0.5rem 0.75rem;"><code style="word-break: break-all;"><?php echo htmlspecialchars($v['page'] ?? ''); ?></code></td>
                    <td style="padding: 0.5rem 0.75rem;"><?php echo htmlspecialchars($v['ip'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="users.php" class="btn btn-secondary">← Back to Users List</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.html'; ?>
