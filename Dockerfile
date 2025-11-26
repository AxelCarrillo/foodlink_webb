# Usa PHP 8.2 con Apache
FROM php:8.2-apache

# Actualiza paquetes
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copiar el proyecto al directorio público de Apache
COPY . /var/www/html/

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Puerto por defecto de Apache
EXPOSE 80

# Comando final
CMD ["apache2-foreground"]
