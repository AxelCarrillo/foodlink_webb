FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# ELIMINAR override.conf si existiera (del build anterior)
RUN rm -f /etc/apache2/conf-enabled/override.conf || true \
    && rm -f /etc/apache2/conf-available/override.conf || true

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
