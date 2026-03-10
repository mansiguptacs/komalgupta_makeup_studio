<?php
/**
 * Secure section: list of booked appointments.
 * Accessible only after administrator login.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../login.php');

$page_title = 'Appointments';
$current_page = 'appointments'; // We can distinguish it if we want in header
require_once __DIR__ . '/../includes/header.php';

$appFile = __DIR__ . '/../data/appointments.json';
$apps = [];
if (is_readable($appFile)) {
    $apps = json_decode(file_get_contents($appFile), true);
    if (!is_array($apps)) {
        $apps = [];
    }
}
// Sort by created_at descending (newest first)
usort($apps, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

?>

<section class="page-section">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h1>Appointment Requests</h1>
            <div>
                <a href="analytics.php" class="btn btn-secondary" style="margin-right: 8px;">Analytics</a>
                <a href="users.php" class="btn btn-secondary" style="margin-right: 8px;">Users List</a>
                <a href="../api/logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </div>
        <p class="lead">View and reply to customer appointment requests.</p>

        <div class="app-list-doc" style="margin-top: 1.5rem;">
            <?php if (empty($apps)): ?>
                <div class="message" style="background: var(--color-surface); border: 1px solid var(--color-border);">
                    <p>No appointment requests yet.</p>
                </div>
            <?php else: ?>
                <table class="user-table" style="width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border);">
                    <thead>
                        <tr style="background: var(--color-bg);">
                            <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Date Submitted</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Client Details</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Appointment Details</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-family: var(--font-heading); border-bottom: 1px solid var(--color-border);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apps as $a): ?>
                        <tr style="border-bottom: 1px solid var(--color-border); vertical-align: top;">
                            <td style="padding: 0.75rem 1rem;">
                                <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($a['created_at']))); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <strong><?php echo htmlspecialchars($a['name']); ?></strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($a['email']); ?>"><?php echo htmlspecialchars($a['email']); ?></a><br>
                                <a href="tel:<?php echo htmlspecialchars($a['phone']); ?>"><?php echo htmlspecialchars($a['phone']); ?></a>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <strong>Location:</strong> <?php echo htmlspecialchars($a['location']); ?><br>
                                <strong>Date:</strong> <?php echo htmlspecialchars($a['date'] ?: 'Not specified'); ?><br>
                                <strong>Service:</strong> <?php echo htmlspecialchars($a['service'] ?: 'Not specified'); ?><br>
                                <?php if (!empty($a['message'])): ?>
                                    <p style="margin-top: 5px; font-size: 0.9em; color: var(--color-text-muted);">
                                        <em>"<?php echo nl2br(htmlspecialchars($a['message'])); ?>"</em>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <a href="mailto:<?php echo htmlspecialchars($a['email']); ?>?subject=Re: Your Appointment Request at KG Makeup Studio" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Reply by Email</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
            <a href="users.php" class="btn btn-secondary">← Back to Users List</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.html'; ?>
