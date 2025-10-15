<?php

require_once 'vendor/autoload.php';

use App\Services\PurissimaApiService;
use App\Services\LoggerService;

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Initialize logger and API service
$logger = new LoggerService();
$apiService = new PurissimaApiService($logger);

try {
    echo "Fetching orders from API...\n";
    $orders = $apiService->getOrders();
    
    $itemNames = [];
    $totalItems = 0;
    
    foreach ($orders as $orderId => $orderData) {
        $items = $orderData['items'] ?? [];
        
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
    
    echo "\n=== ITEM NAMES EXTRACTION RESULTS ===\n";
    echo "Total orders processed: " . count($orders) . "\n";
    echo "Total items found: " . $totalItems . "\n";
    echo "Unique item names: " . count($uniqueItemNames) . "\n\n";
    
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
    
    // Save to file
    $outputFile = 'unique-item-names.txt';
    file_put_contents($outputFile, implode("\n", $uniqueItemNames));
    echo "\nUnique item names saved to: " . $outputFile . "\n";
    
    // Save frequency data to CSV
    $csvFile = 'item-frequency.csv';
    $csvContent = "Item Name,Frequency\n";
    foreach ($frequency as $name => $count) {
        $csvContent .= '"' . str_replace('"', '""', $name) . '",' . $count . "\n";
    }
    file_put_contents($csvFile, $csvContent);
    echo "Item frequency data saved to: " . $csvFile . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nScript completed successfully!\n";
