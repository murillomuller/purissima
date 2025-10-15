<?php

/**
 * Setup script for Purissima PHP Project
 * Run this script to initialize the project
 */

echo "🚀 Setting up Purissima PHP Project...\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "❌ PHP 8.1 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}

echo "✅ PHP version: " . PHP_VERSION . "\n";

// Check if composer is installed
if (!file_exists('composer.json')) {
    echo "❌ composer.json not found. Please run this script from the project root.\n";
    exit(1);
}

// Create .env file if it doesn't exist
if (!file_exists('.env')) {
    if (file_exists('env.example')) {
        copy('env.example', '.env');
        echo "✅ Created .env file from env.example\n";
    } else {
        echo "⚠️  env.example not found. Please create .env file manually.\n";
    }
} else {
    echo "✅ .env file already exists\n";
}

// Check storage directories
$directories = ['storage/logs', 'storage/uploads', 'storage/output'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Check if vendor directory exists
if (!is_dir('vendor')) {
    echo "\n📦 Installing dependencies with Composer...\n";
    echo "Run: composer install\n";
} else {
    echo "✅ Dependencies already installed\n";
}

echo "\n🎉 Setup complete!\n\n";
echo "Next steps:\n";
echo "1. Run: composer install\n";
echo "2. Edit .env file with your configuration\n";
echo "3. Run: composer serve\n";
echo "4. Open: http://localhost:8000\n\n";
echo "Happy coding! 🚀\n";
