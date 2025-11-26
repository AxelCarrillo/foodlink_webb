# Usa PHP 8.2 con Apache
FROM php:8.2-apache

# Actualiza paquetes
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Habilitar mod_rewrite (importante para rutas amigables)
RUN a2enmod rewrite

# Copiar el proyecto al directorio público de Apache
COPY . /var/www/html/

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar Apache para permitir .htaccess
RUN echo "<Directory /var/www/html/> \
    AllowOverride All \
    Require all granted \
</Directory>" > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Puerto por defecto de Apache
EXPOSE 80

# Comando final
CMD ["apache2-foreground"]
