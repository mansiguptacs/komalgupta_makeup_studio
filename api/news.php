<?php
require_once __DIR__ . '/../includes/php_logging.php';
header('Content-Type: application/json');

$news_file = __DIR__ . '/../data/news.csv';
$news_items = [];
if (file_exists($news_file) && is_readable($news_file)) {
    $handle = fopen($news_file, 'r');
    if ($handle !== false) {
        $header = array_map('trim', fgetcsv($handle, 0, ",", "\"", "\\"));
        while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== false) {
            if (count($row) >= count($header)) {
                $news_items[] = array_combine($header, array_map('trim', array_pad($row, count($header), '')));
            }
        }
        fclose($handle);
    }
}

usort($news_items, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

echo json_encode($news_items);
