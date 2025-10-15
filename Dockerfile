FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json ./
COPY composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY . .

# Ensure .env file exists
RUN if [ ! -f .env ]; then cp env.example .env; fi

# Create storage directories
RUN mkdir -p storage/logs storage/uploads storage/output storage/pdf storage/fonts

# Ensure font files are copied and accessible
RUN if [ -d storage/fonts ]; then \
    chmod -R 755 storage/fonts && \
    ls -la storage/fonts; \
    fi

# Set permissions
RUN chown -R www-data:www-data storage

# Expose port
EXPOSE 8000

# Start server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
