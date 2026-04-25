<?php
/**
 * Central DB connector (MySQLi) for InfinityFree.
 */

/** @var string|null Last connection or query error (for admin diagnostics) */
$GLOBALS['kg_db_last_error'] = null;

function kg_db_config() {
    $default = [
        'host' => 'sql303.infinityfree.com',
        'port' => 3306,
        'database' => 'if0_41339591_kgmakeupstudio',
        'username' => 'if0_41339591',
        'password' => '',
        'friend_users_api' => '',
        // Optional shared secret for your friend sites to access api/users.php
        // (network_users.php will pass this if it's set)
        'friend_access_key' => '',
    ];

    foreach (['db_credentials.php', 'db_credentials.local.php'] as $name) {
        $credFile = __DIR__ . '/' . $name;
        if (is_readable($credFile)) {
            $fromFile = require $credFile;
            if (is_array($fromFile)) {
                return array_merge($default, $fromFile);
            }
        }
    }
    return $default;
}

function kg_db_last_error() {
    return isset($GLOBALS['kg_db_last_error']) ? (string)$GLOBALS['kg_db_last_error'] : '';
}

function kg_db() {
    static $db = null;
    static $attempted = false;
    $GLOBALS['kg_db_last_error'] = null;

    if ($db instanceof mysqli) {
        return $db;
    }
    if ($attempted) {
        return null;
    }
    $attempted = true;

    $cfg = kg_db_config();
    if (empty($cfg['password'])) {
        $GLOBALS['kg_db_last_error'] = 'No database password in config/db_credentials.php (password is empty).';
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($cfg['host'], $cfg['username'], $cfg['password'], $cfg['database'], (int)$cfg['port']);
    if ($conn->connect_errno) {
        $GLOBALS['kg_db_last_error'] = 'MySQL connect failed: ' . $conn->connect_error . ' (errno ' . $conn->connect_errno . ')';
        return null;
    }
    $conn->set_charset('utf8mb4');
    $db = $conn;
    return $db;
}

function kg_ensure_tables($db) {
    $GLOBALS['kg_db_last_error'] = null;

    $sql = [
        'CREATE TABLE IF NOT EXISTS site_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            joined_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS user_bookings (
            booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            cell_phone VARCHAR(30) NOT NULL,
            booking_date DATE NOT NULL,
            service_interested_in VARCHAR(190) NOT NULL,
            message TEXT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_bookings_user (user_id),
            INDEX idx_user_bookings_date (booking_date),
            CONSTRAINT fk_user_bookings_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS product_reviews (
            review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            rating DECIMAL(2,1) NOT NULL,
            review_text TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_product (user_id, product_id),
            INDEX idx_product_reviews_product (product_id),
            CONSTRAINT fk_product_reviews_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS service_reviews (
            review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            service_id INT UNSIGNED NOT NULL,
            rating DECIMAL(2,1) NOT NULL,
            review_text TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_service (user_id, service_id),
            INDEX idx_service_reviews_service (service_id),
            CONSTRAINT fk_service_reviews_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            source VARCHAR(50) DEFAULT \'footer\',
            subscribed_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS team_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            photo_url VARCHAR(512) NULL,
            designation VARCHAR(150) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    ];

    foreach ($sql as $q) {
        if (!$db->query($q)) {
            $GLOBALS['kg_db_last_error'] = 'SQL error: ' . $db->error;
            return false;
        }
    }
    if (!kg_ensure_site_users_columns($db)) {
        return false;
    }
    return true;
}

function kg_ensure_site_users_columns($db) {
    $columns = [
        'first_name' => 'VARCHAR(120) NULL',
        'last_name' => 'VARCHAR(120) NULL',
        'home_address' => 'VARCHAR(255) NULL',
        'home_phone' => 'VARCHAR(30) NULL',
        'cell_phone' => 'VARCHAR(30) NULL',
        'last_logged_in' => 'DATETIME NULL',
        'password' => 'VARCHAR(200) NULL',
    ];

    foreach ($columns as $name => $def) {
        if (!kg_add_column_if_missing($db, 'site_users', $name, $def)) {
            return false;
        }
    }
    return true;
}

