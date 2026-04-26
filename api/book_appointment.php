<?php
require_once __DIR__ . '/../includes/api_response.php';
kg_send_json_headers('*');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Standard JSON input format
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // In the future this will hit a database to reserve the slot. 
    // Right now, return success.
    
    if (isset($data['name']) && isset($data['email'])) {
        $file = dirname(__DIR__) . '/data/appointments.json';
        $apps = [];
        if (file_exists($file)) {
            $apps = json_decode(file_get_contents($file), true) ?: [];
        }
        $apps[] = [
            'booking_id' => uniqid(),
            'name' => trim($data['name'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'cell_phone' => trim($data['phone'] ?? ''),
            'location' => trim($data['location'] ?? 'Civil Lines, Badaun, Uttar Pradesh') ?: 'Civil Lines, Badaun, Uttar Pradesh',
            'date' => trim($data['date'] ?? ''),
            'service' => trim($data['service'] ?? ''),
            'message' => trim($data['message'] ?? ''),
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($file, json_encode($apps, JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true, 'message' => 'Appointment request received.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing fields.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid method.']);
}
