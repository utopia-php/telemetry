FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    libcurl4-openssl-dev \
    libprotobuf-dev \
    protobuf-compiler \
    && rm -rf /var/lib/apt/lists/*

# Install Swoole
RUN pecl install swoole && docker-php-ext-enable swoole

# Install protobuf extension
RUN pecl install protobuf && docker-php-ext-enable protobuf

# Install OpenTelemetry extension
RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry

# Install sockets extension
RUN docker-php-ext-install sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Default command
CMD ["php", "-v"]
