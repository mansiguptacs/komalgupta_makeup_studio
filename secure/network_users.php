<?php
/**
 * Admin page: show local users + friend users via cURL.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../login.php');
require_once __DIR__ . '/../includes/user_repository.php';
require_once __DIR__ . '/../config/db.php';

$page_title = 'Network Users';
$current_page = 'users';
require_once __DIR__ . '/../includes/header.php';

function kg_parse_endpoints(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    // Supports comma separated and whitespace separated URLs.
    $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        // Basic safety: only accept http(s) endpoints.
        if (!preg_match('~^https?://~i', $p)) continue;
        $out[] = $p;
    }
    return array_values(array_unique($out));
}

function kg_url_with_key(string $url, string $key): string {
    if ($key === '') return $url;
    if (preg_match('/([?&])key=/', $url)) return $url; // don't double-add
    $sep = (parse_url($url, PHP_URL_QUERY) === null) ? '?' : '&';
    return $url . $sep . 'key=' . urlencode($key);
}

function kg_normalize_friend_users(array $users, string $fallbackSourceName): array {
    $normalized = [];
    foreach ($users as $u) {
        if (!is_array($u)) continue;
        $email = trim((string)($u['email'] ?? ''));
        if ($email === '') continue;
        $name = (string)($u['name'] ?? '');
        $joined = (string)( $u['joined_date'] ?? $u['subscribed_at'] ?? '');
        $normalized[] = [
            'name' => $name,
            'email' => $email,
            'joined_date' => $joined,
            'source' => $fallbackSourceName,
        ];
    }
    return $normalized;
}

function kg_fetch_friend_users(string $url, string $accessKey, string $sourceName, string &$error): array {
    $error = '';
    if (!function_exists('curl_init')) {
        $error = 'cURL extension is not enabled on this host.';
        return [];
    }

    $reqUrl = kg_url_with_key($url, $accessKey);

    $ch = curl_init($reqUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $headers = ['Accept: application/json'];
    if ($accessKey !== '') {
        $headers[] = 'X-Friend-Key: ' . $accessKey;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        $error = 'cURL error: ' . $curlErr;
        return [];
    }
    if ($code < 200 || $code >= 300) {
        $error = 'Friend API returned HTTP ' . $code;
        return [];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        $error = 'Friend API returned invalid JSON.';
        return [];
    }

    // Expected: { success: true, users: [...] }
    $users = [];
    if (isset($data) && is_array($data)) {
        $users = $data;
    }
    // elseif (isset($data['data']) && is_array($data['data'])) {
    //    $users = $data['data'];
    //} 
    elseif (array_is_list($data)) {
        // Allow friend APIs that return users directly as an array.
        $users = $data;
    } else {
        $error = 'Friend API JSON format invalid. Expected  users: [] ';
        return [];
    }

    return kg_normalize_friend_users($users, $sourceName);
}

$localUsers = kg_get_site_users();
$cfg = kg_db_config();

$friendEndpointsRaw = (string)($cfg['friend_users_api'] ?? '');
$friendAccessKey = (string)($cfg['friend_access_key'] ?? '');
$friendEndpoints = kg_parse_endpoints($friendEndpointsRaw);

$friendResults = []; // each: ['url','label','users','error']
$combinedByEmail = [];

foreach ($friendEndpoints as $idx => $endpoint) {
    $host = parse_url($endpoint, PHP_URL_HOST) ?: ('Friend #' . ($idx + 1));
    $friendlyLabel = (string)$host;

    $error = '';
    $users = kg_fetch_friend_users($endpoint, $friendAccessKey, $friendlyLabel, $error);
    $friendResults[] = [
        'url' => $endpoint,
        'label' => $friendlyLabel,
        'users' => $users,
        'error' => $error,
    ];

    foreach ($users as $u) {
        $emailKey = strtolower(trim((string)($u['email'] ?? '')));
        if ($emailKey === '') continue;
        if (!isset($combinedByEmail[$emailKey])) {
            $combinedByEmail[$emailKey] = [
                'name' => $u['name'] ?? '',
                'email' => $u['email'] ?? '',
                'joined_date' => $u['joined_date'] ?? '',
                'sources' => [],
            ];
        }
        if (!in_array($friendlyLabel, $combinedByEmail[$emailKey]['sources'], true)) {
            $combinedByEmail[$emailKey]['sources'][] = $friendlyLabel;
        }
        if (empty($combinedByEmail[$emailKey]['name']) && !empty($u['name'])) {
            $combinedByEmail[$emailKey]['name'] = $u['name'];
        }
        // Keep first non-empty joined value.
        if (empty($combinedByEmail[$emailKey]['joined_date']) && !empty($u['joined_date'])) {
            $combinedByEmail[$emailKey]['joined_date'] = $u['joined_date'];
        }
    }
}

$combinedFriendUsers = array_values($combinedByEmail);
?>
<section class="page-section">
  <div class="container">
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap;">
      <h1>Users from Network</h1>
      <a href="users.php" class="btn btn-secondary">Back to Users</a>
    </div>
    <p class="lead">Your users plus users fetched from your friend's site(s) using cURL.</p>

    <h2 style="font-family:var(--font-heading);">Your users</h2>
    <table class="user-table" style="width:100%; border-collapse:collapse; background:var(--color-surface); border:1px solid var(--color-border);">
      <thead><tr style="background:var(--color-bg);"><th style="padding:.75rem; text-align:left;">Name</th><th style="padding:.75rem; text-align:left;">Email</th><th style="padding:.75rem; text-align:left;">Joined</th></tr></thead>
      <tbody>
        <?php foreach ($localUsers as $u): ?>
          <tr><td style="padding:.75rem;"><?php echo htmlspecialchars($u['name'] ?? ''); ?></td><td style="padding:.75rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td><td style="padding:.75rem;"><?php echo htmlspecialchars($u['joined'] ?? ''); ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($localUsers)): ?><tr><td colspan="3" style="padding:.75rem;">No local users found.</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h2 style="font-family:var(--font-heading); margin-top:2rem;">Friend users (combined)</h2>
    <?php if (empty($friendEndpoints)): ?>
      <p class="message error">No friend API URLs configured. Set <code>friend_users_api</code> in <code>config/db_credentials.php</code> (comma/whitespace separated).</p>
    <?php endif; ?>
    <table class="user-table" style="width:100%; border-collapse:collapse; background:var(--color-surface); border:1px solid var(--color-border);">
      <thead><tr style="background:var(--color-bg);"><th style="padding:.75rem; text-align:left;">Name</th><th style="padding:.75rem; text-align:left;">Email</th><th style="padding:.75rem; text-align:left;">Joined</th><th style="padding:.75rem; text-align:left;">From</th></tr></thead>
      <tbody>
        <?php foreach ($combinedFriendUsers as $u): ?>
          <tr>
            <td style="padding:.75rem;"><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
            <td style="padding:.75rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
            <td style="padding:.75rem;"><?php echo htmlspecialchars($u['joined'] ?? ''); ?></td>
            <td style="padding:.75rem;"><?php echo htmlspecialchars(implode(', ', $u['sources'] ?? [])); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($combinedFriendUsers)): ?><tr><td colspan="4" style="padding:.75rem;">No friend users available.</td></tr><?php endif; ?>
      </tbody>
    </table>

    <?php if (!empty($friendResults)): ?>
      <h2 style="font-family:var(--font-heading); margin-top:2rem;">Friend users (per site)</h2>
      <?php foreach ($friendResults as $res): ?>
        <details style="margin:1rem 0; background:var(--color-surface); border:1px solid var(--color-border); border-radius:12px; padding:1rem;">
          <summary style="cursor:pointer; font-weight:700;">
            <?php echo htmlspecialchars($res['label']); ?>
            <span style="font-weight:400; color:var(--color-muted);">
              (<?php echo (int)count($res['users']); ?> users)
            </span>
          </summary>
          <?php if (!empty($res['error'])): ?>
            <p class="message error" style="margin:.75rem 0 0;"><?php echo htmlspecialchars($res['error']); ?></p>
          <?php endif; ?>
          <table class="user-table" style="width:100%; margin-top:.75rem; border-collapse:collapse; background:transparent; border:1px solid var(--color-border);">
            <thead><tr style="background:var(--color-bg);"><th style="padding:.6rem; text-align:left;">Name</th><th style="padding:.6rem; text-align:left;">Email</th><th style="padding:.6rem; text-align:left;">Joined</th></tr></thead>
            <tbody>
              <?php foreach ($res['users'] as $u): ?>
                <tr>
                  <td style="padding:.6rem;"><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
                  <td style="padding:.6rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                  <td style="padding:.6rem;"><?php echo htmlspecialchars($u['joined_date'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($res['users'])): ?><tr><td colspan="3" style="padding:.6rem;">No users fetched from this site.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </details>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.html'; ?>
