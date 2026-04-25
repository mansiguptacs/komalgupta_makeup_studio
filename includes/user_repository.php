<?php
require_once __DIR__ . '/../config/db.php';

function kg_get_site_users($filters = []) {
    $nameFilter = trim((string)($filters['name'] ?? ''));
    $emailFilter = trim((string)($filters['email'] ?? ''));
    $phoneFilter = trim((string)($filters['phone'] ?? ''));

    $db = kg_db();
    if ($db && kg_ensure_tables($db)) {
        $rows = [];
        $whereParts = [];
        $types = '';
        $params = [];

        if ($nameFilter !== '') {
            $whereParts[] = "name LIKE ?";
            $types .= 's';
            $params[] = '%' . $nameFilter . '%';
        }
        if ($emailFilter !== '') {
            $whereParts[] = "email LIKE ?";
            $types .= 's';
            $params[] = '%' . $emailFilter . '%';
        }
        if ($phoneFilter !== '') {
            $whereParts[] = "(cell_phone LIKE ? OR home_phone LIKE ?)";
            $types .= 'ss';
            $params[] = '%' . $phoneFilter . '%';
            $params[] = '%' . $phoneFilter . '%';
        }

        $sql = "SELECT id, name, email, joined_date, home_phone, cell_phone FROM site_users";
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        $sql .= " ORDER BY COALESCE(joined_date, '1900-01-01') DESC, id DESC";

        $stmt = $db->prepare($sql);
        if ($stmt) {
            if ($types !== '') {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $rows[] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'email' => $r['email'],
                    'joined' => $r['joined_date'] ?: '',
                    'home_phone' => $r['home_phone'] ?? '',
                    'cell_phone' => $r['cell_phone'] ?? '',
                ];
            }
            $stmt->close();
        }
        return $rows;
    }

    $usersFile = dirname(__DIR__) . '/data/site_users.json';
    if (is_readable($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        if (is_array($users)) {
            if ($nameFilter === '' && $emailFilter === '' && $phoneFilter === '') {
                return $users;
            }
            $filtered = [];
            foreach ($users as $u) {
                $name = (string)($u['name'] ?? '');
                $email = (string)($u['email'] ?? '');
                $homePhone = (string)($u['home_phone'] ?? '');
                $cellPhone = (string)($u['cell_phone'] ?? '');

                if ($nameFilter !== '' && stripos($name, $nameFilter) === false) {
                    continue;
                }
                if ($emailFilter !== '' && stripos($email, $emailFilter) === false) {
                    continue;
                }
                if ($phoneFilter !== '' && stripos($homePhone . ' ' . $cellPhone, $phoneFilter) === false) {
                    continue;
                }
                $filtered[] = $u;
            }
            return $filtered;
        }
    }
    return [];
}

function kg_seed_users_from_file_if_empty() {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return;
    }

    $countRes = $db->query("SELECT COUNT(*) AS c FROM site_users");
    $count = 0;
    if ($countRes) {
        $row = $countRes->fetch_assoc();
        $count = (int)($row['c'] ?? 0);
        $countRes->free();
    }
    if ($count > 0) {
        return;
    }

    $usersFile = dirname(__DIR__) . '/data/site_users.json';
    if (!is_readable($usersFile)) {
        return;
    }
    $users = json_decode(file_get_contents($usersFile), true);
    if (!is_array($users)) {
        return;
    }

    $stmt = $db->prepare("INSERT IGNORE INTO site_users (name, email, joined_date) VALUES (?, ?, ?)");
    if (!$stmt) {
        return;
    }
    foreach ($users as $u) {
        $name = trim((string)($u['name'] ?? ''));
        $email = trim((string)($u['email'] ?? ''));
        $joined = trim((string)($u['joined'] ?? $u['joined_date'] ?? ''));
        if ($name === '' || $email === '') {
            continue;
        }
        $joinedOrNull = $joined !== '' ? $joined : null;
        $stmt->bind_param('sss', $name, $email, $joinedOrNull);
        $stmt->execute();
    }
    $stmt->close();
}

/**
 * Copy subscribers from data/subscribers.json into DB (skip duplicates).
 */
function kg_seed_subscribers_from_file() {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return 0;
    }

    $file = dirname(__DIR__) . '/data/subscribers.json';
    if (!is_readable($file)) {
        return 0;
    }
    $list = json_decode(file_get_contents($file), true);
    if (!is_array($list)) {
        return 0;
    }

    $stmt = $db->prepare(
        "INSERT IGNORE INTO subscribers (email, source, subscribed_at) VALUES (?, ?, ?)"
    );
    if (!$stmt) {
        return 0;
    }

    $inserted = 0;
    foreach ($list as $row) {
        $email = isset($row['email']) ? trim(strtolower($row['email'])) : '';
        if ($email === '') {
            continue;
        }
        $source = trim((string)($row['source'] ?? 'footer'));
        if ($source === '') {
            $source = 'footer';
        }
        $at = trim((string)($row['subscribed_at'] ?? ''));
        if ($at === '') {
            $at = date('Y-m-d H:i:s');
        }
        $stmt->bind_param('sss', $email, $source, $at);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $inserted++;
        }
    }
    $stmt->close();
    return $inserted;
}

function kg_get_subscribers() {
    $db = kg_db();
    if ($db && kg_ensure_tables($db)) {
        $rows = [];
        $res = $db->query("SELECT email, subscribed_at, source FROM subscribers ORDER BY subscribed_at DESC, id DESC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'email' => $r['email'],
                    'subscribed_at' => $r['subscribed_at'],
                    'source' => $r['source'] ?: 'footer',
                ];
            }
            $res->free();
        }
        return $rows;
    }

    $file = dirname(__DIR__) . '/data/subscribers.json';
    if (is_readable($file)) {
        $subs = json_decode(file_get_contents($file), true);
        if (is_array($subs)) {
            usort($subs, function ($a, $b) {
                return strcmp($b['subscribed_at'] ?? '', $a['subscribed_at'] ?? '');
            });
            return $subs;
        }
    }
    return [];
}

function kg_add_subscriber($email, $source = 'footer') {
    $db = kg_db();
    if ($db && kg_ensure_tables($db)) {
        $check = $db->prepare("SELECT id FROM subscribers WHERE email = ? LIMIT 1");
        if ($check) {
            $check->bind_param('s', $email);
            $check->execute();
            $result = $check->get_result();
            if ($result && $result->num_rows > 0) {
                $check->close();
                return [true, 'You are already subscribed.'];
            }
            $check->close();
        }

        $stmt = $db->prepare("INSERT INTO subscribers (email, source, subscribed_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            return [false, 'DB prepare failed: ' . $db->error];
        }
        $stmt->bind_param('ss', $email, $source);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            return [true, 'Thanks for subscribing!'];
        }
        return [false, 'Could not save subscriber: ' . $db->error];
    }

    $file = dirname(__DIR__) . '/data/subscribers.json';
    $list = [];
    if (is_readable($file)) {
        $list = json_decode(file_get_contents($file), true) ?: [];
    }
    foreach ($list as $row) {
        if (($row['email'] ?? '') === $email) {
            return [true, 'You are already subscribed.'];
        }
    }
    $list[] = [
        'email' => $email,
        'subscribed_at' => date('Y-m-d H:i:s'),
        'source' => $source,
    ];
    file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));
    return [true, 'Thanks for subscribing!'];
}
