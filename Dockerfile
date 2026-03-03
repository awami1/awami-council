FROM php:8.1-cli

# Install system dependency for mbstring (Oniguruma regex library)
RUN apt-get update && apt-get install -y libonig-dev && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Create data directory for SQLite (dev fallback)
RUN mkdir -p data

EXPOSE 80

# استخدام start.sh لدعم PORT env variable
CMD ["bash", "start.sh"]
