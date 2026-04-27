<?php
/**
 * Admin login API.
 * POST: userid, password (form or JSON)
 * Validates against file-based credentials (hashed password with salt).
 * On success: sets session and returns JSON success + redirect URL.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_response.php';
kg_send_json_headers('*');
kg_handle_preflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (isSiteUserSessionActive()) {
    echo json_encode([
        'success' => false,
        'error' => 'A user account session is already active. Please logout from user account first, then login as admin.',
    ]);
    exit;
}

$credFile = dirname(__DIR__) . '/data/admin_users.json';
if (!is_readable($credFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server configuration error. Run init_admin.php to create credentials.']);
    exit;
}

// Accept both form-urlencoded and JSON
$userid = null;
$password = null;
$returnUrl = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $userid = isset($data['userid']) ? trim($data['userid']) : null;
    $password = isset($data['password']) ? $data['password'] : null;
    $returnUrl = isset($data['return']) ? $data['return'] : null;
} else {
    $userid = isset($_POST['userid']) ? trim($_POST['userid']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $returnUrl = isset($_POST['return']) ? $_POST['return'] : null;
}
if ($returnUrl === null) {
    $returnUrl = isset($_GET['return']) ? $_GET['return'] : null;
}

if (empty($userid) || $password === null || $password === '') {
    echo json_encode(['success' => false, 'error' => 'User ID and password are required.']);
    exit;
}

$admins = json_decode(file_get_contents($credFile), true);
if (!is_array($admins) || !isset($admins[$userid]) || !isset($admins[$userid]['hash'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID or password.']);
    exit;
}

$storedHash = $admins[$userid]['hash'];
if (!password_verify($password, $storedHash)) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID or password.']);
    exit;
}

$_SESSION['admin_user'] = $userid;
if (empty($returnUrl)) {
    $returnUrl = 'secure/users.php';
}
if (strpos($returnUrl, 'http') === 0) {
    $returnUrl = 'secure/users.php';
}

echo json_encode([
    'success' => true,
    'redirect' => $returnUrl,
]);
