#!/bin/bash

# Purissima Application Build Script for Jenkins Freestyle
# This script builds the Docker image

set -e  # Exit on any error

# Configuration
DOCKER_IMAGE="purissima-app"
DOCKER_TAG="${BUILD_NUMBER:-latest}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Start build
print_step "Starting Purissima build process..."

# Step 1: Build Docker image
print_step "Building Docker image..."
docker build -f Dockerfile.prod -t "${DOCKER_IMAGE}:${DOCKER_TAG}" .

# Step 2: Tag as latest
print_step "Tagging image as latest..."
docker tag "${DOCKER_IMAGE}:${DOCKER_TAG}" "${DOCKER_IMAGE}:latest"

# Step 3: Show image info
print_status "Docker image built successfully"
docker images "${DOCKER_IMAGE}"

# Step 4: Save image info for post-build
print_step "Saving build info..."
echo "DOCKER_IMAGE=${DOCKER_IMAGE}" > build-info.env
echo "DOCKER_TAG=${DOCKER_TAG}" >> build-info.env
echo "CONTAINER_NAME=purissima-app" >> build-info.env

print_status "✅ Build completed successfully!"
print_status "Image: ${DOCKER_IMAGE}:${DOCKER_TAG}"
print_status "Build info saved to: build-info.env"
