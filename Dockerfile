# Base PHP 8.1 com suporte FPM
FROM php:8.1-fpm

# Cria usuário para poder executar a aplicação, e comandos, sem ser como root
RUN USEUID=1000; \
    if [ "$(getent passwd $USEUID)" = "" ];then \
        useradd -u $USEUID appuser -m; \
        usermod -a -G www-data appuser; \
    fi

# Instalar dependências de sistema
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql

# Instalar o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do Lumen
RUN apt-get -y update \
    && apt-get install -y libssl-dev pkg-config libzip-dev unzip git \
    && apt-get install -y libmagickwand-dev imagemagick --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*
 
RUN pecl install zlib zip imagick \
    && docker-php-ext-enable zip \
    && docker-php-ext-enable imagick


RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql    
 
# RUN mkdir -p /var/www/logs && \
#     chmod 777 /var/www/logs

USER appuser