<?php

/**
 * Simple script to extract unique item names from Purissima API
 * This version makes a direct API call without requiring the full application setup
 */

// Configuration
$apiUrl = 'https://api.purissima.com/receituario/get-orders.php'; // Update this URL as needed
$timeout = 30;

echo "=== PURISSIMA ITEM NAMES EXTRACTOR ===\n";
echo "Fetching data from: " . $apiUrl . "\n\n";

// Make API request
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => $timeout,
        'header' => [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json; charset=utf-8',
            'User-Agent: Purissima-Item-Extractor/1.0'
        ]
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "Error: Failed to fetch data from API\n";
    echo "Please check the API URL and your internet connection.\n";
    exit(1);
}

// Parse JSON response
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

// Check API response status
if (!isset($data['status']) || $data['status'] !== 1) {
    echo "Error: API returned error status\n";
    echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$orders = $data['results'] ?? [];

if (empty($orders)) {
    echo "No orders found in the API response.\n";
    exit(0);
}

// Extract item names
$itemNames = [];
$totalItems = 0;
$ordersProcessed = 0;

foreach ($orders as $orderId => $orderData) {
    $ordersProcessed++;
    
    // Handle different possible data structures
    $items = [];
    if (isset($orderData['items'])) {
        $items = $orderData['items'];
    } elseif (isset($orderData['order']['items'])) {
        $items = $orderData['order']['items'];
    } elseif (is_array($orderData) && isset($orderData[0]) && is_array($orderData[0])) {
        // If the order data is an array of items
        $items = $orderData;
    }
    
    foreach ($items as $item) {
        if (isset($item['itm_name'])) {
            $itemName = trim($item['itm_name']);
            if (!empty($itemName)) {
                $itemNames[] = $itemName;
                $totalItems++;
            }
        }
    }
}

// Get unique item names
$uniqueItemNames = array_unique($itemNames);
sort($uniqueItemNames);

// Display results
echo "=== EXTRACTION RESULTS ===\n";
echo "Orders processed: " . $ordersProcessed . "\n";
echo "Total items found: " . $totalItems . "\n";
echo "Unique item names: " . count($uniqueItemNames) . "\n\n";

if (empty($uniqueItemNames)) {
    echo "No item names found. The API response structure might be different than expected.\n";
    echo "Here's a sample of the data structure:\n";
    echo json_encode(array_slice($orders, 0, 1, true), JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

echo "=== UNIQUE ITEM NAMES ===\n";
foreach ($uniqueItemNames as $index => $name) {
    echo ($index + 1) . ". " . $name . "\n";
}

echo "\n=== ITEM NAME FREQUENCY ===\n";
$frequency = array_count_values($itemNames);
arsort($frequency);

foreach ($frequency as $name => $count) {
    echo $name . " (appears " . $count . " time" . ($count > 1 ? "s" : "") . ")\n";
}

// Save results to files
$timestamp = date('Y-m-d_H-i-s');

// Save unique names
$uniqueFile = "unique-item-names_{$timestamp}.txt";
file_put_contents($uniqueFile, implode("\n", $uniqueItemNames));
echo "\nUnique item names saved to: " . $uniqueFile . "\n";

// Save frequency data to CSV
$csvFile = "item-frequency_{$timestamp}.csv";
$csvContent = "Item Name,Frequency\n";
foreach ($frequency as $name => $count) {
    $csvContent .= '"' . str_replace('"', '""', $name) . '",' . $count . "\n";
}
file_put_contents($csvFile, $csvContent);
echo "Item frequency data saved to: " . $csvFile . "\n";

// Save raw data for debugging
$rawFile = "raw-orders-data_{$timestamp}.json";
file_put_contents($rawFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Raw orders data saved to: " . $rawFile . "\n";

echo "\n=== SUMMARY ===\n";
echo "✓ Successfully extracted " . count($uniqueItemNames) . " unique item names\n";
echo "✓ Processed " . $ordersProcessed . " orders with " . $totalItems . " total items\n";
echo "✓ Results saved to multiple files for analysis\n";
echo "\nScript completed successfully!\n";
