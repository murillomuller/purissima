#!/bin/bash

# Purissima Application Post-Build Script for Jenkins Freestyle (Simplified)
# This script deploys the built Docker image with simplified health checks

set -e  # Exit on any error

# Load build info
if [ -f "build-info.env" ]; then
    source build-info.env
else
    print_error "Build info file not found. Make sure build step completed successfully."
    exit 1
fi

# Configuration
CONTAINER_NAME="purissima-app"
EXTERNAL_PORT="5666"
INTERNAL_PORT="8000"

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

# Start deployment
print_step "Starting Purissima deployment..."

# Step 1: Stop and remove existing container
print_step "Stopping existing container..."
if docker ps -q -f name="${CONTAINER_NAME}" | grep -q .; then
    docker stop "${CONTAINER_NAME}"
    print_status "Container stopped"
else
    print_warning "No running container found"
fi

if docker ps -aq -f name="${CONTAINER_NAME}" | grep -q .; then
    docker rm "${CONTAINER_NAME}"
    print_status "Container removed"
else
    print_warning "No existing container found"
fi

# Step 2: Run new container
print_step "Starting new container on port ${EXTERNAL_PORT}..."
docker run -d \
    --name "${CONTAINER_NAME}" \
    --restart unless-stopped \
    -p ${EXTERNAL_PORT}:${INTERNAL_PORT} \
    -v purissima-storage:/var/www/html/storage \
    -v purissima-logs:/var/log/purissima \
    -e APP_ENV=production \
    -e APP_DEBUG=false \
    -e TZ=America/Sao_Paulo \
    --label "net.unraid.docker.managed=dockerman" \
    --label "net.unraid.docker.webui=http://[IP]:[PORT:${EXTERNAL_PORT}]/" \
    "${DOCKER_IMAGE}:${DOCKER_TAG}"

print_status "Container started successfully"

# Step 3: Wait for container to be ready
print_step "Waiting for container to be ready..."
sleep 20

# Step 4: Simple health check
print_step "Performing health check..."

# Check if container is running
if ! docker ps | grep -q "${CONTAINER_NAME}"; then
    print_error "Container is not running!"
    docker logs "${CONTAINER_NAME}" --tail 20
    exit 1
fi

# Check if PHP server started successfully
if docker logs "${CONTAINER_NAME}" --tail 10 | grep -q "PHP.*Development Server.*started"; then
    print_status "✅ Health check passed - PHP server is running!"
else
    print_error "Health check failed - PHP server may not have started"
    print_warning "Container logs:"
    docker logs "${CONTAINER_NAME}" --tail 20
    exit 1
fi

# Step 5: Show container status
print_step "Container status:"
docker ps | grep "${CONTAINER_NAME}"

# Step 6: Show volumes
print_step "Docker volumes:"
docker volume ls | grep purissima

# Step 7: Cleanup old images (keep last 5)
print_step "Cleaning up old images..."
OLD_IMAGES=$(docker images "${DOCKER_IMAGE}" --format "table {{.Tag}}\t{{.ID}}" | tail -n +2 | head -n -5 | awk '{print $2}')
if [ -n "$OLD_IMAGES" ]; then
    echo "$OLD_IMAGES" | xargs docker rmi || true
    print_status "Old images cleaned up"
else
    print_warning "No old images to clean up"
fi

# Success message
print_status "🎉 Deployment completed successfully!"
print_status "Application is available at: http://your-unraid-ip:${EXTERNAL_PORT}"
print_status "Container name: ${CONTAINER_NAME}"
print_status "Image: ${DOCKER_IMAGE}:${DOCKER_TAG}"
print_status "External port: ${EXTERNAL_PORT}"

# Show useful commands
echo ""
print_step "Useful commands:"
echo "  View logs: docker logs ${CONTAINER_NAME}"
echo "  Stop app:  docker stop ${CONTAINER_NAME}"
echo "  Restart:   docker restart ${CONTAINER_NAME}"
echo "  Shell:     docker exec -it ${CONTAINER_NAME} /bin/bash"
echo "  Test app:  curl http://localhost:${EXTERNAL_PORT}/"
