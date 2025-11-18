# Multi-stage build para optimizar el tamaño de la imagen
FROM php:8.2-fpm-alpine AS base

# Instalar dependencias del sistema
RUN apk add --no-cache \
    bash \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    postgresql-dev \
    mysql-client \
    nginx \
    supervisor \
    icu-dev \
    icu-libs

# Instalar extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    soap \
    xml \
    intl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario para la aplicación
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Stage de construcción
FROM base AS build

# Copiar archivos de dependencias
COPY --chown=www:www composer.json composer.lock ./

# Instalar dependencias de producción
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar el resto de la aplicación
COPY --chown=www:www . .

# Optimizar autoloader y caché
RUN composer dump-autoload --optimize --classmap-authoritative

# Stage de producción
FROM base AS production

# Copiar aplicación construida
COPY --from=build --chown=www:www /var/www/html /var/www/html

# Copiar configuraciones
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Crear directorios necesarios
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/certificates \
    bootstrap/cache \
    /var/log/supervisor && \
    chown -R www:www storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    chmod +x /usr/local/bin/entrypoint.sh

# Exponer puerto
EXPOSE 80

# Comando de inicio (ejecutar como root para que supervisord pueda gestionar procesos)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
