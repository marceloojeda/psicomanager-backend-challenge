# Base PHP 8.1 com suporte FPM
FROM php:8.1-fpm

# Instalar dependências de sistema
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql

# Instalar o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copiar o projeto para o contêiner
COPY ./ /var/www/html

# Instalar dependências do Lumen
RUN apt-get -y update \
    && apt-get install -y libssl-dev pkg-config libzip-dev unzip git \
    && apt-get install -y libmagickwand-dev imagemagick --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*
 
RUN pecl install zlib zip imagick \
    && docker-php-ext-enable zip \
    && docker-php-ext-enable imagick


RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql    
 
# Install composer (updated via entry point)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# RUN mkdir -p /var/www/logs && \
#     chmod 777 /var/www/logs

# Copy the application code into the container
COPY ./ /var/www/html
 
# Remove vendor e composer.lock
RUN chown -R www-data storage
 
RUN composer install