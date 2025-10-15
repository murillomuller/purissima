#!/bin/bash

# Unraid Setup Script for Purissima Application
# This script sets up the necessary directories and permissions for Unraid

set -e

echo "🚀 Setting up Purissima on Unraid..."

# Configuration
UNRAID_APP_DATA="/mnt/user/appdata/purissima"
UNRAID_SHARES="/mnt/user"
APP_NAME="purissima"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root on Unraid"
   exit 1
fi

# Create appdata directory structure
print_status "Creating appdata directory structure..."
mkdir -p "${UNRAID_APP_DATA}"/{storage/{logs,uploads,output,pdf,fonts},logs,mysql,ssl,nginx-logs}

# Set proper permissions
print_status "Setting permissions..."
chmod -R 755 "${UNRAID_APP_DATA}"
chown -R nobody:users "${UNRAID_APP_DATA}"

# Create .env file if it doesn't exist
if [ ! -f "${UNRAID_APP_DATA}/.env" ]; then
    print_status "Creating .env file..."
    cat > "${UNRAID_APP_DATA}/.env" << EOF
# Purissima Application Configuration for Unraid
APP_NAME="Purissima PHP Project"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://unraid-server:8000

# Database Configuration
DB_HOST=purissima-mysql
DB_PORT=3306
DB_DATABASE=purissima
DB_USERNAME=purissima
DB_PASSWORD=purissimapassword

# External API Configuration
API_BASE_URL=https://api.purissima.com
API_TIMEOUT=30
API_RETRY_ATTEMPTS=3

# PDF Configuration
PDF_UPLOAD_PATH=storage/uploads
PDF_OUTPUT_PATH=storage/output
PDF_MAX_SIZE=10485760

# Logging
LOG_LEVEL=info
LOG_FILE=storage/logs/app.log

# Timezone
TZ=America/Sao_Paulo
EOF
    chmod 644 "${UNRAID_APP_DATA}/.env"
    chown nobody:users "${UNRAID_APP_DATA}/.env"
fi

# Create docker-compose override for Unraid
print_status "Creating docker-compose override..."
cat > "docker-compose.override.yml" << EOF
version: '3.8'

services:
  purissima-app:
    volumes:
      - ${UNRAID_APP_DATA}/storage:/var/www/html/storage
      - ${UNRAID_APP_DATA}/logs:/var/log/purissima
      - ${UNRAID_APP_DATA}/.env:/var/www/html/.env:ro
    environment:
      - TZ=America/Sao_Paulo
    labels:
      - "net.unraid.docker.managed=dockerman"
      - "net.unraid.docker.webui=http://[IP]:[PORT:8000]/"
      - "net.unraid.docker.icon=https://raw.githubusercontent.com/linuxserver/docker-templates/master/linuxserver.io/img/php-icon.png"

  purissima-nginx:
    volumes:
      - ${UNRAID_APP_DATA}/ssl:/etc/nginx/ssl:ro
      - ${UNRAID_APP_DATA}/nginx-logs:/var/log/nginx
    labels:
      - "net.unraid.docker.managed=dockerman"
      - "net.unraid.docker.webui=http://[IP]:[PORT:80]/"

  purissima-mysql:
    volumes:
      - ${UNRAID_APP_DATA}/mysql:/var/lib/mysql
    environment:
      - TZ=America/Sao_Paulo
    labels:
      - "net.unraid.docker.managed=dockerman"
EOF

# Create Unraid template file
print_status "Creating Unraid template..."
cat > "purissima-unraid.xml" << EOF
<?xml version="1.0"?>
<Container version="2">
  <Name>purissima-app</Name>
  <Repository>purissima-app:latest</Repository>
  <Registry>https://hub.docker.com/</Registry>
  <Network>bridge</Network>
  <MyIP>192.168.1.100</MyIP>
  <Shell>bash</Shell>
  <Privileged>false</Privileged>
  <Support>https://github.com/your-repo/purissima</Support>
  <Project>https://github.com/your-repo/purissima</Project>
  <Overview>Purissima PHP Application for prescription generation and management</Overview>
  <Category>Medical:Other</Category>
  <WebUI>http://[IP]:[PORT:8000]/</WebUI>
  <TemplateURL/>
  <Icon>https://raw.githubusercontent.com/linuxserver/docker-templates/master/linuxserver.io/img/php-icon.png</Icon>
  <ExtraParams/>
  <PostArgs/>
  <CPUset/>
  <DateInstalled>$(date +%s)</DateInstalled>
  <DonateText/>
  <DonateLink/>
  <Description>Purissima PHP Application for prescription generation and management</Description>
  <Networking>
    <Mode>bridge</Mode>
    <Publish>
      <Port>
        <HostPort>8000</HostPort>
        <ContainerPort>8000</ContainerPort>
        <Protocol>tcp</Protocol>
      </Port>
    </Publish>
  </Networking>
  <Data>
    <Volume>
      <HostDir>${UNRAID_APP_DATA}/storage</HostDir>
      <ContainerDir>/var/www/html/storage</ContainerDir>
      <Mode>rw</Mode>
    </Volume>
    <Volume>
      <HostDir>${UNRAID_APP_DATA}/logs</HostDir>
      <ContainerDir>/var/log/purissima</ContainerDir>
      <Mode>rw</Mode>
    </Volume>
    <Volume>
      <HostDir>${UNRAID_APP_DATA}/.env</HostDir>
      <ContainerDir>/var/www/html/.env</ContainerDir>
      <Mode>ro</Mode>
    </Volume>
  </Data>
  <Environment>
    <Variable>
      <Value>production</Value>
      <Mode/>
    </Variable>
    <Variable>
      <Value>false</Value>
      <Mode/>
    </Variable>
    <Variable>
      <Value>America/Sao_Paulo</Value>
      <Mode/>
    </Variable>
  </Environment>
  <Labels/>
  <Config Name="APP_ENV" Target="APP_ENV" Default="production" Mode="" Description="Application Environment" Type="Variable" Display="always" Required="true" Mask="false">production</Config>
  <Config Name="APP_DEBUG" Target="APP_DEBUG" Default="false" Mode="" Description="Application Debug Mode" Type="Variable" Display="always" Required="true" Mask="false">false</Config>
  <Config Name="TZ" Target="TZ" Default="America/Sao_Paulo" Mode="" Description="Timezone" Type="Variable" Display="always" Required="true" Mask="false">America/Sao_Paulo</Config>
  <Config Name="Storage" Target="/var/www/html/storage" Default="${UNRAID_APP_DATA}/storage" Mode="rw" Description="Application Storage" Type="Path" Display="always" Required="true" Mask="false">${UNRAID_APP_DATA}/storage</Config>
  <Config Name="Logs" Target="/var/log/purissima" Default="${UNRAID_APP_DATA}/logs" Mode="rw" Description="Application Logs" Type="Path" Display="always" Required="true" Mask="false">${UNRAID_APP_DATA}/logs</Config>
  <Config Name="Environment" Target="/var/www/html/.env" Default="${UNRAID_APP_DATA}/.env" Mode="ro" Description="Environment Configuration" Type="Path" Display="always" Required="true" Mask="false">${UNRAID_APP_DATA}/.env</Config>
</Container>
EOF

print_status "Setup complete! 🎉"
echo ""
print_status "Next steps:"
echo "1. Copy the purissima-unraid.xml to your Unraid server"
echo "2. Import it in Unraid Docker tab"
echo "3. Or use: docker-compose -f docker-compose.unraid.yml up -d"
echo ""
print_status "Application will be available at: http://your-unraid-ip:8000"
echo ""
print_warning "Don't forget to:"
echo "- Update APP_URL in ${UNRAID_APP_DATA}/.env with your actual Unraid IP"
echo "- Configure your firewall if needed"
echo "- Set up SSL certificates if using HTTPS"
