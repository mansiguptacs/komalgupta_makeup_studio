<?php
require_once __DIR__ . '/../config/db.php';

function kg_register_on_marketplace(string $username, string $email, string $fullName, string $password): array {
    if (!function_exists('curl_init')) {
        return [false, 'cURL not available'];
    }

    $postData = json_encode([
        'username' => $username,
        'email'    => $email,
        'full_name' => $fullName,
        'password' => $password,
    ]);

    $ch = curl_init('https://mansiguptacs.com/ourmarketplace/api/register.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    // Shared hosting (e.g. InfinityFree) often lacks an up-to-date CA bundle,
    // causing "unable to get local issuer certificate". Disable peer verification.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $body = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return [false, 'Marketplace cURL error: ' . $curlErr];
    }

    $data = json_decode($body, true);
    // 201 = created, 409 = already exists (both are success for our purposes)
    if (($httpCode === 201 || $httpCode === 409) && is_array($data)) {
        return [true, $data];
    }
    if ($httpCode >= 200 && $httpCode < 300 && is_array($data)) {
        return [true, $data];
    }

    $msg = is_array($data) && isset($data['message']) ? $data['message'] : ('HTTP ' . $httpCode);
    return [false, 'Marketplace registration failed: ' . $msg];
}

function kg_register_site_user($payload) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, 'Database is not configured.'];
    }

    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));
    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $password = (string)($payload['password'] ?? '');
    $cellPhone = trim((string)($payload['cell_phone'] ?? ''));
    $homePhone = trim((string)($payload['home_phone'] ?? ''));
    $homeAddress = trim((string)($payload['home_address'] ?? ''));

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $cellPhone === '') {
        return [false, 'Please fill all required fields.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Please enter a valid email address.'];
    }
    if (strlen($password) < 6) {
        return [false, 'Password must be at least 6 characters long.'];
    }

    $check = $db->prepare("SELECT id, password FROM site_users WHERE email = ? LIMIT 1");
    if (!$check) {
        return [false, 'Could not process registration right now.'];
    }
    $check->bind_param('s', $email);
    $check->execute();
    $exists = $check->get_result();
    if ($exists && $exists->num_rows > 0) {
        $existing = $exists->fetch_assoc();
        $check->close();
        return [false, 'An account already exists with this email. Please login.'];
    }
    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $joinedDate = date('Y-m-d');
    $stmt = $db->prepare("INSERT INTO site_users (first_name, last_name, email, password, home_address, home_phone, cell_phone, joined_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return [false, 'Could not create account right now.'];
    }
    $stmt->bind_param('ssssssss', $firstName, $lastName, $email, $passwordHash, $homeAddress, $homePhone, $cellPhone, $joinedDate);
    $ok = $stmt->execute();
    $newId = (int)$stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        return [false, 'Could not create account right now.'];
    }

    // Note: marketplace registration is handled client-side from user_register.php
    // (via KGMarketplace.register in assets/js/marketplace.js) because the marketplace
    // host (InfinityFree) uses a JavaScript anti-bot challenge that blocks server-side
    // cURL requests.

    return [true, ['id' => $newId, 'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email]];
}

function kg_authenticate_site_user($email, $password) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, 'Database is not configured.'];
    }

    $email = strtolower(trim((string)$email));
    $password = (string)$password;
    if ($email === '' || $password === '') {
        return [false, 'Email and password are required.'];
    }

    $stmt = $db->prepare("SELECT id, first_name, last_name, email, password FROM site_users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return [false, 'Could not process login right now.'];
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return [false, 'Invalid email or password.'];
    }
    if (empty($row['password'])) {
        return [false, 'Invalid email or password.'];
    }

    $storedPassword = (string)$row['password'];
    $isValid = false;
    $needsRehash = false;

    if (password_verify($password, $storedPassword)) {
        $isValid = true;
        $needsRehash = password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
    } else {
        // Backward compatibility for legacy rows that may contain plaintext passwords.
        if (hash_equals($storedPassword, $password)) {
            $isValid = true;
            $needsRehash = true;
        }
    }

    if (!$isValid) {
        return [false, 'Invalid email or password.'];
    }

    if ($needsRehash) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehash = $db->prepare("UPDATE site_users SET password = ? WHERE id = ?");
        if ($rehash) {
            $id = (int)$row['id'];
            $rehash->bind_param('si', $newHash, $id);
            $rehash->execute();
            $rehash->close();
        }
    }

    $update = $db->prepare("UPDATE site_users SET last_logged_in = NOW() WHERE id = ?");
    if ($update) {
        $id = (int)$row['id'];
        $update->bind_param('i', $id);
        $update->execute();
        $update->close();
    }

    return [true, ['id' => (int)$row['id'], 'first_name' => (string)($row['first_name'] ?? ''), 'last_name' => (string)($row['last_name'] ?? ''), 'email' => $row['email']]];
}

/**
 * Find or create a site_users row for an OurMarketplace SSO identity so dashboard
 * bookings and reviews keep a stable numeric user_id (FK on user_bookings / reviews).
 *
 * @return array Tuple: [bool success, ?array userRow, string errorMessage]
 */
function kg_sso_upsert_site_user_from_marketplace(int $marketplaceUserId, string $username, string $fullName): array {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, null, 'Database is not configured.'];
    }

    $username = trim($username);
    $fullName = trim($fullName);
    if ($username === '') {
        $username = 'user' . $marketplaceUserId;
    }

    $email = 'mp-' . $marketplaceUserId . '@sso.kgmakeupstudio.local';
    $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY);
    $firstName = isset($parts[0]) ? (string)$parts[0] : $username;
    $lastName = isset($parts[1]) ? (string)$parts[1] : '';

    $stmt = $db->prepare('SELECT id, first_name, last_name, email FROM site_users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return [false, null, 'Could not look up SSO user.'];
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row) {
        $id = (int)$row['id'];
        $upd = $db->prepare('UPDATE site_users SET first_name = ?, last_name = ?, last_logged_in = NOW() WHERE id = ?');
        if ($upd) {
            $upd->bind_param('ssi', $firstName, $lastName, $id);
            $upd->execute();
            $upd->close();
        }
        return [true, [
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string)$row['email'],
        ], ''];
    }

    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $joinedDate = date('Y-m-d');
    $cellPhone = '';
    $homePhone = '';
    $homeAddress = '';

    $ins = $db->prepare('INSERT INTO site_users (first_name, last_name, email, password, home_address, home_phone, cell_phone, joined_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$ins) {
        return [false, null, 'Could not create SSO-linked site user.'];
    }
    $ins->bind_param('ssssssss', $firstName, $lastName, $email, $passwordHash, $homeAddress, $homePhone, $cellPhone, $joinedDate);
    if (!$ins->execute()) {
        $ins->close();
        return [false, null, 'Could not create SSO-linked site user.'];
    }
    $newId = (int)$ins->insert_id;
    $ins->close();

    return [true, [
        'id' => $newId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
    ], ''];
}

function kg_get_services_catalog() {
    $file = dirname(__DIR__) . '/data/services.csv';
    return kg_read_csv_assoc($file);
}

function kg_get_products_catalog() {
    $file = dirname(__DIR__) . '/data/products.csv';
    return kg_read_csv_assoc($file);
}

function kg_create_user_booking($user, $payload) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, 'Database is not configured.'];
    }
    $fallbackName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
    $name = trim((string)($payload['name'] ?? $fallbackName));
    $email = strtolower(trim((string)($payload['email'] ?? $user['email'])));
    $cellPhone = trim((string)($payload['cell_phone'] ?? ''));
    $bookingDate = trim((string)($payload['booking_date'] ?? ''));
    $service = trim((string)($payload['service_interested_in'] ?? ''));
    $message = trim((string)($payload['message'] ?? ''));
    $userId = (int)$user['id'];

    if ($name === '' || $email === '' || $cellPhone === '' || $bookingDate === '' || $service === '') {
        return [false, 'Please fill all required booking fields.'];
    }

    $stmt = $db->prepare("INSERT INTO user_bookings (name, email, cell_phone, booking_date, service_interested_in, message, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return [false, 'Could not create booking right now.'];
    }
    $stmt->bind_param('ssssssi', $name, $email, $cellPhone, $bookingDate, $service, $message, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return [false, 'Could not create booking right now.'];
    }
    return [true, 'Appointment booked successfully.'];
}

function kg_get_user_bookings($userId, $page = 1, $perPage = 5) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return ['rows' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    }
    $userId = (int)$userId;
    $page = max(1, (int)$page);
    $perPage = max(1, (int)$perPage);

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM user_bookings WHERE user_id = ?");
    $total = 0;
    if ($countStmt) {
        $countStmt->bind_param('i', $userId);
        $countStmt->execute();
        $res = $countStmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $total = (int)($row['c'] ?? 0);
        $countStmt->close();
    }
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $stmt = $db->prepare("SELECT booking_id, name, email, cell_phone, booking_date, service_interested_in, message, created_at FROM user_bookings WHERE user_id = ? ORDER BY booking_date DESC, booking_id DESC LIMIT ? OFFSET ?");
    $rows = [];
    if ($stmt) {
        $stmt->bind_param('iii', $userId, $perPage, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function kg_save_review($userId, $type, $itemId, $rating, $reviewText) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, 'Database is not configured.'];
    }
    $userId = (int)$userId;
    $itemId = (int)$itemId;
    $rating = (float)$rating;
    $reviewText = trim((string)$reviewText);
    $type = $type === 'service' ? 'service' : 'product';

    if ($itemId <= 0 || $rating < 1 || $rating > 5) {
        return [false, 'Please choose a valid item and rating between 1 and 5.'];
    }

    if ($type === 'service') {
        $stmt = $db->prepare("INSERT INTO service_reviews (user_id, service_id, rating, review_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), updated_at = CURRENT_TIMESTAMP");
    } else {
        $stmt = $db->prepare("INSERT INTO product_reviews (user_id, product_id, rating, review_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), updated_at = CURRENT_TIMESTAMP");
    }
    if (!$stmt) {
        return [false, 'Could not save review right now.'];
    }
    $stmt->bind_param('iids', $userId, $itemId, $rating, $reviewText);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return [false, 'Could not save review right now.'];
    }
    return [true, 'Review saved successfully.'];
}

function kg_get_user_reviews($userId, $limit = 20) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [];
    }
    $userId = (int)$userId;
    $limit = max(1, (int)$limit);
    $reviews = [];

    $q1 = $db->prepare("SELECT 'product' AS review_type, product_id AS item_id, rating, review_text, updated_at FROM product_reviews WHERE user_id = ? ORDER BY updated_at DESC LIMIT ?");
    if ($q1) {
        $q1->bind_param('ii', $userId, $limit);
        $q1->execute();
        $res = $q1->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $reviews[] = $row;
        }
        $q1->close();
    }

    $q2 = $db->prepare("SELECT 'service' AS review_type, service_id AS item_id, rating, review_text, updated_at FROM service_reviews WHERE user_id = ? ORDER BY updated_at DESC LIMIT ?");
    if ($q2) {
        $q2->bind_param('ii', $userId, $limit);
        $q2->execute();
        $res = $q2->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $reviews[] = $row;
        }
        $q2->close();
    }

    usort($reviews, function ($a, $b) {
        return strcmp((string)$b['updated_at'], (string)$a['updated_at']);
    });
    return array_slice($reviews, 0, $limit);
}

function kg_read_csv_assoc($filename) {
    $data = [];
    if (!is_readable($filename)) {
        return $data;
    }
    $handle = fopen($filename, 'r');
    if ($handle === false) {
        return $data;
    }
    $headers = fgetcsv($handle, 0, ',', '"', '\\');
    if (!is_array($headers)) {
        fclose($handle);
        return $data;
    }
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (count($headers) !== count($row)) {
            continue;
        }
        $data[] = array_combine($headers, $row);
    }
    fclose($handle);
    return $data;
}
