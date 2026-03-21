<?php
/**
 * Team members (public site shows only active rows).
 */
require_once __DIR__ . '/../config/db.php';

/**
 * @return array<int, array{name:string,email:string,photo_url:?string,designation:string}>
 */
function kg_get_active_team_members() {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [];
    }
    $sql = 'SELECT name, email, photo_url, designation
            FROM team_members
            WHERE is_active = 1
            ORDER BY sort_order ASC, name ASC';
    $res = $db->query($sql);
    if (!$res) {
        return [];
    }
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'name' => (string)$row['name'],
            'email' => (string)$row['email'],
            'photo_url' => isset($row['photo_url']) && $row['photo_url'] !== ''
                ? (string)$row['photo_url']
                : null,
            'designation' => (string)$row['designation'],
        ];
    }
    $res->free();
    return $out;
}
