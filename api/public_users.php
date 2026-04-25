<?php
/**
 * Public JSON endpoint to share this site's users with trusted friend sites.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/user_repository.php';

kg_seed_users_from_file_if_empty();
$users = kg_get_site_users();
// Plain JSON array — same shape as api/users.php for friend integrations
echo json_encode($users);
