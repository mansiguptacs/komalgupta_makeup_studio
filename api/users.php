<?php
/**
 * JSON endpoint for users list.
 * Useful for cURL integration between friendly websites.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/user_repository.php';

// Optional shared secret check:
// If config has friend_access_key set, friends must call:
// - /api/users.php?key=THE_KEY
// or send HTTP header: X-Friend-Key: THE_KEY
require_once __DIR__ . '/../config/db.php';
$cfg = kg_db_config();
$requiredKey = (string)($cfg['friend_access_key'] ?? '');
if ($requiredKey !== '') {
    $providedKey = (string)($_GET['key'] ?? $_SERVER['HTTP_X_FRIEND_KEY'] ?? '');
    if (!hash_equals($requiredKey, $providedKey)) {
        http_response_code(403);
        // Not a user array — avoids confusion with "no users"
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

kg_seed_users_from_file_if_empty();
$users = kg_get_site_users();

// Plain JSON array of user objects (name, email, joined, …) for friend-site cURL
echo json_encode($users);
