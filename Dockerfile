FROM php:8.2-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    zip \
    unzip \
    git

# Installer les extensions PHP
RUN docker-php-ext-install mysqli curl

# Activer mod_rewrite (très important pour PHP)
RUN a2enmod rewrite

# Copier le projet dans Apache
COPY . /var/www/html/

# Donner les bonnes permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port utilisé par Render
EXPOSE 80
