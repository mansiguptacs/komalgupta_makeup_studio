<?php
/**
 * Shared API response headers for JSON endpoints.
 */
require_once __DIR__ . '/php_logging.php';

function kg_send_json_headers($allowOrigin = '*') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . $allowOrigin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Friend-Key');
    header('Access-Control-Max-Age: 600');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Origin');
}

function kg_handle_preflight() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
