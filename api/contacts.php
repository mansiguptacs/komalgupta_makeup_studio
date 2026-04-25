<?php
require_once __DIR__ . '/../includes/api_response.php';
kg_send_json_headers('*');
header('Content-Type: application/json');

function getContacts($filename) {
    if (!file_exists($filename) || !is_readable($filename)) return [];
    
    $content = file_get_contents($filename);
    $blocks = explode("\n\n", str_replace("\r", "", trim($content)));
    $contacts = [];

    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $contact = [];
        $lines = explode("\n", $block);
        
        foreach ($lines as $line) {
            $parts = explode(":", $line, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $contact[$key] = trim($parts[1]);
            }
        }
        
        if (!empty($contact)) {
            $contacts[] = $contact;
        }
    }
    return $contacts;
}

$contacts_file = __DIR__ . '/../data/contacts.txt';
echo json_encode(getContacts($contacts_file));
