<?php

require_once 'src/Services/DoseMapper.php';

use App\Services\DoseMapper;

$mapper = new DoseMapper();

// Test the exact problematic item name
$itemName = 'Problemas Masculinos – Leve (mesmos SKUs da Saúde Masculina na rota Sintomas):';

echo "Testing DoseMapper with problematic item name:\n";
echo "=" . str_repeat("=", 80) . "\n\n";

echo "Original item name: " . $itemName . "\n\n";

// Get the dosage text
$dosage = $mapper->getDosageText($itemName);

echo "Dosage result: " . ($dosage ?: "NO MAPPING FOUND") . "\n\n";

// Let's also test what the normalization should produce
echo "Expected normalized key: 'problemas masculinos – leve'\n";
echo "Available mappings for 'problemas masculinos':\n";

// Check if we can find any matching keys
$reflection = new ReflectionClass($mapper);
$property = $reflection->getProperty('doseByItem');
$property->setAccessible(true);
$mappings = $property->getValue($mapper);

foreach ($mappings as $key => $value) {
    if (strpos($key, 'problemas masculinos') !== false) {
        echo "- '$key' => '$value'\n";
    }
}

echo "\nTest completed.\n";
