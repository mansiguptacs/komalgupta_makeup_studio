<?php
header('Content-Type: application/json');

function readCsvData($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 0, ",", "\"", "\\");
        while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
            if (count($headers) == count($row)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

$services = readCsvData(__DIR__ . '/../data/services.csv');
$products = readCsvData(__DIR__ . '/../data/products.csv');

echo json_encode([
    'services' => $services,
    'products' => $products
]);
