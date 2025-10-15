#!/bin/bash

echo "========================================"
echo "  PURISSIMA ITEM NAMES EXTRACTOR"
echo "  Node.js Version"
echo "========================================"
echo

# Check if Node.js is available
if ! command -v node &> /dev/null; then
    echo "Error: Node.js is not installed or not in PATH"
    echo "Please install Node.js from https://nodejs.org/ and try again"
    exit 1
fi

echo "Node.js found. Running item extraction script..."
echo

# Run the Node.js extractor
node extract-item-names.js

echo
echo "========================================"
echo "  EXTRACTION COMPLETED"
echo "========================================"
echo
echo "Check the generated files for results:"
echo "- unique-item-names_*.txt (list of unique names)"
echo "- item-frequency_*.csv (frequency data with percentages)"
echo "- item-details_*.json (detailed analysis)"
echo "- raw-orders-data_*.json (raw API data)"
echo
