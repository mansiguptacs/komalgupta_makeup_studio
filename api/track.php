<?php
/**
 * Lightweight visit tracking. Appends page view to data/visits.json.
 * Called via fetch on each page load (footer script).
 * GET ?page=/path — optional ref for referrer context later.
 */
header('Cache-Control: no-store');

$page = isset($_GET['page']) ? trim($_GET['page']) : '';
if ($page === '') {
    $page = '/';
}
// Basic sanitization / length limit
if (strlen($page) > 500) {
    $page = substr($page, 0, 500);
}

$entry = [
    'page' => $page,
    'ts' => date('Y-m-d H:i:s'),
    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
    'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : '',
];

$file = dirname(__DIR__) . '/data/visits.json';
$list = [];
if (file_exists($file)) {
    $list = json_decode(file_get_contents($file), true) ?: [];
}
$list[] = $entry;
// Cap file size: keep last 5000 entries
if (count($list) > 5000) {
    $list = array_slice($list, -5000);
}
file_put_contents($file, json_encode($list));

// Return 204 or tiny transparent gif so img-based tracking would work too
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
