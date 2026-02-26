<?php
// Simple endpoint to save cart additions to a CSV file

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || empty($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$product_id = trim($data['product_id']);
$timestamp = date('Y-m-d H:i:s');

$cart_file = __DIR__ . '/data/cart.csv';

// Check if file exists to write headers first time
$file_exists = file_exists($cart_file);

// Open file in append mode
$handle = fopen($cart_file, 'a');

if ($handle === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not open cart file for writing']);
    exit;
}

// Write headers if new file
if (!$file_exists) {
    fputcsv($handle, ['timestamp', 'product_id', 'quantity']);
}

// Write the new cart item (default quantity 1)
$success = fputcsv($handle, [$timestamp, $product_id, 1]);
fclose($handle);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write to cart file']);
}
