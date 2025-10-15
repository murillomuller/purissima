#!/bin/bash

echo "Starting Purissima PHP Project with Hot Reload..."
echo ""
echo "This will:"
echo "- Mount your local files into the container for hot reload"
echo "- Start the PHP development server"
echo "- Automatically reload when you change files"
echo ""
echo "Press Ctrl+C to stop"
echo ""

docker-compose -f docker-compose.dev.yml up --build
