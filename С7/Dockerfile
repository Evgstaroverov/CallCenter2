FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Устанавливаем расширения PHP, необходимые для Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копируем код приложения
COPY . /var/www

# Настраиваем права с проверкой существования директорий
RUN if [ -d "/var/www/storage" ]; then chmod -R 755 /var/www/storage; else mkdir -p /var/www/storage && chmod -R 755 /var/www/storage; fi && \
    if [ -d "/var/www/bootstrap/cache" ]; then chmod -R 755 /var/www/bootstrap/cache; else mkdir -p /var/www/bootstrap/cache && chmod -R 755 /var/www/bootstrap/cache; fi && \
    chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]