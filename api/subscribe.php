<?php
/**
 * Subscribe endpoint: saves email to data/subscribers.json for admin analytics.
 */
header('Content-Type: application/json');

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

$file = dirname(__DIR__) . '/data/subscribers.json';
$list = [];
if (file_exists($file)) {
    $list = json_decode(file_get_contents($file), true) ?: [];
}

// Avoid duplicate by email
foreach ($list as $row) {
    if (isset($row['email']) && $row['email'] === $email) {
        echo json_encode(['success' => true, 'message' => 'You are already subscribed.']);
        exit;
    }
}

$list[] = [
    'email' => $email,
    'subscribed_at' => date('Y-m-d H:i:s'),
    'source' => 'footer',
];
file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'message' => 'Thanks for subscribing!']);
