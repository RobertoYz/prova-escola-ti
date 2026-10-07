FROM php:8.2-cli

RUN apt-get update && apt-get install -y unzip sqlite3 libsqlite3-dev
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY src/api-prova /app
COPY variante /app/variante

RUN composer install --no-dev --optimize-autoloader
RUN cp .env.example .env && php artisan key:generate

RUN mkdir -p /data && touch /data/database.sqlite
RUN chown -R www-data:www-data /data && chmod -R 775 /data

ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/data/database.sqlite

RUN php artisan migrate --force

ENV PHP_CLI_SERVER_WORKERS=4
CMD ["php", "-S", "0.0.0.0:8080", "server.php"]
