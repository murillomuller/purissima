#!/usr/bin/env node

/**
 * Purissima Item Names Extractor - Node.js Version
 * Extracts all unique item names from the Purissima API orders endpoint
 */

const https = require("https");
const http = require("http");
const fs = require("fs");
const path = require("path");

// Configuration
const config = {
  apiUrl: "https://api.purissima.com/receituario/get-orders.php", // Update this URL as needed
  timeout: 30000, // 30 seconds
  userAgent: "Purissima-Item-Extractor-Node/1.0",
};

// Colors for console output
const colors = {
  reset: "\x1b[0m",
  bright: "\x1b[1m",
  red: "\x1b[31m",
  green: "\x1b[32m",
  yellow: "\x1b[33m",
  blue: "\x1b[34m",
  magenta: "\x1b[35m",
  cyan: "\x1b[36m",
};

function log(message, color = "reset") {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function makeHttpRequest(url) {
  return new Promise((resolve, reject) => {
    const isHttps = url.startsWith("https://");
    const client = isHttps ? https : http;

    const options = {
      timeout: config.timeout,
      headers: {
        "Content-Type": "application/json; charset=utf-8",
        Accept: "application/json; charset=utf-8",
        "User-Agent": config.userAgent,
      },
    };

    const req = client.get(url, options, (res) => {
      let data = "";

      res.on("data", (chunk) => {
        data += chunk;
      });

      res.on("end", () => {
        try {
          const jsonData = JSON.parse(data);
          resolve({
            statusCode: res.statusCode,
            data: jsonData,
            headers: res.headers,
          });
        } catch (error) {
          reject(new Error(`Invalid JSON response: ${error.message}`));
        }
      });
    });

    req.on("error", (error) => {
      reject(new Error(`Request failed: ${error.message}`));
    });

    req.on("timeout", () => {
      req.destroy();
      reject(new Error("Request timeout"));
    });
  });
}

function extractItemNames(orders) {
  const itemNames = [];
  const itemDetails = [];
  let totalItems = 0;
  let ordersProcessed = 0;

  for (const [orderId, orderData] of Object.entries(orders)) {
    ordersProcessed++;

    // Handle different possible data structures
    let items = [];
    if (orderData.items) {
      items = orderData.items;
    } else if (orderData.order && orderData.order.items) {
      items = orderData.order.items;
    } else if (
      Array.isArray(orderData) &&
      orderData.length > 0 &&
      typeof orderData[0] === "object"
    ) {
      // If the order data is an array of items
      items = orderData;
    }

    for (const item of items) {
      if (item.itm_name) {
        const itemName = item.itm_name.trim();
        if (itemName) {
          itemNames.push(itemName);
          itemDetails.push({
            name: itemName,
            orderId: orderId,
            subscription: item.subscription || "N/A",
            quantity: item.quantity || 1,
          });
          totalItems++;
        }
      }
    }
  }

  return {
    itemNames,
    itemDetails,
    totalItems,
    ordersProcessed,
  };
}

function generateReports(extractionResults) {
  const { itemNames, itemDetails, totalItems, ordersProcessed } =
    extractionResults;
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-").slice(0, 19);

  // Get unique item names
  const uniqueItemNames = [...new Set(itemNames)].sort();

  // Calculate frequency
  const frequency = {};
  itemNames.forEach((name) => {
    frequency[name] = (frequency[name] || 0) + 1;
  });

  // Sort frequency by count (descending)
  const sortedFrequency = Object.entries(frequency)
    .sort(([, a], [, b]) => b - a)
    .reduce((obj, [key, value]) => {
      obj[key] = value;
      return obj;
    }, {});

  // Generate files
  const files = {};

  // 1. Unique item names (simple text file)
  const uniqueFile = `unique-item-names_${timestamp}.txt`;
  const uniqueContent = uniqueItemNames.join("\n");
  fs.writeFileSync(uniqueFile, uniqueContent, "utf8");
  files.unique = uniqueFile;

  // 2. Frequency data (CSV)
  const csvFile = `item-frequency_${timestamp}.csv`;
  let csvContent = "Item Name,Frequency,Percentage\n";
  Object.entries(sortedFrequency).forEach(([name, count]) => {
    const percentage = ((count / totalItems) * 100).toFixed(2);
    csvContent += `"${name.replace(/"/g, '""')}",${count},${percentage}%\n`;
  });
  fs.writeFileSync(csvFile, csvContent, "utf8");
  files.frequency = csvFile;

  // 3. Detailed item data (JSON)
  const detailsFile = `item-details_${timestamp}.json`;
  const detailsContent = {
    summary: {
      totalOrders: ordersProcessed,
      totalItems: totalItems,
      uniqueItems: uniqueItemNames.length,
      extractionDate: new Date().toISOString(),
    },
    uniqueItemNames,
    frequency: sortedFrequency,
    itemDetails,
  };
  fs.writeFileSync(
    detailsFile,
    JSON.stringify(detailsContent, null, 2),
    "utf8"
  );
  files.details = detailsFile;

  // 4. Raw orders data (JSON)
  const rawFile = `raw-orders-data_${timestamp}.json`;
  fs.writeFileSync(
    rawFile,
    JSON.stringify(extractionResults.rawOrders, null, 2),
    "utf8"
  );
  files.raw = rawFile;

  return {
    uniqueItemNames,
    frequency: sortedFrequency,
    files,
  };
}

function displayResults(results, extractionResults) {
  const { uniqueItemNames, frequency, files } = results;
  const { totalItems, ordersProcessed } = extractionResults;

  log("\n=== EXTRACTION RESULTS ===", "cyan");
  log(`Orders processed: ${ordersProcessed}`, "green");
  log(`Total items found: ${totalItems}`, "green");
  log(`Unique item names: ${uniqueItemNames.length}`, "green");

  if (uniqueItemNames.length === 0) {
    log(
      "\nNo item names found. The API response structure might be different than expected.",
      "yellow"
    );
    return;
  }

  log("\n=== UNIQUE ITEM NAMES ===", "cyan");
  uniqueItemNames.forEach((name, index) => {
    log(`${(index + 1).toString().padStart(3)}. ${name}`, "blue");
  });

  log("\n=== ITEM NAME FREQUENCY ===", "cyan");
  Object.entries(frequency).forEach(([name, count]) => {
    const percentage = ((count / totalItems) * 100).toFixed(1);
    const times = count > 1 ? "times" : "time";
    log(`${name} (appears ${count} ${times} - ${percentage}%)`, "magenta");
  });

  log("\n=== GENERATED FILES ===", "cyan");
  log(`✓ Unique item names: ${files.unique}`, "green");
  log(`✓ Frequency data (CSV): ${files.frequency}`, "green");
  log(`✓ Detailed data (JSON): ${files.details}`, "green");
  log(`✓ Raw orders data: ${files.raw}`, "green");
}

async function main() {
  try {
    log("========================================", "bright");
    log("  PURISSIMA ITEM NAMES EXTRACTOR", "bright");
    log("  Node.js Version", "bright");
    log("========================================", "bright");
    log("");
    log(`Fetching data from: ${config.apiUrl}`, "blue");
    log("");

    // Make API request
    const response = await makeHttpRequest(config.apiUrl);

    if (response.statusCode >= 400) {
      throw new Error(`API returned error status: ${response.statusCode}`);
    }

    const data = response.data;

    // Check API response status
    if (!data.status || data.status !== 1) {
      throw new Error(
        `API returned error status: ${data.message || "Unknown error"}`
      );
    }

    const orders = data.results || {};

    if (Object.keys(orders).length === 0) {
      log("No orders found in the API response.", "yellow");
      return;
    }

    // Extract item names
    const extractionResults = extractItemNames(orders);
    extractionResults.rawOrders = orders;

    // Generate reports
    const results = generateReports(extractionResults);

    // Display results
    displayResults(results, extractionResults);

    log("\n=== SUMMARY ===", "cyan");
    log(
      `✓ Successfully extracted ${results.uniqueItemNames.length} unique item names`,
      "green"
    );
    log(
      `✓ Processed ${extractionResults.ordersProcessed} orders with ${extractionResults.totalItems} total items`,
      "green"
    );
    log("✓ Results saved to multiple files for analysis", "green");
    log("\nScript completed successfully!", "green");
  } catch (error) {
    log(`\nError: ${error.message}`, "red");
    log("\nTroubleshooting tips:", "yellow");
    log("1. Check if the API URL is correct", "yellow");
    log("2. Verify your internet connection", "yellow");
    log("3. Check if the API server is accessible", "yellow");
    log("4. Review the API documentation for any changes", "yellow");
    process.exit(1);
  }
}

// Run the script
if (require.main === module) {
  main();
}

module.exports = {
  extractItemNames,
  generateReports,
  makeHttpRequest,
};
