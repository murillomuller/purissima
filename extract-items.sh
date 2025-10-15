#!/bin/bash

echo "========================================"
echo "  PURISSIMA ITEM NAMES EXTRACTOR"
echo "========================================"
echo

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    echo "Please install PHP and try again"
    exit 1
fi

echo "PHP found. Running item extraction script..."
echo

# Run the simple extractor
php simple-item-extractor.php

echo
echo "========================================"
echo "  EXTRACTION COMPLETED"
echo "========================================"
echo
echo "Check the generated files for results:"
echo "- unique-item-names_*.txt (list of unique names)"
echo "- item-frequency_*.csv (frequency data)"
echo "- raw-orders-data_*.json (raw API data)"
echo
