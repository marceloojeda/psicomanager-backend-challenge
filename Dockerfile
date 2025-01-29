# Base PHP 8.1 com suporte FPM
FROM php:8.1-fpm

# Instalar dependências de sistema
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql

RUN apt-get update && apt-get install -y netcat-openbsd

RUN pecl install redis && docker-php-ext-enable redis

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

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

# Configuração de memória e Xdebug
RUN echo "memory_limit = 2G" >> /usr/local/etc/php/conf.d/20-memory-limit.ini

RUN echo "xdebug.mode=debug\n\
    xdebug.start_with_request=yes\n\
    xdebug.discover_client_host=1\n\
    xdebug.client_port=9000\n\
    xdebug.log=/var/log/xdebug.log\n\
    xdebug.max_nesting_level=256" > /usr/local/etc/php/conf.d/20-xdebug.ini

# RUN mkdir -p /var/www/logs && \
#     chmod 777 /var/www/logs

# Copy the application code into the container
COPY ./ /var/www/html

# Remove vendor e composer.lock
RUN chown -R www-data storage

#RUN composer install

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap \
    && chown -R www-data:www-data /var/www/html/storage/logs \
    && chmod 777 -R /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 9000
