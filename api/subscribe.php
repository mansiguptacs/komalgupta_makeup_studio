<?php
/**
 * Subscribe endpoint: saves email in DB (fallback file when DB unavailable).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/user_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$email = isset($data['email']) ? trim(strtolower($data['email'])) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

[$ok, $msg] = kg_add_subscriber($email, 'footer');
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

echo json_encode(['success' => true, 'message' => $msg]);
