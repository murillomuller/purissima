#!/bin/bash

echo "🐳 Setting up Purissima with Docker..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from env.example..."
    cp env.example .env
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/logs storage/uploads storage/output

# Build and run with Docker Compose
echo "🚀 Building and starting containers..."
docker-compose up --build -d

echo "✅ Setup complete!"
echo ""
echo "🌐 Application is running at: http://localhost:8000"
echo "📋 Available pages:"
echo "   - Home: http://localhost:8000"
echo "   - Orders: http://localhost:8000/orders"
echo "   - PDF Editor: http://localhost:8000/pdf"
echo "   - About: http://localhost:8000/about"
echo ""
echo "🛠️  To stop the application: docker-compose down"
echo "📊 To view logs: docker-compose logs -f"
