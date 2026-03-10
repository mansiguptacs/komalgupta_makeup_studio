<?php
/**
 * One-time script to create admin credentials file.
 * Run from CLI: php init_admin.php
 * Or once from browser then delete or protect this file.
 *
 * Creates data/admin_users.json with userid "admin" and a hashed password (with salt).
 * Default password is "admin123" - change below if needed.
 */

$dataDir = __DIR__ . '/data';
$credFile = $dataDir . '/admin_users.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0750, true);
}

$password = 'admin123'; // Change this if you want a different initial password
$hash = password_hash($password, PASSWORD_DEFAULT); // PASSWORD_DEFAULT uses bcrypt (includes salt)

$admins = [
    'admin' => [
        'hash' => $hash,
    ],
];

$json = json_encode($admins, JSON_PRETTY_PRINT);
if (file_put_contents($credFile, $json) !== false) {
    echo "Admin credentials file created: $credFile\n";
    echo "User ID: admin\n";
    echo "Password: $password\n";
    echo "Store this file outside the web root in production.\n";
} else {
    echo "Failed to write credentials file.\n";
    exit(1);
}
