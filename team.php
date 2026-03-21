<?php
$page_title = 'Our Team';
require_once __DIR__ . '/includes/team_repository.php';

$members = kg_get_active_team_members();
$dbOk = kg_db() !== null;

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-section team-section">
    <div class="container">
        <h1>Our Team</h1>
        <p class="lead">Meet the artists behind Komal Gupta Makeup Studio.</p>

        <?php if (!$dbOk): ?>
            <p class="message" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1rem; border-radius: var(--radius);">
                Team profiles will load from the database once your site is connected to MySQL. Run <strong>admin_setup_db.php</strong> (while logged in as admin) to create tables, then add rows in <code>team_members</code>.
            </p>
        <?php elseif (empty($members)): ?>
            <p class="message" style="color: var(--color-text-muted);">No team members to show yet. Add active members in the <code>team_members</code> table (set <code>is_active = 1</code>).</p>
        <?php else: ?>
            <div class="team-grid">
                <?php foreach ($members as $m):
                    $initials = '';
                    $parts = preg_split('/\s+/', trim($m['name']));
                    if (!empty($parts[0])) {
                        $initials .= strtoupper(substr($parts[0], 0, 1));
                    }
                    if (!empty($parts[1])) {
                        $initials .= strtoupper(substr($parts[1], 0, 1));
                    }
                    ?>
                    <article class="team-card">
                        <div class="team-card-photo-wrap">
                            <?php if (!empty($m['photo_url'])): ?>
                                <img class="team-card-photo" src="<?php echo htmlspecialchars($m['photo_url']); ?>" alt="" width="320" height="320" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="team-card-photo team-card-photo--placeholder" aria-hidden="true"><?php echo htmlspecialchars($initials ?: '?'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="team-card-body">
                            <h2 class="team-card-name"><?php echo htmlspecialchars($m['name']); ?></h2>
                            <p class="team-card-role"><?php echo htmlspecialchars($m['designation']); ?></p>
                            <a class="team-card-email" href="mailto:<?php echo htmlspecialchars($m['email']); ?>"><?php echo htmlspecialchars($m['email']); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.html'; ?>
