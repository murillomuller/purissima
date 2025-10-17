<?php

require_once 'src/Services/item-name-mappings.php';

/**
 * Apply mappings to item names from a text file
 */

function applyMappingsToFile($inputFile, $outputFile)
{
    $mappings = require 'src/Services/item-name-mappings.php';

    if (!file_exists($inputFile)) {
        echo "Error: Input file '$inputFile' not found.\n";
        return false;
    }

    $lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $mappedLines = [];

    echo "Processing " . count($lines) . " items...\n";

    foreach ($lines as $lineNumber => $line) {
        // Extract the item name (everything after the line number and pipe)
        if (preg_match('/^\s*\d+\|(.+)$/', $line, $matches)) {
            $originalName = trim($matches[1]);
            $mappedName = applyItemNameMappings($originalName, $mappings);

            // Format the output line
            $mappedLines[] = sprintf("%3d|%s", $lineNumber + 1, $mappedName);

            // Show progress for items that were actually changed
            if ($originalName !== $mappedName) {
                echo "Line " . ($lineNumber + 1) . ": '$originalName' -> '$mappedName'\n";
            }
        } else {
            // Keep lines that don't match the expected format
            $mappedLines[] = $line;
        }
    }

    // Write the mapped results to the output file
    file_put_contents($outputFile, implode("\n", $mappedLines));

    echo "\nMapped items saved to: $outputFile\n";
    echo "Total items processed: " . count($lines) . "\n";

    return true;
}

// Main execution
if ($argc < 2) {
    echo "Usage: php apply-item-mappings.php <input-file> [output-file]\n";
    echo "Example: php apply-item-mappings.php unique-item-names_2025-10-15T21-16-29.txt\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argc > 2 ? $argv[2] : str_replace('.txt', '-mapped.txt', $inputFile);

applyMappingsToFile($inputFile, $outputFile);
