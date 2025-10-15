# Purissima Item Names Extractor

A Node.js script to extract all unique item names from the Purissima API orders endpoint.

## Prerequisites

- **Node.js** (version 12.0.0 or higher)
  - Download from: https://nodejs.org/
  - Verify installation: `node --version`

## Quick Start

### Option 1: Direct Execution (Recommended)

```bash
# On Windows
extract-items-node.bat

# On Unix/Linux/macOS
./extract-items-node.sh
```

### Option 2: Using npm scripts

```bash
npm run extract
# or
npm start
```

### Option 3: Direct Node.js execution

```bash
node extract-item-names.js
```

## Features

✅ **Extracts all unique item names** from the API response  
✅ **Shows frequency analysis** - how many times each item appears  
✅ **Calculates percentages** for each item  
✅ **Handles different data structures** - adapts to various API response formats  
✅ **Colorized console output** for better readability  
✅ **Comprehensive error handling** and troubleshooting tips

## Generated Files

The script generates timestamped files for analysis:

1. **`unique-item-names_*.txt`** - Simple list of unique item names
2. **`item-frequency_*.csv`** - Frequency data with percentages in CSV format
3. **`item-details_*.json`** - Detailed analysis including:
   - Summary statistics
   - Unique item names
   - Frequency data
   - Individual item details (order ID, subscription, quantity)
4. **`raw-orders-data_*.json`** - Raw API response data for debugging

## Configuration

Edit the `config` object in `extract-item-names.js` to customize:

```javascript
const config = {
  apiUrl: "https://api.purissima.com/receituario/get-orders.php", // API endpoint
  timeout: 30000, // Request timeout in milliseconds
  userAgent: "Purissima-Item-Extractor-Node/1.0", // User agent string
};
```

## Sample Output

```
========================================
  PURISSIMA ITEM NAMES EXTRACTOR
  Node.js Version
========================================

Fetching data from: https://api.purissima.com/receituario/get-orders.php

=== EXTRACTION RESULTS ===
Orders processed: 25
Total items found: 150
Unique item names: 45

=== UNIQUE ITEM NAMES ===
  1. Acetaminophen 500mg
  2. Amoxicillin 250mg
  3. Aspirin 100mg
  ...

=== ITEM NAME FREQUENCY ===
Acetaminophen 500mg (appears 15 times - 10.0%)
Amoxicillin 250mg (appears 12 times - 8.0%)
Aspirin 100mg (appears 8 times - 5.3%)
...

=== GENERATED FILES ===
✓ Unique item names: unique-item-names_2024-01-15T10-30-45.txt
✓ Frequency data (CSV): item-frequency_2024-01-15T10-30-45.csv
✓ Detailed data (JSON): item-details_2024-01-15T10-30-45.json
✓ Raw orders data: raw-orders-data_2024-01-15T10-30-45.json

=== SUMMARY ===
✓ Successfully extracted 45 unique item names
✓ Processed 25 orders with 150 total items
✓ Results saved to multiple files for analysis

Script completed successfully!
```

## Troubleshooting

### Common Issues

1. **"Node.js is not installed"**

   - Install Node.js from https://nodejs.org/
   - Restart your terminal/command prompt

2. **"Request timeout"**

   - Check your internet connection
   - Increase the timeout value in the config
   - Verify the API server is accessible

3. **"API returned error status"**

   - Check if the API URL is correct
   - Verify the API server is running
   - Check API documentation for any changes

4. **"No item names found"**
   - The API response structure might be different
   - Check the raw-orders-data\_\*.json file to see the actual response
   - The script handles various data structures automatically

### Debug Mode

To see more detailed information about the API response, check the generated `raw-orders-data_*.json` file.

## API Response Structure

The script expects the API to return data in this format:

```json
{
  "status": 1,
  "results": {
    "order_id_1": {
      "order": { ... },
      "items": [
        {
          "itm_name": "Item Name",
          "subscription": "Subscription Info",
          "quantity": 1
        }
      ]
    }
  }
}
```

The script automatically adapts to different response structures.

## License

MIT License - Feel free to modify and use as needed.
