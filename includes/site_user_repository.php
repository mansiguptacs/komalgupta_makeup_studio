<?php
require_once __DIR__ . '/../config/db.php';

function kg_register_site_user($payload) {
    $db = kg_db();
    if (!$db || !kg_ensure_tables($db)) {
        return [false, 'Database is not configured.'];
    }

    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));
    $name = trim($firstName . ' ' . $lastName);
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

    $check = $db->prepare("SELECT id, name, password FROM site_users WHERE email = ? LIMIT 1");
    if (!$check) {
        return [false, 'Could not process registration right now.'];
    }
    $check->bind_param('s', $email);
    $check->execute();
    $exists = $check->get_result();
    if ($exists && $exists->num_rows > 0) {
        $existing = $exists->fetch_assoc();
        $check->close();
        // Migration path: allow setting password for existing imported users
        // that were created before auth support and have no password yet.
        if (empty($existing['password'])) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $existingId = (int)($existing['id'] ?? 0);
            $update = $db->prepare("UPDATE site_users SET name = ?, first_name = ?, last_name = ?, home_address = ?, home_phone = ?, cell_phone = ?, password = ? WHERE id = ?");
            if (!$update) {
                return [false, 'Could not activate account right now.'];
            }
            $update->bind_param('sssssssi', $name, $firstName, $lastName, $homeAddress, $homePhone, $cellPhone, $passwordHash, $existingId);
            $ok = $update->execute();
            $update->close();
            if (!$ok) {
                return [false, 'Could not activate account right now.'];
            }
            return [true, ['id' => $existingId, 'name' => $name, 'email' => $email]];
        }
        return [false, 'An account already exists with this email. Please login.'];
    }
    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $joinedDate = date('Y-m-d');
    $stmt = $db->prepare("INSERT INTO site_users (name, email, joined_date, first_name, last_name, home_address, home_phone, cell_phone, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return [false, 'Could not create account right now.'];
    }
    $stmt->bind_param('sssssssss', $name, $email, $joinedDate, $firstName, $lastName, $homeAddress, $homePhone, $cellPhone, $passwordHash);
    $ok = $stmt->execute();
    $newId = (int)$stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        return [false, 'Could not create account right now.'];
    }
    return [true, ['id' => $newId, 'name' => $name, 'email' => $email]];
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

    $stmt = $db->prepare("SELECT id, name, email, password FROM site_users WHERE email = ? LIMIT 1");
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
        return [false, 'This account exists but password is not set yet. Please register again with the same email to activate it.'];
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

    return [true, ['id' => (int)$row['id'], 'name' => (string)($row['name'] ?? ''), 'email' => $row['email']]];
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
    $name = trim((string)($payload['name'] ?? $user['name']));
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
