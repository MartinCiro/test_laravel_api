FROM php:8.2-fpm

# Arguments defined in docker-compose.yml
ARG user=laravel
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    default-mysql-client \
    wait-for-it \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

# Copy composer files first (for better layer caching)
COPY composer.json composer.lock ./

# Install dependencies (cached layer)
RUN composer install --no-dev --optimize-autoloader --no-scripts


# Copy the rest of the application
COPY . .

# Copy custom php.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN composer dump-autoload

# Copy deployment script
COPY docker/deploy.sh /usr/local/bin/deploy.sh
RUN chmod +x /usr/local/bin/deploy.sh

# Fix permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache vendor

# Change current user to www-data
USER $user

# Expose port 9000 and start php-fpm server
EXPOSE 9000

# Command to run deployment script and then php-fpm
CMD ["/bin/bash", "-c", "/usr/local/bin/deploy.sh && php-fpm"]