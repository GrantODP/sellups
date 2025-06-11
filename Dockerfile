
# Use the official PHP Apache image
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Copy source code to the Apache web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Set proper permissions (optional)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80

