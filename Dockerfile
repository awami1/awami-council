FROM php:8.1-cli

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Create data directory for SQLite (dev fallback)
RUN mkdir -p data

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "router.php"]
