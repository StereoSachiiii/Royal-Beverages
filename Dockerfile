FROM php:8.2-apache

# Install PostgreSQL client & dev libraries needed for PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install and enable PostgreSQL PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache mod_rewrite for .htaccess routing
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# The standard PHP image uses /var/www/html as DocumentRoot.
# We will map our project root here, so index.php and public are available.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Increase request header and line limit to handle large cookies/headers
RUN echo "LimitRequestFieldSize 32768" >> /etc/apache2/apache2.conf \
    && echo "LimitRequestLine 32768" >> /etc/apache2/apache2.conf

# Make sure permissions are correct for Apache
RUN chown -R www-data:www-data /var/www/html
