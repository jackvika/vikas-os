# Vikas OS portfolio: PHP 8.2 + Apache (keeps .htaccess rules working)
FROM php:8.2-apache

# Apache modules used by .htaccess + allow .htaccess overrides
RUN a2enmod headers expires deflate rewrite \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Production PHP settings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { echo "expose_php = Off"; echo "date.timezone = Asia/Kolkata"; echo "variables_order = EGPCS"; } > "$PHP_INI_DIR/conf.d/zz-site.ini"

COPY . /var/www/html/
RUN mkdir -p /var/www/html/data \
 && chown -R www-data:www-data /var/www/html/data

# Railway provides $PORT; Apache listens on it (defaults to 80 locally)
CMD ["sh", "-c", "sed -ri \"s/Listen [0-9]+/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
