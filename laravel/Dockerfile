# Imagen oficial PHP 8.4 con Apache 2.4 + mod_php.
FROM php:8.4-apache

# Dependencias del sistema y extensiones PHP que usa Laravel.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libicu-dev \
        libzip-dev \
        zlib1g-dev \
        libonig-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        default-mysql-client \
        openssl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Habilitamos los módulos de Apache que usamos.
RUN a2enmod rewrite ssl headers

# Generamos un certificado autofirmado para HTTPS (solo desarrollo).
RUN mkdir -p /etc/apache2/certs \
    && openssl req -x509 -nodes -days 365 \
        -newkey rsa:2048 \
        -keyout /etc/apache2/certs/betterscan.key \
        -out    /etc/apache2/certs/betterscan.crt \
        -subj "/C=ES/ST=Madrid/L=Madrid/O=BetterScan/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

# Copiamos la configuración de Apache (VirtualHosts 80 y 443).
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Composer (binario oficial).
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Apuntamos el DocumentRoot a public/ y arreglamos permisos.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Script de arranque: composer install, espera a MariaDB, migrate, seed.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 443
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
